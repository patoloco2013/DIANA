<?php
declare(strict_types=1);

namespace Diana\Controllers;

use Diana\Core\Catalogos;
use Diana\Core\Controller;
use Diana\Core\Database;

/**
 * Registro de asistentes a eventos.
 * El precio sale de la categoría del asistente y la modalidad elegida
 * (evento_precios); al registrar un socio se genera el cargo automático
 * en su estado de cuenta y se le asignan los puntos DPC del evento.
 */
final class RegistroController extends Controller
{
    public const MODULO = 'registro';

    /** Lista de eventos vigentes para tomar registro. */
    public function index(): void
    {
        $eventos = Database::todas(
            "SELECT e.*,
                    (SELECT COUNT(*) FROM asistencias a WHERE a.evento_id = e.id) AS registrados,
                    (SELECT COALESCE(SUM(p.puntos), 0) FROM evento_puntos p WHERE p.evento_id = e.id) AS puntos_dpc
             FROM eventos e
             WHERE e.colegio_id = ? AND e.estatus = 'publicado'
             ORDER BY e.fecha_inicio DESC LIMIT 100",
            [$this->colegioId()]);

        $this->vista('registro/index', [
            'titulo' => 'Registro de eventos',
            'eventos' => $eventos,
            'modalidades' => Catalogos::MODALIDADES,
        ]);
    }

    /** Panel de registro de un evento: asistentes + alta rápida. */
    public function evento(string $id = '0'): void
    {
        $cid = $this->colegioId();
        $evento = $this->eventoOAbortar($id);

        $asistentes = Database::todas(
            "SELECT a.*, s.numero, s.nombre_completo AS socio_nombre
             FROM asistencias a LEFT JOIN socios s ON s.id = a.socio_id
             WHERE a.evento_id = ? ORDER BY a.id DESC",
            [(int) $id]);

        $sociosActivos = Database::todas(
            "SELECT id, numero, nombre_completo AS nombre, tipo FROM socios
             WHERE colegio_id = ? AND estatus = 'activo' ORDER BY nombre_completo",
            [$cid]);

        $this->vista('registro/evento', [
            'titulo'        => 'Registro: ' . $evento['nombre'],
            'evento'        => $evento,
            'asistentes'    => $asistentes,
            'sociosActivos' => $sociosActivos,
            'precios'       => $this->preciosDe((int) $id),
            'modulos'       => Database::todas(
                'SELECT m.*, (SELECT COALESCE(SUM(p.puntos), 0) FROM evento_puntos p WHERE p.modulo_id = m.id) AS puntos_dpc
                 FROM evento_modulos m WHERE m.evento_id = ? ORDER BY m.orden, m.id', [(int) $id]),
            'puntosTotales' => $this->puntosTotales((int) $id),
            'modalidades'   => EventosController::modalidadesDe($evento['modalidad']),
        ]);
    }

    /** Alta de asistente (socio o público) con cargo automático. */
    public function agregar(string $id = '0'): void
    {
        $cid = $this->colegioId();
        if (!$this->esPost()) {
            redirigir('registro');
        }
        $evento = $this->eventoOAbortar($id);
        $volver = fn() => redirigir('registro/evento/' . (int) $evento['id']);

        if ($evento['cupo'] !== null) {
            $ocupados = (int) Database::valor('SELECT COUNT(*) FROM asistencias WHERE evento_id = ?', [(int) $id]);
            if ($ocupados >= (int) $evento['cupo']) {
                flash('warning', 'El evento ya alcanzó su cupo.');
                $volver();
            }
        }

        $modalidadesValidas = EventosController::modalidadesDe($evento['modalidad']);
        $modalidad = (string) $this->post('modalidad', '');
        if (!in_array($modalidad, $modalidadesValidas, true)) {
            $modalidad = $modalidadesValidas[0];
        }
        $tipo   = $this->post('tipo') === 'publico' ? 'publico' : 'socio';
        $puntos = $this->puntosTotales((int) $evento['id']);

        $pdo = Database::pdo();
        $pdo->beginTransaction();
        try {
            if ($tipo === 'socio') {
                $socioId = (int) ($this->post('socio_id', '0') ?? 0);
                $socio = Database::una(
                    "SELECT id, nombre_completo, tipo FROM socios WHERE id = ? AND colegio_id = ? AND estatus = 'activo'",
                    [$socioId, $cid]);
                if (!$socio) {
                    throw new \RuntimeException('Seleccione un socio activo.');
                }
                if (Database::una('SELECT id FROM asistencias WHERE evento_id = ? AND socio_id = ?', [(int) $id, $socioId])) {
                    throw new \RuntimeException('El socio ya está registrado en este evento.');
                }

                // Categoría: la elegida por el capturista, o la que corresponde a su tipo
                $categoria = (string) $this->post('categoria', '');
                if (!isset(Catalogos::CATEGORIAS_ASISTENTE[$categoria])) {
                    $categoria = Catalogos::CATEGORIA_POR_TIPO_SOCIO[$socio['tipo']] ?? 'socio';
                }
                $precio = $this->precio((int) $id, $categoria, $modalidad);

                Database::ejecutar(
                    "INSERT INTO asistencias (colegio_id, evento_id, socio_id, tipo, categoria, modalidad, puntos_dpc, creado_por)
                     VALUES (?, ?, ?, 'socio', ?, ?, ?, ?)",
                    [$cid, (int) $id, $socioId, $categoria, $modalidad, $puntos, $this->usuarioId()]);

                if ($precio > 0) {
                    Database::ejecutar(
                        "INSERT INTO cuentas (colegio_id, socio_id, evento_id, tipo, concepto, importe, fecha, creado_por)
                         VALUES (?, ?, ?, 'cargo', ?, ?, CURDATE(), ?)",
                        [$cid, $socioId, (int) $id,
                         'Inscripción: ' . mb_substr($evento['nombre'], 0, 185),
                         $precio, $this->usuarioId()]);
                }
                flash('success', $precio > 0
                    ? 'Socio registrado. Se generó el cargo por ' . dinero($precio) . '.'
                    : 'Socio registrado (sin costo).');
            } else {
                $nombre = $this->post('asistente');
                if (!$nombre) {
                    throw new \RuntimeException('Capture el nombre del asistente.');
                }
                $email = $this->post('email');
                if ($email && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    throw new \RuntimeException('El correo del asistente no es válido.');
                }
                $categoria = (string) $this->post('categoria', '');
                if (!isset(Catalogos::CATEGORIAS_ASISTENTE[$categoria])) {
                    $categoria = 'no_socio';
                }
                Database::ejecutar(
                    "INSERT INTO asistencias (colegio_id, evento_id, asistente, email, tipo, categoria, modalidad, puntos_dpc, creado_por)
                     VALUES (?, ?, ?, ?, 'publico', ?, ?, ?, ?)",
                    [$cid, (int) $id, $nombre, $email, $categoria, $modalidad, $puntos, $this->usuarioId()]);
                flash('success', 'Asistente de público registrado. Cobro por caja: '
                    . dinero($this->precio((int) $id, $categoria, $modalidad)) . '.');
            }
            $pdo->commit();
        } catch (\RuntimeException $e) {
            $pdo->rollBack();
            flash('danger', $e->getMessage());
        }

        $volver();
    }

    /** Quita un asistente y cancela el cargo asociado si sigue vigente. */
    public function quitar(string $id = '0', string $asistenciaId = '0'): void
    {
        $cid = $this->colegioId();
        if (!$this->esPost()) {
            redirigir('registro');
        }
        $asistencia = Database::una(
            'SELECT * FROM asistencias WHERE id = ? AND evento_id = ? AND colegio_id = ?',
            [(int) $asistenciaId, (int) $id, $cid]);
        if ($asistencia) {
            Database::ejecutar('DELETE FROM asistencias WHERE id = ?', [(int) $asistenciaId]);
            if ($asistencia['socio_id']) {
                Database::ejecutar(
                    "UPDATE cuentas SET estatus = 'cancelado'
                     WHERE evento_id = ? AND socio_id = ? AND tipo = 'cargo' AND estatus = 'vigente'",
                    [(int) $id, (int) $asistencia['socio_id']]);
            }
            flash('success', 'Registro eliminado; el cargo del evento fue cancelado.');
        }
        redirigir('registro/evento/' . (int) $id);
    }

    // ------------------------------------------------------------------
    private function eventoOAbortar(string $id): array
    {
        $evento = Database::una('SELECT * FROM eventos WHERE id = ? AND colegio_id = ?', [(int) $id, $this->colegioId()]);
        if (!$evento) {
            flash('warning', 'Evento no encontrado.');
            redirigir('registro');
        }
        return $evento;
    }

    /** Puntos DPC totales del evento (propios + de todos sus módulos). */
    private function puntosTotales(int $eventoId): float
    {
        return (float) Database::valor(
            'SELECT COALESCE(SUM(puntos), 0) FROM evento_puntos WHERE evento_id = ?', [$eventoId]);
    }

    private function precio(int $eventoId, string $categoria, string $modalidad): float
    {
        return (float) Database::valor(
            'SELECT COALESCE(precio, 0) FROM evento_precios WHERE evento_id = ? AND categoria = ? AND modalidad = ?',
            [$eventoId, $categoria, $modalidad]);
    }

    /** Precios [modalidad][categoria] => precio, para pintar el formulario. */
    private function preciosDe(int $eventoId): array
    {
        $mapa = [];
        foreach (Database::todas('SELECT * FROM evento_precios WHERE evento_id = ?', [$eventoId]) as $p) {
            $mapa[$p['modalidad']][$p['categoria']] = $p['precio'];
        }
        return $mapa;
    }
}

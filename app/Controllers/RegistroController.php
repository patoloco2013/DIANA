<?php
declare(strict_types=1);

namespace Diana\Controllers;

use Diana\Core\Catalogos;
use Diana\Core\Controller;
use Diana\Core\Database;

/**
 * Registro de asistentes a eventos, en dos fases:
 *
 *   1. Confirmaciones (evento()): se da de alta al asistente, se genera el
 *      cargo automático según su categoría y modalidad, y aquí se registran
 *      sus pagos. Los puntos DPC todavía NO se otorgan.
 *   2. Asistencia (asistencia()): al pasar lista se marca quién asistió
 *      realmente; solo entonces se otorgan los puntos DPC del evento y se
 *      puede anotar su lugar/mesa y comentarios.
 */
final class RegistroController extends Controller
{
    public const MODULO = 'registro';

    private const FORMAS_PAGO = ['efectivo', 'transferencia', 'tarjeta', 'cheque', 'otro'];

    /** Lista de eventos vigentes para tomar registro. */
    public function index(): void
    {
        $eventos = Database::todas(
            "SELECT e.*,
                    (SELECT COUNT(*) FROM asistencias a WHERE a.evento_id = e.id) AS registrados,
                    (SELECT COUNT(*) FROM asistencias a WHERE a.evento_id = e.id AND a.asistio = 1) AS asistieron,
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

    // ------------------------------------------------------------------
    // Fase 1: Confirmaciones
    // ------------------------------------------------------------------
    /** Panel de confirmaciones de un evento: alta rápida + cargos + pagos. */
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
            'titulo'        => 'Confirmaciones: ' . $evento['nombre'],
            'evento'        => $evento,
            'asistentes'    => $asistentes,
            'sociosActivos' => $sociosActivos,
            'precios'       => $this->preciosDe((int) $id),
            'estadoPago'    => $this->estadoPagoPorSocio((int) $id),
            'modulos'       => Database::todas(
                'SELECT m.*, (SELECT COALESCE(SUM(p.puntos), 0) FROM evento_puntos p WHERE p.modulo_id = m.id) AS puntos_dpc
                 FROM evento_modulos m WHERE m.evento_id = ? ORDER BY m.orden, m.id', [(int) $id]),
            'puntosTotales' => $this->puntosTotales((int) $id),
            'modalidades'   => EventosController::modalidadesDe($evento['modalidad']),
            'formasPago'    => self::FORMAS_PAGO,
        ]);
    }

    /** Alta de asistente (socio o público) con cargo automático. Puntos DPC = 0: se ganan al pasar lista. */
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
        $tipo = $this->post('tipo') === 'publico' ? 'publico' : 'socio';

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
                    "INSERT INTO asistencias (colegio_id, evento_id, socio_id, tipo, categoria, modalidad, creado_por)
                     VALUES (?, ?, ?, 'socio', ?, ?, ?)",
                    [$cid, (int) $id, $socioId, $categoria, $modalidad, $this->usuarioId()]);

                if ($precio > 0) {
                    Database::ejecutar(
                        "INSERT INTO cuentas (colegio_id, socio_id, evento_id, tipo, concepto, importe, fecha, creado_por)
                         VALUES (?, ?, ?, 'cargo', ?, ?, CURDATE(), ?)",
                        [$cid, $socioId, (int) $id,
                         'Inscripción: ' . mb_substr($evento['nombre'], 0, 185),
                         $precio, $this->usuarioId()]);
                }
                flash('success', $precio > 0
                    ? 'Socio confirmado. Se generó el cargo por ' . dinero($precio) . '.'
                    : 'Socio confirmado (sin costo).');
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
                    "INSERT INTO asistencias (colegio_id, evento_id, asistente, email, tipo, categoria, modalidad, creado_por)
                     VALUES (?, ?, ?, ?, 'publico', ?, ?, ?)",
                    [$cid, (int) $id, $nombre, $email, $categoria, $modalidad, $this->usuarioId()]);
                flash('success', 'Asistente de público confirmado. Cobro por caja: '
                    . dinero($this->precio((int) $id, $categoria, $modalidad)) . '.');
            }
            $pdo->commit();
        } catch (\RuntimeException $e) {
            $pdo->rollBack();
            flash('danger', $e->getMessage());
        }

        $volver();
    }

    /** Quita una confirmación y cancela el cargo asociado si sigue vigente. */
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
                     WHERE evento_id = ? AND socio_id = ? AND tipo IN ('cargo', 'pago') AND estatus = 'vigente'",
                    [(int) $id, (int) $asistencia['socio_id']]);
            }
            flash('success', 'Confirmación eliminada; el cargo y los pagos del evento fueron cancelados.');
        }
        redirigir('registro/evento/' . (int) $id);
    }

    /** Registra un pago del socio específicamente para este evento. */
    public function registrarPago(string $id = '0', string $asistenciaId = '0'): void
    {
        $cid = $this->colegioId();
        $evento = $this->eventoOAbortar($id);
        $volver = fn() => redirigir('registro/evento/' . (int) $evento['id']);
        if (!$this->esPost()) {
            $volver();
        }
        $asistencia = Database::una(
            "SELECT * FROM asistencias WHERE id = ? AND evento_id = ? AND colegio_id = ? AND tipo = 'socio'",
            [(int) $asistenciaId, (int) $evento['id'], $cid]);
        if (!$asistencia) {
            flash('warning', 'Confirmación no encontrada.');
            $volver();
        }

        $importe = (float) ($this->post('importe', '0') ?? 0);
        $formaPago = in_array($this->post('forma_pago'), self::FORMAS_PAGO, true) ? $this->post('forma_pago') : 'efectivo';
        if ($importe <= 0) {
            flash('danger', 'Capture un importe mayor a cero.');
            $volver();
        }

        Database::ejecutar(
            "INSERT INTO cuentas (colegio_id, socio_id, evento_id, tipo, concepto, importe, fecha, forma_pago, referencia, creado_por)
             VALUES (?, ?, ?, 'pago', ?, ?, CURDATE(), ?, ?, ?)",
            [$cid, (int) $asistencia['socio_id'], (int) $evento['id'],
             'Pago inscripción: ' . mb_substr($evento['nombre'], 0, 175),
             $importe, $formaPago, $this->post('referencia'), $this->usuarioId()]);
        flash('success', 'Pago de ' . dinero($importe) . ' registrado.');
        $volver();
    }

    // ------------------------------------------------------------------
    // Fase 2: Asistencia (pasar lista)
    // ------------------------------------------------------------------
    /** Lista de confirmados para marcar quién asistió, con lugar y comentarios. */
    public function asistencia(string $id = '0'): void
    {
        $evento = $this->eventoOAbortar($id);

        $asistentes = Database::todas(
            "SELECT a.*, s.numero, s.nombre_completo AS socio_nombre
             FROM asistencias a LEFT JOIN socios s ON s.id = a.socio_id
             WHERE a.evento_id = ? ORDER BY COALESCE(s.nombre_completo, a.asistente)",
            [(int) $id]);

        $this->vista('registro/asistencia', [
            'titulo'        => 'Asistencia: ' . $evento['nombre'],
            'evento'        => $evento,
            'asistentes'    => $asistentes,
            'puntosTotales' => $this->puntosTotales((int) $id),
        ]);
    }

    /** Marca asistio = 1 y otorga los puntos DPC vigentes del evento. */
    public function marcarAsistencia(string $id = '0', string $asistenciaId = '0'): void
    {
        $evento = $this->eventoOAbortar($id);
        if ($this->esPost() && $this->asistenciaOAbortarSuave((int) $evento['id'], (int) $asistenciaId)) {
            Database::ejecutar(
                'UPDATE asistencias SET asistio = 1, fecha_asistio = NOW(), asistio_por = ?, puntos_dpc = ?
                 WHERE id = ?',
                [$this->usuarioId(), $this->puntosTotales((int) $evento['id']), (int) $asistenciaId]);
            flash('success', 'Asistencia registrada.');
        }
        redirigir('registro/asistencia/' . (int) $evento['id']);
    }

    /** Deshace una asistencia marcada por error; los puntos otorgados se retiran. */
    public function quitarAsistencia(string $id = '0', string $asistenciaId = '0'): void
    {
        $evento = $this->eventoOAbortar($id);
        if ($this->esPost() && $this->asistenciaOAbortarSuave((int) $evento['id'], (int) $asistenciaId)) {
            Database::ejecutar(
                'UPDATE asistencias SET asistio = 0, fecha_asistio = NULL, asistio_por = NULL, puntos_dpc = 0
                 WHERE id = ?',
                [(int) $asistenciaId]);
            flash('success', 'Asistencia retirada.');
        }
        redirigir('registro/asistencia/' . (int) $evento['id']);
    }

    /** Guarda el lugar/mesa y los comentarios capturados al pasar lista. */
    public function actualizarAsistencia(string $id = '0', string $asistenciaId = '0'): void
    {
        $evento = $this->eventoOAbortar($id);
        if ($this->esPost() && $this->asistenciaOAbortarSuave((int) $evento['id'], (int) $asistenciaId)) {
            Database::ejecutar(
                'UPDATE asistencias SET lugar = ?, comentarios = ? WHERE id = ?',
                [$this->post('lugar'), $this->post('comentarios'), (int) $asistenciaId]);
            flash('success', 'Datos de asistencia actualizados.');
        }
        redirigir('registro/asistencia/' . (int) $evento['id']);
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

    private function asistenciaOAbortarSuave(int $eventoId, int $asistenciaId): bool
    {
        $existe = Database::una('SELECT id FROM asistencias WHERE id = ? AND evento_id = ? AND colegio_id = ?',
            [$asistenciaId, $eventoId, $this->colegioId()]);
        if (!$existe) {
            flash('warning', 'Confirmación no encontrada.');
        }
        return (bool) $existe;
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

    /**
     * Cargo y cobrado por socio para ESTE evento (cuentas.evento_id = X),
     * sin tocar el saldo general del socio (que sigue siendo la suma de
     * todos sus movimientos, tengan o no evento_id).
     * socio_id => ['cargo' => float, 'cobrado' => float, 'pagado' => bool]
     */
    private function estadoPagoPorSocio(int $eventoId): array
    {
        $mapa = [];
        foreach (Database::todas(
            "SELECT socio_id, tipo, SUM(importe) AS total FROM cuentas
             WHERE evento_id = ? AND estatus = 'vigente' AND socio_id IS NOT NULL
             GROUP BY socio_id, tipo",
            [$eventoId]
        ) as $fila) {
            $mapa[(int) $fila['socio_id']][$fila['tipo']] = (float) $fila['total'];
        }
        $resultado = [];
        foreach ($mapa as $socioId => $montos) {
            $cargo = $montos['cargo'] ?? 0.0;
            $cobrado = $montos['pago'] ?? 0.0;
            $resultado[$socioId] = ['cargo' => $cargo, 'cobrado' => $cobrado, 'pagado' => $cargo <= 0 || $cobrado >= $cargo];
        }
        return $resultado;
    }
}

<?php
declare(strict_types=1);

namespace Diana\Controllers;

use Diana\Core\Controller;
use Diana\Core\Database;

/**
 * Registro de asistentes a eventos.
 * Al registrar un socio se genera automáticamente el cargo en cuentas
 * (sustituye el flujo rsnew.php + cargos manuales del SIE).
 */
final class RegistroController extends Controller
{
    public const MODULO = 'registro';

    /** Lista de eventos vigentes para tomar registro. */
    public function index(): void
    {
        $eventos = Database::todas(
            "SELECT e.*, (SELECT COUNT(*) FROM asistencias a WHERE a.evento_id = e.id) AS registrados
             FROM eventos e
             WHERE e.colegio_id = ? AND e.estatus = 'publicado'
             ORDER BY e.fecha_inicio DESC LIMIT 100",
            [$this->colegioId()]);

        $this->vista('registro/index', ['titulo' => 'Registro de eventos', 'eventos' => $eventos]);
    }

    /** Panel de registro de un evento: asistentes + alta rápida. */
    public function evento(string $id = '0'): void
    {
        $cid = $this->colegioId();
        $evento = Database::una('SELECT * FROM eventos WHERE id = ? AND colegio_id = ?', [(int) $id, $cid]);
        if (!$evento) {
            flash('warning', 'Evento no encontrado.');
            redirigir('registro');
        }

        $asistentes = Database::todas(
            "SELECT a.*, s.numero, s.nombre_completo AS socio_nombre
             FROM asistencias a LEFT JOIN socios s ON s.id = a.socio_id
             WHERE a.evento_id = ? ORDER BY a.id DESC",
            [(int) $id]);

        $sociosActivos = Database::todas(
            "SELECT id, numero, nombre_completo AS nombre FROM socios
             WHERE colegio_id = ? AND estatus = 'activo' ORDER BY nombre_completo",
            [$cid]);

        $this->vista('registro/evento', [
            'titulo' => 'Registro: ' . $evento['nombre'],
            'evento' => $evento,
            'asistentes' => $asistentes,
            'sociosActivos' => $sociosActivos,
        ]);
    }

    /** Alta de asistente (socio o público) con cargo automático. */
    public function agregar(string $id = '0'): void
    {
        $cid = $this->colegioId();
        if (!$this->esPost()) {
            redirigir('registro');
        }
        $evento = Database::una('SELECT * FROM eventos WHERE id = ? AND colegio_id = ?', [(int) $id, $cid]);
        if (!$evento) {
            flash('warning', 'Evento no encontrado.');
            redirigir('registro');
        }

        if ($evento['cupo'] !== null) {
            $ocupados = (int) Database::valor('SELECT COUNT(*) FROM asistencias WHERE evento_id = ?', [(int) $id]);
            if ($ocupados >= (int) $evento['cupo']) {
                flash('warning', 'El evento ya alcanzó su cupo.');
                redirigir('registro/evento/' . (int) $id);
            }
        }

        $tipo = $this->post('tipo') === 'publico' ? 'publico' : 'socio';
        $pdo = Database::pdo();
        $pdo->beginTransaction();
        try {
            if ($tipo === 'socio') {
                $socioId = (int) ($this->post('socio_id', '0') ?? 0);
                $socio = Database::una(
                    "SELECT id, nombre FROM socios WHERE id = ? AND colegio_id = ? AND estatus = 'activo'",
                    [$socioId, $cid]);
                if (!$socio) {
                    throw new \RuntimeException('Seleccione un socio activo.');
                }
                $yaExiste = Database::una(
                    'SELECT id FROM asistencias WHERE evento_id = ? AND socio_id = ?',
                    [(int) $id, $socioId]);
                if ($yaExiste) {
                    throw new \RuntimeException('El socio ya está registrado en este evento.');
                }

                Database::ejecutar(
                    "INSERT INTO asistencias (colegio_id, evento_id, socio_id, tipo, puntos_epc, creado_por)
                     VALUES (?, ?, ?, 'socio', ?, ?)",
                    [$cid, (int) $id, $socioId, $evento['puntos_epc'], $this->usuarioId()]);

                // Cargo automático al estado de cuenta del socio
                if ((float) $evento['precio_socio'] > 0) {
                    Database::ejecutar(
                        "INSERT INTO cuentas (colegio_id, socio_id, evento_id, tipo, concepto, importe, fecha, creado_por)
                         VALUES (?, ?, ?, 'cargo', ?, ?, CURDATE(), ?)",
                        [$cid, $socioId, (int) $id,
                         'Inscripción: ' . mb_substr($evento['nombre'], 0, 185),
                         $evento['precio_socio'], $this->usuarioId()]);
                }
                flash('success', 'Socio registrado en el evento.');
            } else {
                $nombre = $this->post('asistente');
                if (!$nombre) {
                    throw new \RuntimeException('Capture el nombre del asistente.');
                }
                Database::ejecutar(
                    "INSERT INTO asistencias (colegio_id, evento_id, asistente, tipo, puntos_epc, creado_por)
                     VALUES (?, ?, ?, 'publico', ?, ?)",
                    [$cid, (int) $id, $nombre, $evento['puntos_epc'], $this->usuarioId()]);
                flash('success', 'Asistente de público registrado.');
            }
            $pdo->commit();
        } catch (\RuntimeException $e) {
            $pdo->rollBack();
            flash('danger', $e->getMessage());
        }

        redirigir('registro/evento/' . (int) $id);
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
}

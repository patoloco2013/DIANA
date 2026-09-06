<?php
declare(strict_types=1);

namespace Diana\Controllers;

use Diana\Core\Controller;
use Diana\Core\Database;

/**
 * Reportes esenciales del colegio activo.
 * Cubre lo que realmente se usaba del menú "Reportes SIE":
 * saldos, morosidad, cobranza y resultados por evento.
 */
final class ReportesController extends Controller
{
    public const MODULO = 'reportes';

    public function index(): void
    {
        $this->vista('reportes/index', ['titulo' => 'Reportes']);
    }

    /** Saldos de todos los socios (cartera). */
    public function saldos(): void
    {
        $filas = Database::todas(
            "SELECT s.numero, s.nombre, s.estatus,
                    COALESCE(SUM(CASE WHEN c.tipo = 'cargo' THEN c.importe END), 0) AS cargos,
                    COALESCE(SUM(CASE WHEN c.tipo = 'pago'  THEN c.importe END), 0) AS pagos,
                    COALESCE(SUM(CASE WHEN c.tipo = 'cargo' THEN c.importe ELSE -c.importe END), 0) AS saldo
             FROM socios s
             LEFT JOIN cuentas c ON c.socio_id = s.id AND c.estatus = 'vigente'
             WHERE s.colegio_id = ?
             GROUP BY s.id, s.numero, s.nombre, s.estatus
             ORDER BY saldo DESC",
            [$this->colegioId()]);

        $this->vista('reportes/saldos', ['titulo' => 'Saldos de socios', 'filas' => $filas]);
    }

    /** Morosidad: cargos vigentes ya vencidos y sin cubrir. */
    public function morosidad(): void
    {
        $filas = Database::todas(
            "SELECT s.numero, s.nombre, c.concepto, c.importe, c.fecha, c.fecha_vencimiento,
                    DATEDIFF(CURDATE(), c.fecha_vencimiento) AS dias_vencido
             FROM cuentas c
             JOIN socios s ON s.id = c.socio_id
             WHERE c.colegio_id = ? AND c.tipo = 'cargo' AND c.estatus = 'vigente'
               AND c.fecha_vencimiento IS NOT NULL AND c.fecha_vencimiento < CURDATE()
             ORDER BY dias_vencido DESC",
            [$this->colegioId()]);

        $this->vista('reportes/morosidad', ['titulo' => 'Morosidad', 'filas' => $filas]);
    }

    /** Cobranza: pagos recibidos en un rango de fechas. */
    public function cobranza(): void
    {
        $desde = $this->fechaGet('desde', date('Y-m-01'));
        $hasta = $this->fechaGet('hasta', date('Y-m-d'));

        $filas = Database::todas(
            "SELECT c.fecha, c.concepto, c.referencia, c.forma_pago, c.importe, s.numero, s.nombre
             FROM cuentas c JOIN socios s ON s.id = c.socio_id
             WHERE c.colegio_id = ? AND c.tipo = 'pago' AND c.estatus = 'vigente'
               AND c.fecha BETWEEN ? AND ?
             ORDER BY c.fecha, c.id",
            [$this->colegioId(), $desde, $hasta]);

        $total = array_sum(array_map(fn($f) => (float) $f['importe'], $filas));

        $this->vista('reportes/cobranza', [
            'titulo' => 'Cobranza', 'filas' => $filas,
            'desde' => $desde, 'hasta' => $hasta, 'total' => $total,
        ]);
    }

    /** Resultados por evento: asistencia e ingresos. */
    public function eventos(): void
    {
        $filas = Database::todas(
            "SELECT e.nombre, e.fecha_inicio, e.estatus, e.puntos_epc,
                    (SELECT COUNT(*) FROM asistencias a WHERE a.evento_id = e.id AND a.tipo = 'socio')   AS socios,
                    (SELECT COUNT(*) FROM asistencias a WHERE a.evento_id = e.id AND a.tipo = 'publico') AS publico,
                    COALESCE((SELECT SUM(c.importe) FROM cuentas c
                              WHERE c.evento_id = e.id AND c.tipo = 'cargo' AND c.estatus = 'vigente'), 0) AS facturado,
                    COALESCE((SELECT SUM(c.importe) FROM cuentas c
                              WHERE c.evento_id = e.id AND c.tipo = 'pago' AND c.estatus = 'vigente'), 0) AS cobrado
             FROM eventos e
             WHERE e.colegio_id = ?
             ORDER BY e.fecha_inicio DESC LIMIT 200",
            [$this->colegioId()]);

        $this->vista('reportes/eventos', ['titulo' => 'Reporte de eventos', 'filas' => $filas]);
    }

    /** Fecha YYYY-MM-DD válida desde GET, o el valor por defecto. */
    private function fechaGet(string $campo, string $porDefecto): string
    {
        $v = (string) ($_GET[$campo] ?? '');
        return preg_match('/^\d{4}-\d{2}-\d{2}$/', $v) ? $v : $porDefecto;
    }
}

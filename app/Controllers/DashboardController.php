<?php
declare(strict_types=1);

namespace Diana\Controllers;

use Diana\Core\Controller;
use Diana\Core\Database;

/** Tablero: indicadores rápidos del colegio activo. */
final class DashboardController extends Controller
{
    public const MODULO = 'dashboard';

    public function index(): void
    {
        $cid = $this->colegioId();

        $kpis = [
            'socios_activos' => Database::valor(
                "SELECT COUNT(*) FROM socios WHERE colegio_id = ? AND estatus = 'activo'", [$cid]),
            'eventos_proximos' => Database::valor(
                "SELECT COUNT(*) FROM eventos
                 WHERE colegio_id = ? AND estatus = 'publicado' AND fecha_inicio >= CURDATE()", [$cid]),
            'cartera' => Database::valor(
                "SELECT COALESCE(SUM(CASE WHEN tipo = 'cargo' THEN importe ELSE -importe END), 0)
                 FROM cuentas WHERE colegio_id = ? AND estatus = 'vigente'", [$cid]),
            'cobrado_mes' => Database::valor(
                "SELECT COALESCE(SUM(importe), 0) FROM cuentas
                 WHERE colegio_id = ? AND tipo = 'pago' AND estatus = 'vigente'
                   AND fecha >= DATE_FORMAT(CURDATE(), '%Y-%m-01')", [$cid]),
        ];

        $proximosEventos = Database::todas(
            "SELECT id, nombre, fecha_inicio, sede, puntos_epc,
                    (SELECT COUNT(*) FROM asistencias a WHERE a.evento_id = eventos.id) AS registrados
             FROM eventos
             WHERE colegio_id = ? AND estatus = 'publicado' AND fecha_inicio >= CURDATE()
             ORDER BY fecha_inicio LIMIT 8", [$cid]);

        $ultimosPagos = Database::todas(
            "SELECT c.fecha, c.importe, c.concepto, s.nombre_completo AS socio
             FROM cuentas c JOIN socios s ON s.id = c.socio_id
             WHERE c.colegio_id = ? AND c.tipo = 'pago' AND c.estatus = 'vigente'
             ORDER BY c.id DESC LIMIT 8", [$cid]);

        $this->vista('dashboard/index', [
            'titulo' => 'Inicio',
            'kpis' => $kpis,
            'proximosEventos' => $proximosEventos,
            'ultimosPagos' => $ultimosPagos,
        ]);
    }
}

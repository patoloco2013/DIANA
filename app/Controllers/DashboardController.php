<?php
declare(strict_types=1);

namespace Diana\Controllers;

use Diana\Core\Controller;
use Diana\Core\Database;

/** Tablero: enfocado en los próximos eventos del colegio activo. */
final class DashboardController extends Controller
{
    public const MODULO = 'dashboard';

    public function index(): void
    {
        $cid = $this->colegioId();

        $proximosEventos = Database::todas(
            "SELECT e.id, e.nombre, e.fecha_inicio, e.hora_inicio, e.sede, e.modalidad, e.cupo,
                    (SELECT COALESCE(SUM(p.puntos), 0) FROM evento_puntos p WHERE p.evento_id = e.id) AS puntos_dpc,
                    (SELECT COUNT(*) FROM asistencias a WHERE a.evento_id = e.id) AS confirmados,
                    (SELECT COUNT(*) FROM asistencias a WHERE a.evento_id = e.id AND a.asistio = 1) AS asistieron,
                    (SELECT i.id FROM evento_imagenes i WHERE i.evento_id = e.id
                     ORDER BY i.principal DESC, i.orden, i.id LIMIT 1) AS imagen_id
             FROM eventos e
             WHERE e.colegio_id = ? AND e.estatus = 'publicado' AND e.fecha_inicio >= CURDATE()
             ORDER BY e.fecha_inicio, e.hora_inicio LIMIT 9",
            [$cid]);

        $kpis = [
            'socios_activos' => Database::valor(
                "SELECT COUNT(*) FROM socios WHERE colegio_id = ? AND estatus = 'activo'", [$cid]),
            'eventos_proximos' => Database::valor(
                "SELECT COUNT(*) FROM eventos
                 WHERE colegio_id = ? AND estatus = 'publicado' AND fecha_inicio >= CURDATE()", [$cid]),
            'confirmados_proximos' => Database::valor(
                "SELECT COUNT(*) FROM asistencias a JOIN eventos e ON e.id = a.evento_id
                 WHERE e.colegio_id = ? AND e.estatus = 'publicado' AND e.fecha_inicio >= CURDATE()", [$cid]),
        ];

        $ultimosPagos = Database::todas(
            "SELECT c.fecha, c.importe, c.concepto, s.nombre_completo AS socio
             FROM cuentas c JOIN socios s ON s.id = c.socio_id
             WHERE c.colegio_id = ? AND c.tipo = 'pago' AND c.estatus = 'vigente'
             ORDER BY c.id DESC LIMIT 6", [$cid]);

        $this->vista('dashboard/index', [
            'titulo' => 'Inicio',
            'kpis' => $kpis,
            'proximosEventos' => $proximosEventos,
            'ultimosPagos' => $ultimosPagos,
        ]);
    }
}

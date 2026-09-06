<?php
declare(strict_types=1);

namespace Diana\Controllers;

use Diana\Core\Controller;
use Diana\Core\Database;

/**
 * Cuentas por socio: estado de cuenta, cargos y pagos.
 * El saldo siempre se calcula (SUM cargos - SUM pagos); nada de saldos
 * redundantes desincronizados como en el SIE.
 */
final class CuentasController extends Controller
{
    public const MODULO = 'cuentas';

    private const FORMAS_PAGO = ['efectivo', 'transferencia', 'tarjeta', 'cheque', 'otro'];

    /** Resumen: socios con saldo, para entrar a su estado de cuenta. */
    public function index(): void
    {
        $cid = $this->colegioId();
        $q = trim((string) ($_GET['q'] ?? ''));

        $sql = "SELECT s.id, s.numero, s.nombre_completo AS nombre, s.estatus,
                       COALESCE(SUM(CASE WHEN c.tipo = 'cargo' THEN c.importe ELSE -c.importe END), 0) AS saldo
                FROM socios s
                LEFT JOIN cuentas c ON c.socio_id = s.id AND c.estatus = 'vigente'
                WHERE s.colegio_id = ?";
        $params = [$cid];
        if ($q !== '') {
            $sql .= ' AND (s.nombre_completo LIKE ? OR s.numero LIKE ?)';
            $like = "%{$q}%";
            array_push($params, $like, $like);
        }
        $sql .= ' GROUP BY s.id, s.numero, s.nombre_completo, s.estatus
                  HAVING saldo <> 0 OR ? <> \'\'
                  ORDER BY saldo DESC LIMIT 300';
        $params[] = $q;

        $this->vista('cuentas/index', [
            'titulo' => 'Cuentas',
            'filas' => Database::todas($sql, $params),
            'q' => $q,
        ]);
    }

    /** Estado de cuenta de un socio + alta de cargos y pagos. */
    public function socio(string $id = '0'): void
    {
        $cid = $this->colegioId();
        $socio = Database::una('SELECT * FROM socios WHERE id = ? AND colegio_id = ?', [(int) $id, $cid]);
        if (!$socio) {
            flash('warning', 'Socio no encontrado.');
            redirigir('cuentas');
        }

        $movimientos = Database::todas(
            'SELECT c.*, e.nombre AS evento_nombre
             FROM cuentas c LEFT JOIN eventos e ON e.id = c.evento_id
             WHERE c.socio_id = ? ORDER BY c.fecha DESC, c.id DESC LIMIT 500',
            [(int) $id]);

        $saldo = (float) Database::valor(
            "SELECT COALESCE(SUM(CASE WHEN tipo = 'cargo' THEN importe ELSE -importe END), 0)
             FROM cuentas WHERE socio_id = ? AND estatus = 'vigente'",
            [(int) $id]);

        $this->vista('cuentas/socio', [
            'titulo' => 'Estado de cuenta',
            'socio' => $socio,
            'movimientos' => $movimientos,
            'saldo' => $saldo,
            'formasPago' => self::FORMAS_PAGO,
        ]);
    }

    /** Alta de un movimiento (cargo o pago) al socio. */
    public function movimiento(string $id = '0'): void
    {
        $cid = $this->colegioId();
        if (!$this->esPost()) {
            redirigir('cuentas');
        }
        $socio = Database::una('SELECT id FROM socios WHERE id = ? AND colegio_id = ?', [(int) $id, $cid]);
        if (!$socio) {
            flash('warning', 'Socio no encontrado.');
            redirigir('cuentas');
        }

        $tipo     = $this->post('tipo') === 'pago' ? 'pago' : 'cargo';
        $concepto = $this->post('concepto');
        $importe  = (float) ($this->post('importe', '0') ?? 0);
        $fecha    = $this->post('fecha') ?? date('Y-m-d');
        $formaPago = in_array($this->post('forma_pago'), self::FORMAS_PAGO, true)
            ? $this->post('forma_pago') : null;

        if (!$concepto || $importe <= 0) {
            flash('danger', 'Capture concepto e importe mayor a cero.');
        } else {
            Database::ejecutar(
                'INSERT INTO cuentas (colegio_id, socio_id, tipo, concepto, referencia, importe, fecha,
                                      fecha_vencimiento, forma_pago, observaciones, creado_por)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
                [$cid, (int) $id, $tipo, $concepto, $this->post('referencia'), $importe, $fecha,
                 $tipo === 'cargo' ? $this->post('fecha_vencimiento') : null,
                 $tipo === 'pago' ? $formaPago : null,
                 $this->post('observaciones'), $this->usuarioId()]);
            flash('success', $tipo === 'pago' ? 'Pago registrado.' : 'Cargo registrado.');
        }
        redirigir('cuentas/socio/' . (int) $id);
    }

    /** Cancela un movimiento (nunca se borra: queda rastro auditable). */
    public function cancelar(string $id = '0', string $movId = '0'): void
    {
        if ($this->esPost()) {
            $filas = Database::ejecutar(
                "UPDATE cuentas SET estatus = 'cancelado'
                 WHERE id = ? AND socio_id = ? AND colegio_id = ? AND estatus = 'vigente'",
                [(int) $movId, (int) $id, $this->colegioId()]);
            flash($filas ? 'success' : 'warning', $filas ? 'Movimiento cancelado.' : 'No se pudo cancelar.');
        }
        redirigir('cuentas/socio/' . (int) $id);
    }
}

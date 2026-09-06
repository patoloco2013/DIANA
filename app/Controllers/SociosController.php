<?php
declare(strict_types=1);

namespace Diana\Controllers;

use Diana\Core\Controller;
use Diana\Core\Database;

/** Padrón de socios del colegio activo: alta, edición, baja y búsqueda. */
final class SociosController extends Controller
{
    public const MODULO = 'socios';

    public function index(): void
    {
        $cid = $this->colegioId();
        $q = trim((string) ($_GET['q'] ?? ''));

        $sql = "SELECT s.*,
                       COALESCE((SELECT SUM(CASE WHEN c.tipo = 'cargo' THEN c.importe ELSE -c.importe END)
                                 FROM cuentas c WHERE c.socio_id = s.id AND c.estatus = 'vigente'), 0) AS saldo
                FROM socios s WHERE s.colegio_id = ?";
        $params = [$cid];
        if ($q !== '') {
            $sql .= ' AND (s.nombre LIKE ? OR s.numero LIKE ? OR s.rfc LIKE ? OR s.email LIKE ?)';
            $like = "%{$q}%";
            array_push($params, $like, $like, $like, $like);
        }
        $sql .= ' ORDER BY s.nombre LIMIT 300';

        $this->vista('socios/index', [
            'titulo' => 'Socios',
            'socios' => Database::todas($sql, $params),
            'q' => $q,
        ]);
    }

    public function crear(): void
    {
        $this->formulario(null);
    }

    public function editar(string $id = '0'): void
    {
        $socio = Database::una('SELECT * FROM socios WHERE id = ? AND colegio_id = ?',
            [(int) $id, $this->colegioId()]);
        if (!$socio) {
            flash('warning', 'Socio no encontrado.');
            redirigir('socios');
        }
        $this->formulario($socio);
    }

    private function formulario(?array $socio): void
    {
        $cid = $this->colegioId();

        if ($this->esPost()) {
            $datos = [
                'numero'   => $this->post('numero'),
                'titulo'   => $this->post('titulo'),
                'nombre'   => $this->post('nombre'),
                'rfc'      => strtoupper((string) $this->post('rfc', '')) ?: null,
                'email'    => $this->post('email'),
                'telefono' => $this->post('telefono'),
                'estatus'  => in_array($this->post('estatus'), ['activo', 'suspendido', 'baja'], true)
                                ? $this->post('estatus') : 'activo',
                'observaciones' => $this->post('observaciones'),
            ];

            $error = null;
            if (!$datos['numero'] || !$datos['nombre']) {
                $error = 'Número y nombre son obligatorios.';
            } elseif ($datos['email'] && !filter_var($datos['email'], FILTER_VALIDATE_EMAIL)) {
                $error = 'El correo no es válido.';
            } else {
                $duplicado = Database::una(
                    'SELECT id FROM socios WHERE colegio_id = ? AND numero = ? AND id <> ?',
                    [$cid, $datos['numero'], (int) ($socio['id'] ?? 0)]);
                if ($duplicado) {
                    $error = "El número de socio {$datos['numero']} ya existe en este colegio.";
                }
            }

            if ($error === null) {
                if ($socio) {
                    Database::ejecutar(
                        'UPDATE socios SET numero=?, titulo=?, nombre=?, rfc=?, email=?, telefono=?, estatus=?, observaciones=?
                         WHERE id = ? AND colegio_id = ?',
                        [...array_values($datos), (int) $socio['id'], $cid]);
                    flash('success', 'Socio actualizado.');
                } else {
                    Database::ejecutar(
                        'INSERT INTO socios (colegio_id, numero, titulo, nombre, rfc, email, telefono, estatus, observaciones)
                         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)',
                        [$cid, ...array_values($datos)]);
                    flash('success', 'Socio registrado.');
                }
                redirigir('socios');
            }

            flash('danger', $error);
            $socio = array_merge($socio ?? [], $datos);
        }

        $this->vista('socios/formulario', [
            'titulo' => $socio && isset($socio['id']) ? 'Editar socio' : 'Nuevo socio',
            'socio' => $socio,
        ]);
    }

    /** Baja lógica: no se borran registros con historial de cuentas. */
    public function baja(string $id = '0'): void
    {
        if ($this->esPost()) {
            Database::ejecutar(
                "UPDATE socios SET estatus = 'baja' WHERE id = ? AND colegio_id = ?",
                [(int) $id, $this->colegioId()]);
            flash('success', 'Socio dado de baja.');
        }
        redirigir('socios');
    }
}

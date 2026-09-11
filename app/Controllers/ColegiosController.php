<?php
declare(strict_types=1);

namespace Diana\Controllers;

use Diana\Core\Auth;
use Diana\Core\Controller;
use Diana\Core\Database;

/**
 * Configuración de colegios (tenants).
 * Sustituye el con2.php hardcodeado: cada colegio se da de alta aquí
 * con su clave, colores y datos de contacto. Solo superadmin.
 */
final class ColegiosController extends Controller
{
    public const MODULO = 'colegios';

    private function soloGlobal(): void
    {
        if (!Auth::esGlobal()) {
            flash('warning', 'Solo el administrador global gestiona colegios.');
            redirigir('dashboard');
        }
    }

    public function index(): void
    {
        $this->soloGlobal();
        $colegios = Database::todas(
            'SELECT c.*,
                    (SELECT COUNT(*) FROM socios s   WHERE s.colegio_id = c.id) AS socios,
                    (SELECT COUNT(*) FROM usuarios u WHERE u.colegio_id = c.id) AS usuarios
             FROM colegios c ORDER BY c.nombre_corto');
        $this->vista('colegios/index', ['titulo' => 'Colegios', 'colegios' => $colegios]);
    }

    public function crear(): void
    {
        $this->soloGlobal();
        $this->formulario(null);
    }

    public function editar(string $id = '0'): void
    {
        $this->soloGlobal();
        $colegio = Database::una('SELECT * FROM colegios WHERE id = ?', [(int) $id]);
        if (!$colegio) {
            flash('warning', 'Colegio no encontrado.');
            redirigir('colegios');
        }
        $this->formulario($colegio);
    }

    private function formulario(?array $colegio): void
    {
        if ($this->esPost()) {
            $datos = [
                'clave'          => strtoupper((string) $this->post('clave', '')),
                'nombre'         => $this->post('nombre'),
                'nombre_corto'   => $this->post('nombre_corto'),
                'ciudad'         => $this->post('ciudad'),
                'email_contacto' => $this->post('email_contacto'),
                'telefono'       => $this->post('telefono'),
                'color_primario' => preg_match('/^#[0-9a-fA-F]{6}$/', (string) $this->post('color_primario', ''))
                                      ? $this->post('color_primario') : '#1f0512',
                'logo_url'       => $this->post('logo_url'),
                'activo'         => $this->post('activo') === '1' ? 1 : 0,
            ];

            $error = null;
            if (!$datos['clave'] || !$datos['nombre'] || !$datos['nombre_corto']) {
                $error = 'Clave, nombre y nombre corto son obligatorios.';
            } elseif (Database::una('SELECT id FROM colegios WHERE clave = ? AND id <> ?',
                    [$datos['clave'], (int) ($colegio['id'] ?? 0)])) {
                $error = "La clave {$datos['clave']} ya está en uso.";
            }

            if ($error === null) {
                if ($colegio) {
                    Database::ejecutar(
                        'UPDATE colegios SET clave=?, nombre=?, nombre_corto=?, ciudad=?, email_contacto=?,
                                telefono=?, color_primario=?, logo_url=?, activo=? WHERE id = ?',
                        [...array_values($datos), (int) $colegio['id']]);
                    flash('success', 'Colegio actualizado.');
                } else {
                    Database::ejecutar(
                        'INSERT INTO colegios (clave, nombre, nombre_corto, ciudad, email_contacto, telefono, color_primario, logo_url, activo)
                         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)',
                        array_values($datos));
                    DisciplinasController::sembrar(Database::ultimoId());
                    flash('success', 'Colegio dado de alta con su catálogo inicial de disciplinas DPC.');
                }
                redirigir('colegios');
            }

            flash('danger', $error);
            $colegio = array_merge($colegio ?? [], $datos);
        }

        $this->vista('colegios/formulario', [
            'titulo' => $colegio && isset($colegio['id']) ? 'Editar colegio' : 'Nuevo colegio',
            'colegio' => $colegio,
        ]);
    }
}

<?php
declare(strict_types=1);

namespace Diana\Controllers;

use Diana\Core\Auth;
use Diana\Core\Controller;
use Diana\Core\Database;

/**
 * Usuarios del sistema (staff) y su rol.
 * Las contraseñas SIEMPRE se guardan con password_hash;
 * nunca se muestran ni se envían por correo.
 */
final class UsuariosController extends Controller
{
    public const MODULO = 'usuarios';

    public function index(): void
    {
        $sql = 'SELECT u.*, r.nombre AS rol_nombre, c.nombre_corto AS colegio_nombre
                FROM usuarios u
                JOIN roles r ON r.id = u.rol_id
                LEFT JOIN colegios c ON c.id = u.colegio_id';
        $params = [];
        if (!Auth::esGlobal()) {
            // Un admin de colegio solo ve usuarios de su colegio
            $sql .= ' WHERE u.colegio_id = ?';
            $params[] = $this->colegioId();
        }
        $sql .= ' ORDER BY u.nombre';

        $this->vista('usuarios/index', [
            'titulo' => 'Usuarios',
            'usuarios' => Database::todas($sql, $params),
        ]);
    }

    public function crear(): void
    {
        $this->formulario(null);
    }

    public function editar(string $id = '0'): void
    {
        $u = Database::una('SELECT * FROM usuarios WHERE id = ?', [(int) $id]);
        if (!$u || (!Auth::esGlobal() && (int) $u['colegio_id'] !== $this->colegioId())) {
            flash('warning', 'Usuario no encontrado.');
            redirigir('usuarios');
        }
        $this->formulario($u);
    }

    private function formulario(?array $u): void
    {
        $roles = Database::todas('SELECT id, nombre FROM roles ORDER BY id');
        $colegios = Auth::esGlobal()
            ? Database::todas('SELECT id, nombre_corto FROM colegios WHERE activo = 1 ORDER BY nombre_corto')
            : [];

        if ($this->esPost()) {
            $usuarioLogin = $this->post('usuario');
            $nombre   = $this->post('nombre');
            $email    = $this->post('email');
            $rolId    = (int) ($this->post('rol_id', '0') ?? 0);
            $password = (string) ($_POST['password'] ?? '');
            $activo   = $this->post('activo') === '1' ? 1 : 0;

            // Colegio: superadmin elige (vacío = global); los demás quedan en el suyo
            $colegioId = Auth::esGlobal()
                ? (($v = $this->post('colegio_id')) !== null ? (int) $v : null)
                : $this->colegioId();

            $error = null;
            if (!$usuarioLogin || !$nombre || !$rolId) {
                $error = 'Usuario, nombre y rol son obligatorios.';
            } elseif (!Database::una('SELECT id FROM roles WHERE id = ?', [$rolId])) {
                $error = 'Rol no válido.';
            } elseif (!$u && strlen($password) < 10) {
                $error = 'La contraseña debe tener al menos 10 caracteres.';
            } elseif ($u && $password !== '' && strlen($password) < 10) {
                $error = 'La nueva contraseña debe tener al menos 10 caracteres.';
            } elseif (Database::una('SELECT id FROM usuarios WHERE usuario = ? AND id <> ?',
                    [$usuarioLogin, (int) ($u['id'] ?? 0)])) {
                $error = "El usuario {$usuarioLogin} ya existe.";
            }

            if ($error === null) {
                if ($u) {
                    Database::ejecutar(
                        'UPDATE usuarios SET colegio_id=?, rol_id=?, usuario=?, nombre=?, email=?, activo=? WHERE id = ?',
                        [$colegioId, $rolId, $usuarioLogin, $nombre, $email, $activo, (int) $u['id']]);
                    if ($password !== '') {
                        Database::ejecutar('UPDATE usuarios SET password_hash = ? WHERE id = ?',
                            [password_hash($password, PASSWORD_DEFAULT), (int) $u['id']]);
                    }
                    flash('success', 'Usuario actualizado.');
                } else {
                    Database::ejecutar(
                        'INSERT INTO usuarios (colegio_id, rol_id, usuario, password_hash, nombre, email, activo)
                         VALUES (?, ?, ?, ?, ?, ?, ?)',
                        [$colegioId, $rolId, $usuarioLogin,
                         password_hash($password, PASSWORD_DEFAULT), $nombre, $email, $activo]);
                    flash('success', 'Usuario creado.');
                }
                redirigir('usuarios');
            }

            flash('danger', $error);
            $u = array_merge($u ?? [], [
                'usuario' => $usuarioLogin, 'nombre' => $nombre, 'email' => $email,
                'rol_id' => $rolId, 'colegio_id' => $colegioId, 'activo' => $activo,
            ]);
        }

        $this->vista('usuarios/formulario', [
            'titulo' => $u && isset($u['id']) ? 'Editar usuario' : 'Nuevo usuario',
            'u' => $u,
            'roles' => $roles,
            'colegios' => $colegios,
        ]);
    }

    /** Desactiva la cuenta (no se borra: conserva la bitácora). */
    public function desactivar(string $id = '0'): void
    {
        if ($this->esPost()) {
            if ((int) $id === $this->usuarioId()) {
                flash('warning', 'No puede desactivar su propia cuenta.');
            } else {
                $sql = 'UPDATE usuarios SET activo = 0 WHERE id = ?';
                $params = [(int) $id];
                if (!Auth::esGlobal()) {
                    $sql .= ' AND colegio_id = ?';
                    $params[] = $this->colegioId();
                }
                Database::ejecutar($sql, $params);
                flash('success', 'Usuario desactivado.');
            }
        }
        redirigir('usuarios');
    }
}

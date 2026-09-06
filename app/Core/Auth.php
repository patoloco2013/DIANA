<?php
declare(strict_types=1);

namespace Diana\Core;

/**
 * Autenticación y permisos basados en sesión del servidor.
 * Sustituye el esquema del SIE de md5 + credenciales guardadas en cookies.
 */
final class Auth
{
    /** Intenta iniciar sesión. Devuelve null si ok, o mensaje de error. */
    public static function entrar(string $usuario, string $password, string $ip): ?string
    {
        $usuario = trim($usuario);
        if ($usuario === '' || $password === '') {
            return 'Capture usuario y contraseña.';
        }

        // Bloqueo temporal por intentos fallidos (fuerza bruta)
        $max     = (int) cfg('seguridad.max_intentos_login', 5);
        $minutos = (int) cfg('seguridad.bloqueo_minutos', 15);
        $fallidos = (int) Database::valor(
            'SELECT COUNT(*) FROM login_intentos
             WHERE usuario = ? AND exitoso = 0 AND creado_en > (NOW() - INTERVAL ? MINUTE)',
            [$usuario, $minutos]
        );
        if ($fallidos >= $max) {
            return "Cuenta bloqueada temporalmente por intentos fallidos. Espere {$minutos} minutos.";
        }

        $fila = Database::una(
            'SELECT u.*, r.nombre AS rol_nombre, r.permisos AS rol_permisos,
                    c.nombre_corto AS colegio_nombre
             FROM usuarios u
             JOIN roles r ON r.id = u.rol_id
             LEFT JOIN colegios c ON c.id = u.colegio_id
             WHERE u.usuario = ? LIMIT 1',
            [$usuario]
        );

        $ok = $fila
            && (int) $fila['activo'] === 1
            && password_verify($password, $fila['password_hash']);

        Database::ejecutar(
            'INSERT INTO login_intentos (usuario, ip, exitoso) VALUES (?, ?, ?)',
            [$usuario, $ip, $ok ? 1 : 0]
        );

        if (!$ok) {
            return 'Usuario o contraseña incorrectos.';
        }

        // Rehash transparente si cambió el algoritmo por defecto
        if (password_needs_rehash($fila['password_hash'], PASSWORD_DEFAULT)) {
            Database::ejecutar('UPDATE usuarios SET password_hash = ? WHERE id = ?',
                [password_hash($password, PASSWORD_DEFAULT), $fila['id']]);
        }

        session_regenerate_id(true); // evita fijación de sesión

        $_SESSION['usuario'] = [
            'id'         => (int) $fila['id'],
            'usuario'    => $fila['usuario'],
            'nombre'     => $fila['nombre'],
            'rol'        => $fila['rol_nombre'],
            'permisos'   => json_decode($fila['rol_permisos'], true) ?: [],
            'colegio_id' => $fila['colegio_id'] !== null ? (int) $fila['colegio_id'] : null,
        ];

        // Colegio activo: el propio, o el primero disponible para superadmin
        $colegioId = $_SESSION['usuario']['colegio_id']
            ?? (int) Database::valor('SELECT id FROM colegios WHERE activo = 1 ORDER BY id LIMIT 1');
        self::cambiarColegio((int) $colegioId);

        Database::ejecutar('UPDATE usuarios SET ultimo_acceso = NOW() WHERE id = ?', [$fila['id']]);
        return null;
    }

    public static function salir(): void
    {
        session_unset();
        session_destroy();
    }

    public static function usuario(): ?array
    {
        return $_SESSION['usuario'] ?? null;
    }

    public static function verificado(): bool
    {
        return isset($_SESSION['usuario']);
    }

    /** ¿El usuario tiene acceso al módulo? Sustituye permisos(k1..kN). */
    public static function puede(string $modulo): bool
    {
        $permisos = $_SESSION['usuario']['permisos'] ?? [];
        return in_array('*', $permisos, true) || in_array($modulo, $permisos, true);
    }

    /** ¿Puede administrar todos los colegios? (colegio_id NULL) */
    public static function esGlobal(): bool
    {
        return self::verificado() && $_SESSION['usuario']['colegio_id'] === null;
    }

    /** Colegio activo de la sesión (todas las consultas filtran por él). */
    public static function colegioId(): int
    {
        return (int) ($_SESSION['colegio']['id'] ?? 0);
    }

    public static function colegio(): array
    {
        return $_SESSION['colegio'] ?? [];
    }

    /** Cambia el colegio activo (solo superadmin puede elegir otro). */
    public static function cambiarColegio(int $colegioId): bool
    {
        if (!self::esGlobal() && $_SESSION['usuario']['colegio_id'] !== $colegioId) {
            return false;
        }
        $colegio = Database::una('SELECT * FROM colegios WHERE id = ? AND activo = 1', [$colegioId]);
        if (!$colegio) {
            return false;
        }
        $_SESSION['colegio'] = $colegio;
        return true;
    }
}

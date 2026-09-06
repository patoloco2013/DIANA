<?php
declare(strict_types=1);

namespace Diana\Core;

/**
 * Protección CSRF: token por sesión, obligatorio en todo POST.
 * El SIE no tenía ninguna protección contra CSRF.
 */
final class Csrf
{
    public static function token(): string
    {
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }

    /** Campo oculto listo para insertar en formularios. */
    public static function campo(): string
    {
        return '<input type="hidden" name="_csrf" value="' . e(self::token()) . '">';
    }

    public static function validar(): bool
    {
        $recibido = $_POST['_csrf'] ?? '';
        return is_string($recibido)
            && $recibido !== ''
            && hash_equals($_SESSION['csrf_token'] ?? '', $recibido);
    }
}

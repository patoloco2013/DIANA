<?php
declare(strict_types=1);

namespace Diana\Core;

/**
 * Render de vistas PHP dentro del layout principal.
 * Sin iframes: cada página es un documento completo y responsivo.
 */
final class View
{
    /** Renderiza app/Views/{vista}.php dentro del layout. */
    public static function render(string $vista, array $datos = [], string $layout = 'layouts/main'): void
    {
        $contenido = self::parcial($vista, $datos);
        extract($datos, EXTR_SKIP);
        require DIANA_ROOT . '/app/Views/' . $layout . '.php';
    }

    /** Renderiza una vista sin layout y la devuelve como cadena. */
    public static function parcial(string $vista, array $datos = []): string
    {
        extract($datos, EXTR_SKIP);
        ob_start();
        require DIANA_ROOT . '/app/Views/' . $vista . '.php';
        return (string) ob_get_clean();
    }
}

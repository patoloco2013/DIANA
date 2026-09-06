<?php
/**
 * Router para el servidor embebido de PHP (solo desarrollo):
 *   php -S localhost:8080 -t public public/router.php
 * En producción esto lo hace public/.htaccess con Apache.
 */
declare(strict_types=1);

$uri = urldecode((string) parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH));

// Servir archivos estáticos existentes tal cual
if ($uri !== '/' && is_file(__DIR__ . $uri)) {
    return false;
}

$_GET['r'] = trim($uri, '/');
require __DIR__ . '/index.php';

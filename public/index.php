<?php
/**
 * DIANA — Front controller único.
 * Toda petición entra por aquí (ver public/.htaccess).
 * Nada de PHP suelto en el docroot: el código vive fuera, en /app.
 */
declare(strict_types=1);

define('DIANA_ROOT', dirname(__DIR__));

require DIANA_ROOT . '/app/bootstrap.php';

(new Diana\Core\Router())->despachar($_GET['r'] ?? '');

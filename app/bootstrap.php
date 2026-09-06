<?php
/**
 * DIANA — Arranque: configuración, autoloader, sesión y cabeceras.
 */
declare(strict_types=1);

// --- Configuración -----------------------------------------------------
$rutaConfig = DIANA_ROOT . '/config/config.php';
if (!is_file($rutaConfig)) {
    http_response_code(500);
    exit('Falta config/config.php — copie config/config.example.php y ajústelo.');
}
$GLOBALS['diana_config'] = require $rutaConfig;

require DIANA_ROOT . '/app/helpers.php';

date_default_timezone_set(cfg('app.zona_horaria', 'America/Mexico_City'));

if (cfg('app.entorno') === 'produccion') {
    ini_set('display_errors', '0');
    error_reporting(E_ALL); // se registran en el log, no se muestran
} else {
    ini_set('display_errors', '1');
    error_reporting(E_ALL);
}

// --- Autoloader PSR-4: Diana\ => /app ---------------------------------
spl_autoload_register(function (string $clase): void {
    if (!str_starts_with($clase, 'Diana\\')) {
        return;
    }
    $ruta = DIANA_ROOT . '/app/' . str_replace('\\', '/', substr($clase, 6)) . '.php';
    if (is_file($ruta)) {
        require $ruta;
    }
});

// --- Sesión endurecida (sustituye las cookies con credenciales del SIE)
session_name(cfg('sesion.nombre', 'diana_sid'));
session_set_cookie_params([
    'lifetime' => 0,                              // cookie de sesión
    'path'     => '/',
    'secure'   => (bool) cfg('sesion.solo_https', false),
    'httponly' => true,
    'samesite' => 'Lax',
]);
session_start();

// Expiración por inactividad
$vida = ((int) cfg('sesion.vida_minutos', 120)) * 60;
if (isset($_SESSION['ultima_actividad']) && (time() - $_SESSION['ultima_actividad']) > $vida) {
    session_unset();
    session_destroy();
    session_start();
}
$_SESSION['ultima_actividad'] = time();

// --- Cabeceras de seguridad (también sin Apache, ej. php -S) ----------
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: SAMEORIGIN');
header('Referrer-Policy: same-origin');

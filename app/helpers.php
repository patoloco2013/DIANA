<?php
/**
 * DIANA — Funciones auxiliares globales.
 */
declare(strict_types=1);

/** Lee un valor de configuración con notación punto: cfg('db.host'). */
function cfg(string $clave, mixed $porDefecto = null): mixed
{
    $valor = $GLOBALS['diana_config'] ?? [];
    foreach (explode('.', $clave) as $parte) {
        if (!is_array($valor) || !array_key_exists($parte, $valor)) {
            return $porDefecto;
        }
        $valor = $valor[$parte];
    }
    return $valor;
}

/** Escapa para HTML. Usar en TODA salida de datos dinámicos en vistas. */
function e(mixed $texto): string
{
    return htmlspecialchars((string) ($texto ?? ''), ENT_QUOTES, 'UTF-8');
}

/**
 * URL base de la instalación, sin diagonal final.
 * Si app.url viene vacío se deduce de la petición, de modo que la app
 * funciona en cualquier subcarpeta sin configurar nada.
 */
function base_url(): string
{
    static $base = null;
    if ($base !== null) {
        return $base;
    }

    $configurada = trim((string) cfg('app.url', ''));
    // Solo se acepta una URL real; una ruta de disco mal capturada se ignora.
    if (preg_match('#^https?://#i', $configurada)) {
        return $base = rtrim($configurada, '/');
    }

    $esHttps = (!empty($_SERVER['HTTPS']) && strtolower((string) $_SERVER['HTTPS']) !== 'off')
        || (int) ($_SERVER['SERVER_PORT'] ?? 0) === 443;

    // El encabezado Host lo controla el cliente: se valida antes de usarlo.
    $host = (string) ($_SERVER['HTTP_HOST'] ?? '');
    if (!preg_match('/^[A-Za-z0-9.\-]{1,253}(:\d{1,5})?$/', $host)) {
        $host = 'localhost';
    }

    $dir = str_replace(chr(92), '/', dirname((string) ($_SERVER['SCRIPT_NAME'] ?? '/index.php')));
    $dir = ($dir === '/' || $dir === '.') ? '' : rtrim($dir, '/');

    return $base = ($esHttps ? 'https' : 'http') . '://' . $host . $dir;
}

/** URL de un archivo estático (CSS, imágenes): asset('assets/css/app.css'). */
function asset(string $ruta): string
{
    return base_url() . '/' . ltrim($ruta, '/');
}

/**
 * URL de una ruta de la aplicación: url('socios/editar/5').
 * Con app.urls_amigables = false (predeterminado) genera index.php?r=...,
 * que funciona aunque el servidor no aplique .htaccess ni mod_rewrite.
 */
function url(string $ruta = ''): string
{
    $base = base_url();
    $ruta = trim($ruta, '/');

    if ($ruta === '') {
        return cfg('app.urls_amigables', false) ? $base . '/' : $base . '/index.php';
    }
    if (cfg('app.urls_amigables', false)) {
        return $base . '/' . $ruta;
    }
    return $base . '/index.php?r=' . implode('/', array_map('rawurlencode', explode('/', $ruta)));
}

/** Redirige y termina la ejecución. */
function redirigir(string $ruta): never
{
    header('Location: ' . url($ruta));
    exit;
}

/** Guarda un mensaje flash para la siguiente petición. */
function flash(string $tipo, string $mensaje): void
{
    $_SESSION['flash'][] = ['tipo' => $tipo, 'mensaje' => $mensaje];
}

/** Devuelve y limpia los mensajes flash pendientes. */
function flashes(): array
{
    $lista = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    return $lista;
}

/** Formatea importes: $ 1,234.50 */
function dinero(float|int|string|null $importe): string
{
    return '$ ' . number_format((float) ($importe ?? 0), 2);
}

/** Fecha corta dd/mm/aaaa a partir de YYYY-MM-DD (o vacío). */
function fecha_corta(?string $fecha): string
{
    if (!$fecha || $fecha === '0000-00-00') {
        return '';
    }
    $ts = strtotime($fecha);
    return $ts ? date('d/m/Y', $ts) : '';
}

/** Aclara (factor > 0) u oscurece (factor < 0) un color #rrggbb. */
function color_sombra(string $hex, float $factor): string
{
    $hex = ltrim($hex, '#');
    if (strlen($hex) !== 6 || !ctype_xdigit($hex)) {
        $hex = '1f0512';
    }
    $salida = '#';
    foreach (str_split($hex, 2) as $par) {
        $c = hexdec($par);
        $c = $factor >= 0 ? $c + (255 - $c) * $factor : $c * (1 + $factor);
        $salida .= sprintf('%02x', (int) round(max(0, min(255, $c))));
    }
    return $salida;
}

/** Color de texto legible (blanco o casi negro) sobre un fondo #rrggbb. */
function color_contraste(string $hex): string
{
    $hex = ltrim($hex, '#');
    if (strlen($hex) !== 6 || !ctype_xdigit($hex)) {
        return '#ffffff';
    }
    [$r, $g, $b] = array_map('hexdec', str_split($hex, 2));
    $luminancia = (0.299 * $r + 0.587 * $g + 0.114 * $b) / 255;
    return $luminancia > 0.6 ? '#111827' : '#ffffff';
}

/** Iniciales (máx. 2) de un nombre, para avatares. */
function iniciales(string $nombre): string
{
    $partes = preg_split('/[^\p{L}\p{N}]+/u', trim($nombre), -1, PREG_SPLIT_NO_EMPTY) ?: [];
    $ini = '';
    foreach (array_slice($partes, 0, 2) as $p) {
        $ini .= mb_strtoupper(mb_substr($p, 0, 1));
    }
    return $ini !== '' ? $ini : '?';
}

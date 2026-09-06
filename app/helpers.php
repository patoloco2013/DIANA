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

/** URL absoluta dentro de la aplicación: url('socios/editar/5'). */
function url(string $ruta = ''): string
{
    return rtrim(cfg('app.url', ''), '/') . '/' . ltrim($ruta, '/');
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

<?php
/**
 * DIANA — Configuración local.
 *
 * Copiar este archivo a config/config.php y ajustar los valores.
 * config/config.php está en .gitignore: las credenciales NUNCA se versionan.
 */
return [
    'app' => [
        'nombre'  => 'DIANA',
        'version' => '1.0.0',
        // 'produccion' oculta errores al usuario; 'desarrollo' los muestra.
        'entorno' => 'desarrollo',
        // URL base sin diagonal final, ej. https://ccpmich.com/diana
        'url'     => 'http://localhost:8123',
        'zona_horaria' => 'America/Mexico_City',
    ],

    // Una sola base de datos para todos los colegios (multi-colegio por colegio_id).
    'db' => [
        'host'     => '127.0.0.1',
        'puerto'   => 3306,
        'nombre'   => 'diana',
        'usuario'  => 'diana_app',
        'password' => 'CAMBIAR',
        'charset'  => 'utf8mb4',
    ],

    'sesion' => [
        'nombre'          => 'diana_sid',
        'vida_minutos'    => 120,   // inactividad máxima antes de cerrar sesión
        'solo_https'      => false, // poner true en producción con HTTPS
    ],

    'seguridad' => [
        // Intentos de login fallidos permitidos antes de bloquear temporalmente
        'max_intentos_login'    => 5,
        'bloqueo_minutos'       => 15,
    ],
];

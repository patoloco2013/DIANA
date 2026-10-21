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
        // URL base sin diagonal final, ej. https://bookitech.mx/alicia/fer/public
        // Dejar vacio ('') para detectarla automaticamente de la peticion:
        // asi la app funciona en cualquier subcarpeta sin configurar nada.
        'url'     => '',

        // true SOLO si el servidor aplica .htaccess con mod_rewrite
        // (URLs tipo /socios/editar/5). En false usa index.php?r=... , que
        // funciona en cualquier hosting. Verifiquelo antes de activarlo.
        'urls_amigables' => false,
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

        // Clave para cifrar secretos guardados en la BD (p. ej. la contraseña
        // de la llave privada de los certificados del SAT). 32 bytes en base64:
        //   php -r "echo base64_encode(random_bytes(32)), PHP_EOL;"
        // Guárdela con los respaldos: si se pierde o cambia, habrá que volver
        // a subir los certificados.
        'clave_cifrado'         => '',
    ],

    'archivos' => [
        // Carpeta de fotos y documentos de socios. Idealmente FUERA del
        // docroot; se sirven a traves de la aplicacion, nunca por URL directa.
        'ruta'   => __DIR__ . '/../storage/uploads',
        // Tamano maximo por archivo. Debe ser <= upload_max_filesize y
        // post_max_size de PHP (ver php.ini o .user.ini del hosting).
        'max_mb' => 10,
    ],
];

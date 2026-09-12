<?php
declare(strict_types=1);

namespace Diana\Core;

use RuntimeException;

/**
 * Prueba de conexión SMTP con sockets nativos de PHP — sin PHPMailer ni
 * ninguna otra dependencia. Solo verifica que el host, puerto, usuario y
 * contraseña realmente funcionan; NO envía ningún correo. El envío
 * transaccional de la aplicación (recordatorios, confirmaciones) es un
 * módulo aparte, pendiente de construir.
 */
final class SmtpCliente
{
    private const TIMEOUT = 8;

    /** @return array{ok: bool, mensaje: string} */
    public static function probar(string $host, int $puerto, string $seguridad, string $usuario, string $password): array
    {
        $transporte = $seguridad === 'ssl' ? 'ssl://' : 'tcp://';
        $conexion = @stream_socket_client(
            $transporte . $host . ':' . $puerto, $errno, $errstr, self::TIMEOUT);
        if ($conexion === false) {
            return ['ok' => false, 'mensaje' => "No se pudo conectar a {$host}:{$puerto} ({$errstr})."];
        }
        stream_set_timeout($conexion, self::TIMEOUT);

        try {
            $saludo = self::leerLinea($conexion);
            if (!str_starts_with($saludo, '220')) {
                throw new RuntimeException("El servidor no respondió como SMTP: {$saludo}");
            }
            self::ehlo($conexion, $host);

            if ($seguridad === 'tls') {
                self::comando($conexion, "STARTTLS\r\n", '220');
                if (!stream_socket_enable_crypto($conexion, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
                    throw new RuntimeException('No fue posible iniciar TLS con el servidor.');
                }
                self::ehlo($conexion, $host);
            }

            if ($usuario !== '') {
                self::comando($conexion, "AUTH LOGIN\r\n", '334');
                self::comando($conexion, base64_encode($usuario) . "\r\n", '334');
                self::comando($conexion, base64_encode($password) . "\r\n", '235');
            }

            fwrite($conexion, "QUIT\r\n");
            fclose($conexion);
            return ['ok' => true, 'mensaje' => $usuario !== ''
                ? 'Conexión y autenticación exitosas.'
                : 'Conexión exitosa (sin probar credenciales: no hay usuario capturado).'];
        } catch (RuntimeException $e) {
            fclose($conexion);
            return ['ok' => false, 'mensaje' => $e->getMessage()];
        }
    }

    /** @param resource $conexion */
    private static function ehlo($conexion, string $host): void
    {
        fwrite($conexion, "EHLO {$host}\r\n");
        self::leerRespuesta($conexion, '250');
    }

    /** @param resource $conexion */
    private static function comando($conexion, string $texto, string $esperado): void
    {
        fwrite($conexion, $texto);
        self::leerRespuesta($conexion, $esperado);
    }

    /** @param resource $conexion */
    private static function leerLinea($conexion): string
    {
        $linea = fgets($conexion, 512);
        $estado = stream_get_meta_data($conexion);
        if ($linea === false || $estado['timed_out']) {
            throw new RuntimeException('El servidor no respondió a tiempo.');
        }
        return rtrim($linea, "\r\n");
    }

    /** Lee una respuesta SMTP (puede venir en varias líneas: "250-..." / "250 ..."). */
    private static function leerRespuesta($conexion, string $esperado): void
    {
        do {
            $linea = self::leerLinea($conexion);
        } while (isset($linea[3]) && $linea[3] === '-');
        if (!str_starts_with($linea, $esperado)) {
            throw new RuntimeException("El servidor respondió: {$linea}");
        }
    }
}

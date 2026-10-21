<?php
declare(strict_types=1);

namespace Diana\Core;

use RuntimeException;

/**
 * Cifrado simétrico (AES-256-GCM) para secretos que la aplicación necesita
 * recuperar después, como la contraseña de la llave privada de un CSD.
 * La clave vive en config.php (seguridad.clave_cifrado), nunca en la BD:
 * un respaldo de la base por sí solo no revela los secretos.
 */
final class Cifrado
{
    private const ALGORITMO = 'aes-256-gcm';
    private const VERSION   = 'v1';

    /** ¿Hay una clave de cifrado válida en la configuración? */
    public static function configurado(): bool
    {
        try {
            self::clave();
            return true;
        } catch (RuntimeException) {
            return false;
        }
    }

    public static function cifrar(string $texto): string
    {
        $iv = random_bytes(12);
        $tag = '';
        $cifrado = openssl_encrypt($texto, self::ALGORITMO, self::clave(), OPENSSL_RAW_DATA, $iv, $tag);
        if ($cifrado === false) {
            throw new RuntimeException('No fue posible cifrar el dato.');
        }
        return self::VERSION . ':' . base64_encode($iv . $tag . $cifrado);
    }

    public static function descifrar(string $paquete): string
    {
        [$version, $datos] = array_pad(explode(':', $paquete, 2), 2, '');
        $bruto = base64_decode($datos, true);
        if ($version !== self::VERSION || $bruto === false || strlen($bruto) < 29) {
            throw new RuntimeException('Dato cifrado con formato no válido.');
        }
        $texto = openssl_decrypt(substr($bruto, 28), self::ALGORITMO, self::clave(), OPENSSL_RAW_DATA,
            substr($bruto, 0, 12), substr($bruto, 12, 16));
        if ($texto === false) {
            throw new RuntimeException('No fue posible descifrar el dato (¿cambió la clave de cifrado?).');
        }
        return $texto;
    }

    private static function clave(): string
    {
        $clave = base64_decode((string) cfg('seguridad.clave_cifrado', ''), true);
        if ($clave === false || strlen($clave) !== 32) {
            throw new RuntimeException(
                'Falta configurar seguridad.clave_cifrado en config/config.php (32 bytes en base64).');
        }
        return $clave;
    }
}

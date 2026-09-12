<?php
declare(strict_types=1);

namespace Diana\Core;

use RuntimeException;

/**
 * Cifrado simétrico (AES-256-GCM) para secretos que se guardan en la base de
 * datos o en disco: contraseñas de terceros (SMTP, PAC), contraseña de la
 * llave privada del sello digital, y el contenido de los propios archivos
 * .cer/.key.
 *
 * La clave vive SOLO en config/config.php (fuera del repositorio). Si dos
 * instalaciones compartieran la misma clave, cualquiera podría descifrar los
 * secretos de la otra — por eso config.example.php la deja vacía y cada
 * instalación debe generar la suya:
 *   php -r "echo base64_encode(random_bytes(32));"
 */
final class Cifrado
{
    private const METODO = 'aes-256-gcm';

    private static function clave(): string
    {
        $b64 = (string) cfg('app.clave_cifrado', '');
        if ($b64 === '') {
            throw new RuntimeException(
                'Falta configurar app.clave_cifrado en config/config.php. ' .
                'Genere una con: php -r "echo base64_encode(random_bytes(32));"');
        }
        $clave = base64_decode($b64, true);
        if ($clave === false || strlen($clave) !== 32) {
            throw new RuntimeException('app.clave_cifrado no es una clave AES-256 válida (deben ser 32 bytes en base64).');
        }
        return $clave;
    }

    /** Cifra texto o binario arbitrario; el resultado es una cadena base64 autocontenida. */
    public static function cifrar(string $datos): string
    {
        $iv = random_bytes(12);
        $tag = '';
        $cifrado = openssl_encrypt($datos, self::METODO, self::clave(), OPENSSL_RAW_DATA, $iv, $tag);
        if ($cifrado === false) {
            throw new RuntimeException('No fue posible cifrar el valor.');
        }
        return base64_encode($iv . $tag . $cifrado);
    }

    /**
     * Descifra un valor producido por cifrar(). Devuelve null si el valor
     * viene vacío, no es válido, o fue alterado (la etiqueta de autenticación
     * de GCM no coincide) — nunca lanza una excepción por datos corruptos.
     */
    public static function descifrar(?string $valor): ?string
    {
        if ($valor === null || $valor === '') {
            return null;
        }
        $datos = base64_decode($valor, true);
        if ($datos === false || strlen($datos) < 29) {
            return null;
        }
        $iv = substr($datos, 0, 12);
        $tag = substr($datos, 12, 16);
        $cifrado = substr($datos, 28);
        $texto = openssl_decrypt($cifrado, self::METODO, self::clave(), OPENSSL_RAW_DATA, $iv, $tag);
        return $texto === false ? null : $texto;
    }
}

<?php
declare(strict_types=1);

namespace Diana\Core;

use RuntimeException;

/**
 * Validación de un Certificado de Sello Digital (CSD) del SAT: un .cer
 * (X.509 en DER) y un .key (llave privada PKCS#8 en DER, cifrada con
 * contraseña). Ambos archivos del SAT vienen en binario DER, no en el PEM
 * con el que trabaja OpenSSL por defecto — de ahí las envolturas de abajo.
 */
final class Csd
{
    /** Envuelve bytes DER de un certificado en el PEM que espera OpenSSL. */
    private static function certificadoAPem(string $derBytes): string
    {
        return "-----BEGIN CERTIFICATE-----\n" . chunk_split(base64_encode($derBytes), 64, "\n") . "-----END CERTIFICATE-----\n";
    }

    /** Envuelve bytes DER de una llave privada cifrada (PKCS#8) en PEM. */
    private static function llaveAPem(string $derBytes): string
    {
        return "-----BEGIN ENCRYPTED PRIVATE KEY-----\n" . chunk_split(base64_encode($derBytes), 64, "\n") . "-----END ENCRYPTED PRIVATE KEY-----\n";
    }

    /**
     * Valida que el certificado sea legible, que la contraseña abra la
     * llave privada y que ambos formen pareja. Lanza RuntimeException (con
     * un mensaje apto para mostrar al usuario) si algo no es correcto.
     *
     * @return array{numero_serie: string, vigente_desde: string, vigente_hasta: string, titular: string}
     */
    public static function validar(string $cerDer, string $keyDer, string $password): array
    {
        $cerPem = self::certificadoAPem($cerDer);
        $datos = @openssl_x509_parse($cerPem, false);
        if ($datos === false) {
            throw new RuntimeException('El archivo .cer no es un certificado X.509 válido.');
        }

        $keyPem = self::llaveAPem($keyDer);
        $llave = @openssl_pkey_get_private($keyPem, $password);
        if ($llave === false) {
            throw new RuntimeException('No fue posible abrir la llave privada: revise el archivo .key y la contraseña.');
        }

        if (!openssl_x509_check_private_key($cerPem, $llave)) {
            throw new RuntimeException('El certificado y la llave privada no corresponden al mismo par.');
        }

        $vigenteHasta = (int) ($datos['validTo_time_t'] ?? 0);
        if ($vigenteHasta > 0 && $vigenteHasta < time()) {
            throw new RuntimeException('El certificado ya venció el ' . date('d/m/Y', $vigenteHasta) . '; no puede usarse para timbrar.');
        }

        // openssl_x509_parse() normalmente usa nombres cortos (CN, O) para un
        // certificado real ya emitido, pero se acepta también la forma larga
        // por si el build de OpenSSL del servidor difiere.
        $sujeto = $datos['subject'] ?? [];
        $titular = $sujeto['CN'] ?? $sujeto['commonName'] ?? $sujeto['O'] ?? $sujeto['organizationName']
            ?? 'Sin nombre en el certificado';

        return [
            'numero_serie'  => (string) ($datos['serialNumberHex'] ?? $datos['serialNumber'] ?? ''),
            'vigente_desde' => date('Y-m-d', (int) ($datos['validFrom_time_t'] ?? time())),
            'vigente_hasta' => date('Y-m-d', $vigenteHasta ?: time()),
            'titular'       => (string) $titular,
        ];
    }
}

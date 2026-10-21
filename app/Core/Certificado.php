<?php
declare(strict_types=1);

namespace Diana\Core;

use RuntimeException;

/**
 * Lectura y validación de certificados del SAT (CSD y e.firma).
 *
 * El .cer es un X.509 en DER; el .key es una llave PKCS#8 cifrada en DER.
 * Se acepta también PEM por si el archivo fue convertido. Lanza
 * RuntimeException con mensajes aptos para mostrar al usuario.
 */
final class Certificado
{
    /**
     * Datos del certificado: rfc, titular, numero (20 dígitos del SAT),
     * valido_desde / valido_hasta (Y-m-d H:i:s), tipo ('csd'|'fiel') y pem.
     */
    public static function leer(string $contenido): array
    {
        $pem = self::aPem($contenido, 'CERTIFICATE');
        $info = openssl_x509_parse($pem);
        if ($info === false) {
            throw new RuntimeException('El archivo .cer no es un certificado válido.');
        }

        $sujeto = $info['subject'] ?? [];
        // x500UniqueIdentifier = "RFC / CURP" (persona física) o "RFC / RFC del representante" (moral)
        $unico = (string) ($sujeto['x500UniqueIdentifier'] ?? '');
        $rfc = strtoupper(trim(explode('/', $unico)[0]));
        if (!Catalogos::rfcValido($rfc)) {
            throw new RuntimeException('El certificado no contiene un RFC válido; verifique que sea un certificado del SAT.');
        }

        // El número de certificado del SAT es el número de serie leído como texto
        $serieHex = (string) ($info['serialNumberHex'] ?? '');
        $serie = $serieHex !== '' && strlen($serieHex) % 2 === 0 ? (string) hex2bin($serieHex) : '';
        $numero = ctype_digit($serie) ? $serie : strtoupper($serieHex);

        // La e.firma permite cifrado/acuerdo de llaves; el CSD solo firma
        $usos = (string) ($info['extensions']['keyUsage'] ?? '');
        $tipo = (str_contains($usos, 'Key Agreement') || str_contains($usos, 'Data Encipherment')) ? 'fiel' : 'csd';

        $titular = $sujeto['name'] ?? $sujeto['O'] ?? $sujeto['CN'] ?? '';
        if (is_array($titular)) {
            $titular = reset($titular);
        }

        return [
            'rfc'          => $rfc,
            'titular'      => mb_substr((string) $titular, 0, 254),
            'numero'       => mb_substr($numero, 0, 40),
            'valido_desde' => date('Y-m-d H:i:s', (int) $info['validFrom_time_t']),
            'valido_hasta' => date('Y-m-d H:i:s', (int) $info['validTo_time_t']),
            'tipo'         => $tipo,
            'pem'          => $pem,
        ];
    }

    /** Verifica que la llave abra con la contraseña y corresponda al certificado. */
    public static function verificarLlave(string $certPem, string $llave, string $password): void
    {
        $clave = openssl_pkey_get_private(self::aPem($llave, 'ENCRYPTED PRIVATE KEY'), $password);
        if ($clave === false) {
            throw new RuntimeException('No se pudo abrir el archivo .key: la contraseña es incorrecta o el archivo no es una llave privada.');
        }
        if (!openssl_x509_check_private_key($certPem, $clave)) {
            throw new RuntimeException('El archivo .key no corresponde a este certificado .cer.');
        }
    }

    /** Convierte DER a PEM (o deja el PEM tal cual). */
    private static function aPem(string $contenido, string $etiqueta): string
    {
        if (str_contains($contenido, '-----BEGIN')) {
            return $contenido;
        }
        return "-----BEGIN {$etiqueta}-----\n" . chunk_split(base64_encode($contenido), 64, "\n") . "-----END {$etiqueta}-----\n";
    }
}

<?php
declare(strict_types=1);

namespace Diana\Core;

use RuntimeException;

/**
 * Archivos subidos por usuarios (fotos, documentos de socios).
 *
 * Se guardan fuera de public/ con nombre aleatorio y se entregan solo a
 * través de un controlador que verifica sesión, permiso y colegio. Nunca se
 * confía en el nombre ni en el tipo declarado por el navegador: la extensión
 * se valida contra una lista blanca y el contenido real con finfo.
 */
final class Archivos
{
    /**
     * Extensión permitida => tipos MIME reales aceptados para ella.
     *
     * Word/Excel/PowerPoint modernos (.docx/.xlsx/.pptx) son en realidad un
     * ZIP por dentro, y según la versión de libmagic del servidor, finfo los
     * reporta como su MIME de Office o simplemente como "application/zip".
     * Se aceptan ambos: sigue rechazando cualquier archivo que no sea un ZIP
     * de verdad (un script renombrado, por ejemplo), aunque no distinga el
     * subtipo exacto de Office en esos casos.
     */
    private const TIPOS = [
        'pdf'  => ['application/pdf'],
        'jpg'  => ['image/jpeg'],
        'jpeg' => ['image/jpeg'],
        'png'  => ['image/png'],
        'webp' => ['image/webp'],
        'doc'  => ['application/msword', 'application/x-ole-storage', 'application/CDFV2', 'application/CDFV2-corrupt'],
        'docx' => ['application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'application/zip'],
        'xls'  => ['application/vnd.ms-excel', 'application/x-ole-storage', 'application/CDFV2', 'application/CDFV2-corrupt'],
        'xlsx' => ['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'application/zip'],
        'ppt'  => ['application/vnd.ms-powerpoint', 'application/x-ole-storage', 'application/CDFV2', 'application/CDFV2-corrupt'],
        'pptx' => ['application/vnd.openxmlformats-officedocument.presentationml.presentation', 'application/zip'],
    ];

    public const EXT_IMAGEN    = ['jpg', 'jpeg', 'png', 'webp'];
    public const EXT_DOCUMENTO = ['pdf', 'jpg', 'jpeg', 'png', 'webp'];
    /** Archivos adjuntos de eventos: además de PDF/imágenes, admite Office. */
    public const EXT_EVENTO_DOCUMENTO = ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'jpg', 'jpeg', 'png', 'webp'];

    public static function rutaBase(): string
    {
        $ruta = (string) cfg('archivos.ruta', '');
        return rtrim($ruta !== '' ? $ruta : DIANA_ROOT . '/storage/uploads', '/\\');
    }

    /** Bytes máximos por archivo según configuración (por defecto 10 MB). */
    public static function maxBytes(): int
    {
        return (int) cfg('archivos.max_mb', 10) * 1024 * 1024;
    }

    /** ¿Se envió algún archivo en este campo? */
    public static function enviado(?array $archivo): bool
    {
        return $archivo !== null && ($archivo['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE;
    }

    /**
     * Valida y guarda un archivo de $_FILES en storage/uploads/{subcarpeta}.
     * Devuelve ['archivo' => ruta relativa, 'nombre_original', 'mime', 'tamano'].
     * Lanza RuntimeException con un mensaje apto para mostrar al usuario.
     */
    public static function guardar(array $archivo, string $subcarpeta, array $extensiones): array
    {
        $error = (int) ($archivo['error'] ?? UPLOAD_ERR_NO_FILE);
        if ($error === UPLOAD_ERR_INI_SIZE || $error === UPLOAD_ERR_FORM_SIZE) {
            throw new RuntimeException('El archivo excede el tamaño permitido por el servidor.');
        }
        if ($error !== UPLOAD_ERR_OK || !is_uploaded_file($archivo['tmp_name'] ?? '')) {
            throw new RuntimeException('No se recibió el archivo correctamente.');
        }
        if ((int) $archivo['size'] > self::maxBytes()) {
            throw new RuntimeException('El archivo excede el máximo de ' . cfg('archivos.max_mb', 10) . ' MB.');
        }

        $original = (string) ($archivo['name'] ?? 'archivo');
        $ext = strtolower(pathinfo($original, PATHINFO_EXTENSION));
        if (!in_array($ext, $extensiones, true) || !isset(self::TIPOS[$ext])) {
            throw new RuntimeException('Tipo de archivo no permitido. Use: ' . strtoupper(implode(', ', $extensiones)) . '.');
        }

        $mime = (string) (new \finfo(FILEINFO_MIME_TYPE))->file($archivo['tmp_name']);
        if (!in_array($mime, self::TIPOS[$ext], true)) {
            throw new RuntimeException('El contenido del archivo no corresponde a su extensión.');
        }

        $subcarpeta = trim(preg_replace('#[^a-zA-Z0-9/_-]#', '', $subcarpeta), '/');
        $directorio = self::rutaBase() . '/' . $subcarpeta;
        self::asegurarDirectorio($directorio);

        $nombre   = bin2hex(random_bytes(16)) . '.' . $ext;
        $relativo = $subcarpeta . '/' . $nombre;
        if (!move_uploaded_file($archivo['tmp_name'], $directorio . '/' . $nombre)) {
            throw new RuntimeException('No fue posible guardar el archivo en el servidor.');
        }

        return [
            'archivo'         => $relativo,
            'nombre_original' => mb_substr(preg_replace('/[\x00-\x1F\x7F]/', '', $original), 0, 150),
            'mime'            => $mime,
            'tamano'          => (int) $archivo['size'],
        ];
    }

    public static function eliminar(?string $relativo): void
    {
        $ruta = self::rutaAbsoluta($relativo);
        if ($ruta !== null && is_file($ruta)) {
            @unlink($ruta);
        }
    }

    /**
     * Como guardar(), pero para material sensible (la llave privada del
     * sello digital, por ejemplo): no se valida el contenido por MIME —
     * estos archivos son binarios sin un tipo reconocible por finfo, así que
     * quien llama debe validarlo por su cuenta (abriéndolo con OpenSSL,
     * típicamente) antes o después de guardarlo. El contenido se cifra con
     * Cifrado::cifrar() antes de escribirse a disco: ni con acceso directo al
     * servidor de archivos queda expuesto el secreto sin también tener la
     * clave de cifrado de la aplicación (que vive solo en config/config.php).
     * Devuelve además 'contenido' con los bytes originales, para validarlos
     * de inmediato sin tener que releer y descifrar lo que se acaba de guardar.
     */
    public static function guardarCifrado(array $archivo, string $subcarpeta, array $extensiones): array
    {
        $error = (int) ($archivo['error'] ?? UPLOAD_ERR_NO_FILE);
        if ($error === UPLOAD_ERR_INI_SIZE || $error === UPLOAD_ERR_FORM_SIZE) {
            throw new RuntimeException('El archivo excede el tamaño permitido por el servidor.');
        }
        if ($error !== UPLOAD_ERR_OK || !is_uploaded_file($archivo['tmp_name'] ?? '')) {
            throw new RuntimeException('No se recibió el archivo correctamente.');
        }
        if ((int) $archivo['size'] > self::maxBytes()) {
            throw new RuntimeException('El archivo excede el máximo de ' . cfg('archivos.max_mb', 10) . ' MB.');
        }

        $original = (string) ($archivo['name'] ?? 'archivo');
        $ext = strtolower(pathinfo($original, PATHINFO_EXTENSION));
        if (!in_array($ext, $extensiones, true)) {
            throw new RuntimeException('Extensión no permitida. Use: ' . strtoupper(implode(', ', $extensiones)) . '.');
        }

        $contenido = file_get_contents($archivo['tmp_name']);
        if ($contenido === false || $contenido === '') {
            throw new RuntimeException('No fue posible leer el archivo subido.');
        }

        $subcarpeta = trim(preg_replace('#[^a-zA-Z0-9/_-]#', '', $subcarpeta), '/');
        $directorio = self::rutaBase() . '/' . $subcarpeta;
        self::asegurarDirectorio($directorio);

        $nombre   = bin2hex(random_bytes(16)) . '.enc';
        $relativo = $subcarpeta . '/' . $nombre;
        if (file_put_contents($directorio . '/' . $nombre, Cifrado::cifrar($contenido)) === false) {
            throw new RuntimeException('No fue posible guardar el archivo en el servidor.');
        }

        return ['archivo' => $relativo, 'contenido' => $contenido];
    }

    /** Lee y descifra un archivo guardado con guardarCifrado(). Null si no existe o es ilegible. */
    public static function leerCifrado(?string $relativo): ?string
    {
        $ruta = self::rutaAbsoluta($relativo);
        if ($ruta === null || !is_file($ruta)) {
            return null;
        }
        $cifrado = file_get_contents($ruta);
        return $cifrado === false ? null : Cifrado::descifrar($cifrado);
    }

    /** Envía el archivo al navegador (inline para verlo, o como descarga). */
    public static function enviar(?string $relativo, string $mime, string $nombreDescarga, bool $inline = true): never
    {
        $ruta = self::rutaAbsoluta($relativo);
        if ($ruta === null || !is_file($ruta)) {
            http_response_code(404);
            exit('Archivo no encontrado.');
        }
        $nombreDescarga = preg_replace('/[^A-Za-z0-9._-]+/', '_', $nombreDescarga) ?: 'archivo';

        header('Content-Type: ' . $mime);
        header('Content-Length: ' . (string) filesize($ruta));
        header('Content-Disposition: ' . ($inline ? 'inline' : 'attachment') . '; filename="' . $nombreDescarga . '"');
        header('Cache-Control: private, max-age=0, no-cache');
        readfile($ruta);
        exit;
    }

    /** Ruta absoluta segura: rechaza cualquier intento de salir de la base. */
    private static function rutaAbsoluta(?string $relativo): ?string
    {
        if ($relativo === null || $relativo === '' || str_contains($relativo, '..')) {
            return null;
        }
        $base = realpath(self::rutaBase());
        $ruta = realpath(self::rutaBase() . '/' . ltrim($relativo, '/\\'));
        if ($base === false || $ruta === false || !str_starts_with($ruta, $base)) {
            return null;
        }
        return $ruta;
    }

    /**
     * Crea el directorio y deja un index.html vacío en cada nivel: si la
     * carpeta quedara dentro del docroot en un hosting sin .htaccess, al menos
     * no se listaría su contenido.
     */
    private static function asegurarDirectorio(string $directorio): void
    {
        if (!is_dir($directorio) && !mkdir($directorio, 0750, true) && !is_dir($directorio)) {
            throw new RuntimeException('No se pudo crear la carpeta de archivos.');
        }
        $actual = self::rutaBase();
        foreach (explode('/', substr($directorio, strlen($actual))) as $parte) {
            $actual .= $parte === '' ? '' : '/' . $parte;
            if (is_dir($actual) && !is_file($actual . '/index.html')) {
                @file_put_contents($actual . '/index.html', '');
            }
        }
    }
}

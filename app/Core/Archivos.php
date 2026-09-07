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
    /** extensión permitida => tipos MIME reales aceptados para ella */
    private const TIPOS = [
        'pdf'  => ['application/pdf'],
        'jpg'  => ['image/jpeg'],
        'jpeg' => ['image/jpeg'],
        'png'  => ['image/png'],
        'webp' => ['image/webp'],
    ];

    public const EXT_IMAGEN    = ['jpg', 'jpeg', 'png', 'webp'];
    public const EXT_DOCUMENTO = ['pdf', 'jpg', 'jpeg', 'png', 'webp'];

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

<?php
declare(strict_types=1);

namespace Diana\Controllers;

use Diana\Core\Archivos;
use Diana\Core\Certificado;
use Diana\Core\Cifrado;
use Diana\Core\Controller;
use Diana\Core\Database;
use RuntimeException;

/**
 * Sección SAT: certificados del colegio activo (CSD para facturar y
 * e.firma). Se suben el .cer, el .key y la contraseña; antes de guardar se
 * comprueba que la llave abra con esa contraseña y corresponda al
 * certificado. Solo uno activo por tipo. El .key y la contraseña nunca se
 * descargan ni se muestran: los usará el módulo de facturación.
 */
final class SatController extends Controller
{
    public const MODULO = 'sat';

    /** Un .cer o .key del SAT pesa ~2 KB; esto deja margen y frena basura. */
    private const MAX_BYTES = 51200;

    /** Días antes del vencimiento en que se avisa. */
    public const DIAS_AVISO = 30;

    public const TIPOS = ['csd' => 'CSD (sello digital)', 'fiel' => 'e.firma (FIEL)'];

    public function index(): void
    {
        $cid = $this->colegioId();
        $this->vista('sat/index', [
            'titulo' => 'Certificados SAT',
            'certificados' => Database::todas(
                'SELECT s.*, u.nombre AS subido_por_nombre FROM sat_certificados s
                 LEFT JOIN usuarios u ON u.id = s.subido_por
                 WHERE s.colegio_id = ? AND s.eliminado_en IS NULL
                 ORDER BY s.tipo, s.activo DESC, s.valido_hasta DESC',
                [$cid]),
            'historial' => Database::todas(
                'SELECT s.*, u.nombre AS eliminado_por_nombre FROM sat_certificados s
                 LEFT JOIN usuarios u ON u.id = s.eliminado_por
                 WHERE s.colegio_id = ? AND s.eliminado_en IS NOT NULL
                 ORDER BY s.eliminado_en DESC LIMIT 50',
                [$cid]),
            'cifradoListo' => Cifrado::configurado(),
        ]);
    }

    /** Alta de un certificado (.cer + .key + contraseña). */
    public function subir(): void
    {
        if (!$this->esPost()) {
            redirigir('sat');
        }
        $cid = $this->colegioId();
        $guardados = [];
        try {
            $cer = $_FILES['cer'] ?? null;
            $key = $_FILES['key'] ?? null;
            $password = (string) ($_POST['password'] ?? '');
            if (!Archivos::enviado($cer) || !Archivos::enviado($key) || $password === '') {
                throw new RuntimeException('Seleccione el archivo .cer, el archivo .key y capture la contraseña de la llave.');
            }

            $cerBytes = $this->leerSubido($cer, '.cer');
            $keyBytes = $this->leerSubido($key, '.key');
            $datos = Certificado::leer($cerBytes);
            Certificado::verificarLlave($datos['pem'], $keyBytes, $password);

            if (strtotime($datos['valido_hasta']) < time()) {
                throw new RuntimeException('El certificado venció el ' . fecha_corta($datos['valido_hasta']) . '; descargue uno vigente del SAT.');
            }
            if (Database::una(
                'SELECT id FROM sat_certificados WHERE colegio_id = ? AND numero_certificado = ? AND eliminado_en IS NULL',
                [$cid, $datos['numero']])) {
                throw new RuntimeException("El certificado {$datos['numero']} ya está dado de alta.");
            }
            $passwordCifrada = Cifrado::cifrar($password);

            $guardados[] = $rutaCer = Archivos::guardar($cer, "sat/{$cid}", Archivos::EXT_CERTIFICADO)['archivo'];
            $guardados[] = $rutaKey = Archivos::guardar($key, "sat/{$cid}", Archivos::EXT_LLAVE)['archivo'];

            $activar = $this->post('activar') === '1'
                || !Database::una('SELECT id FROM sat_certificados WHERE colegio_id = ? AND tipo = ? AND activo = 1 AND eliminado_en IS NULL',
                    [$cid, $datos['tipo']]);

            $pdo = Database::pdo();
            $pdo->beginTransaction();
            try {
                if ($activar) {
                    Database::ejecutar('UPDATE sat_certificados SET activo = 0 WHERE colegio_id = ? AND tipo = ?',
                        [$cid, $datos['tipo']]);
                }
                Database::ejecutar(
                    'INSERT INTO sat_certificados (colegio_id, tipo, rfc, titular, numero_certificado, valido_desde, valido_hasta,
                                                   archivo_cer, archivo_key, password_cifrada, activo, subido_por)
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
                    [$cid, $datos['tipo'], $datos['rfc'], $datos['titular'], $datos['numero'],
                     $datos['valido_desde'], $datos['valido_hasta'], $rutaCer, $rutaKey, $passwordCifrada,
                     $activar ? 1 : 0, $this->usuarioId()]);
                $pdo->commit();
            } catch (\Throwable $e) {
                $pdo->rollBack();
                throw $e;
            }

            flash('success', self::TIPOS[$datos['tipo']] . " {$datos['numero']} de {$datos['rfc']} guardado"
                . ($activar ? ' y activo.' : '.'));
        } catch (RuntimeException $e) {
            array_map([Archivos::class, 'eliminar'], $guardados);
            flash('danger', $e->getMessage());
        } catch (\Throwable $e) {
            array_map([Archivos::class, 'eliminar'], $guardados);
            throw $e;
        }
        redirigir('sat');
    }

    /** Marca un certificado como el activo de su tipo. */
    public function activar(string $id = '0'): void
    {
        $c = $this->certificadoOAbortar($id);
        if ($this->esPost()) {
            if (strtotime($c['valido_hasta']) < time()) {
                flash('warning', 'No se puede activar un certificado vencido.');
            } else {
                $cid = $this->colegioId();
                $pdo = Database::pdo();
                $pdo->beginTransaction();
                Database::ejecutar('UPDATE sat_certificados SET activo = 0 WHERE colegio_id = ? AND tipo = ?', [$cid, $c['tipo']]);
                Database::ejecutar('UPDATE sat_certificados SET activo = 1 WHERE id = ? AND colegio_id = ?', [(int) $c['id'], $cid]);
                $pdo->commit();
                flash('success', "Certificado {$c['numero_certificado']} activo.");
            }
        }
        redirigir('sat');
    }

    /**
     * Quita un certificado: borra el .cer, el .key y la contraseña. El
     * renglón queda como historial (quién y cuándo lo quitó).
     */
    public function quitar(string $id = '0'): void
    {
        $c = $this->certificadoOAbortar($id);
        if ($this->esPost()) {
            Database::ejecutar(
                'UPDATE sat_certificados SET archivo_cer = NULL, archivo_key = NULL, password_cifrada = NULL,
                        activo = 0, eliminado_en = NOW(), eliminado_por = ?
                 WHERE id = ? AND colegio_id = ?',
                [$this->usuarioId(), (int) $c['id'], $this->colegioId()]);
            Archivos::eliminar($c['archivo_cer']);
            Archivos::eliminar($c['archivo_key']);
            flash('success', "Certificado {$c['numero_certificado']} quitado; sus archivos fueron eliminados del servidor."
                . ((int) $c['activo'] === 1 ? ' Ya no hay ' . self::TIPOS[$c['tipo']] . ' activo: active o suba otro.' : ''));
        }
        redirigir('sat');
    }

    /** Descarga del .cer (es público). El .key nunca se entrega. */
    public function descargar(string $id = '0'): void
    {
        $c = $this->certificadoOAbortar($id);
        Archivos::enviar($c['archivo_cer'], 'application/pkix-cert', $c['numero_certificado'] . '.cer', false);
    }

    // ------------------------------------------------------------------
    /** Certificado vigente (no quitado) del colegio activo, o redirige. */
    private function certificadoOAbortar(string $id): array
    {
        $c = Database::una('SELECT * FROM sat_certificados WHERE id = ? AND colegio_id = ? AND eliminado_en IS NULL',
            [(int) $id, $this->colegioId()]);
        if (!$c) {
            flash('warning', 'Certificado no encontrado.');
            redirigir('sat');
        }
        return $c;
    }

    /** Contenido de un archivo subido, con límites de tamaño propios. */
    private function leerSubido(array $archivo, string $etiqueta): string
    {
        if ((int) ($archivo['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK || !is_uploaded_file($archivo['tmp_name'] ?? '')) {
            throw new RuntimeException("No se recibió el archivo {$etiqueta} correctamente.");
        }
        if ((int) $archivo['size'] > self::MAX_BYTES) {
            throw new RuntimeException("El archivo {$etiqueta} es demasiado grande para ser un certificado del SAT.");
        }
        return (string) file_get_contents($archivo['tmp_name']);
    }
}

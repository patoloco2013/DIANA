<?php
declare(strict_types=1);

namespace Diana\Controllers;

use Diana\Core\Archivos;
use Diana\Core\Catalogos;
use Diana\Core\Cifrado;
use Diana\Core\Controller;
use Diana\Core\Csd;
use Diana\Core\Database;
use Diana\Core\SmtpCliente;
use RuntimeException;

/**
 * Configuración del colegio activo: datos fiscales y sello digital (CSD),
 * correo saliente (SMTP), PAC de timbrado, y plantilla de constancia.
 *
 * Cada sección vive en su propia tabla (colegio_fiscal, colegio_correo,
 * colegio_pac, colegio_constancia) con colegio_id como llave primaria: una
 * fila por colegio, actualizada por upsert. Todo secreto se cifra con
 * Cifrado antes de guardarse; nunca se muestra de vuelta al usuario, solo
 * "· · · · · · · ·" indicando que ya hay uno capturado.
 */
final class ConfiguracionController extends Controller
{
    public const MODULO = 'configuracion';

    private const PESTANAS = ['fiscal', 'correo', 'pac', 'constancia'];
    private const MASCARA_SECRETO = '••••••••';

    public function index(): void
    {
        $pestana = in_array($_GET['pestana'] ?? '', self::PESTANAS, true) ? $_GET['pestana'] : 'fiscal';
        $cid = $this->colegioId();

        $this->vista('configuracion/formulario', [
            'titulo'     => 'Configuración',
            'pestana'    => $pestana,
            'fiscal'     => Database::una('SELECT * FROM colegio_fiscal WHERE colegio_id = ?', [$cid]),
            'correo'     => Database::una('SELECT * FROM colegio_correo WHERE colegio_id = ?', [$cid]),
            'pac'        => Database::una('SELECT * FROM colegio_pac WHERE colegio_id = ?', [$cid]),
            'constancia' => Database::una('SELECT * FROM colegio_constancia WHERE colegio_id = ?', [$cid]),
            'mascara'    => self::MASCARA_SECRETO,
        ]);
    }

    // ------------------------------------------------------------------
    // 1. Fiscal: datos generales + Certificado de Sello Digital (CSD)
    // ------------------------------------------------------------------
    public function guardarFiscal(): void
    {
        $volver = fn() => redirigir('configuracion', ['pestana' => 'fiscal']);
        if (!$this->esPost()) {
            $volver();
        }
        $cid = $this->colegioId();
        $rfc = $this->post('rfc');
        $regimen = (string) $this->post('regimen_fiscal', '');
        $cp = $this->post('codigo_postal');

        $error = match (true) {
            $rfc && !Catalogos::rfcValido($rfc) => 'El RFC no tiene un formato válido.',
            $regimen !== '' && !isset(Catalogos::REGIMEN_FISCAL[$regimen]) => 'Régimen fiscal no válido.',
            $cp && !preg_match('/^[0-9]{5}$/', $cp) => 'El código postal debe tener 5 dígitos.',
            default => null,
        };
        if ($error !== null) {
            flash('danger', $error);
            $volver();
        }

        Database::ejecutar(
            'INSERT INTO colegio_fiscal (colegio_id, razon_social, rfc, regimen_fiscal, calle, numero_ext, numero_int, colonia, municipio, estado, codigo_postal)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
             ON DUPLICATE KEY UPDATE razon_social = VALUES(razon_social), rfc = VALUES(rfc),
                regimen_fiscal = VALUES(regimen_fiscal), calle = VALUES(calle), numero_ext = VALUES(numero_ext),
                numero_int = VALUES(numero_int), colonia = VALUES(colonia), municipio = VALUES(municipio),
                estado = VALUES(estado), codigo_postal = VALUES(codigo_postal)',
            [$cid, $this->post('razon_social') !== null ? mb_strtoupper($this->post('razon_social')) : null,
             $rfc !== null ? strtoupper($rfc) : null, $regimen !== '' ? $regimen : null,
             $this->post('calle'), $this->post('numero_ext'), $this->post('numero_int'),
             $this->post('colonia'), $this->post('municipio'),
             in_array($this->post('estado'), Catalogos::ESTADOS, true) ? $this->post('estado') : null, $cp]);
        flash('success', 'Datos fiscales actualizados.');
        $volver();
    }

    /** Sube y valida el CSD (.cer + .key + contraseña) como una sola unidad atómica. */
    public function guardarCsd(): void
    {
        $volver = fn() => redirigir('configuracion', ['pestana' => 'fiscal']);
        if (!$this->esPost()) {
            $volver();
        }
        $cid = $this->colegioId();
        $password = (string) ($_POST['csd_password'] ?? '');

        try {
            if (!Archivos::enviado($_FILES['csd_cer'] ?? null) || !Archivos::enviado($_FILES['csd_key'] ?? null) || $password === '') {
                throw new RuntimeException('Para cargar el sello digital suba el .cer, el .key y capture la contraseña, los tres juntos.');
            }
            $cer = Archivos::guardarCifrado($_FILES['csd_cer'], "colegios/{$cid}/csd", ['cer']);
            try {
                $key = Archivos::guardarCifrado($_FILES['csd_key'], "colegios/{$cid}/csd", ['key']);
            } catch (RuntimeException $e) {
                Archivos::eliminar($cer['archivo']);
                throw $e;
            }

            try {
                $meta = Csd::validar($cer['contenido'], $key['contenido'], $password);
            } catch (RuntimeException $e) {
                Archivos::eliminar($cer['archivo']);
                Archivos::eliminar($key['archivo']);
                throw $e;
            }

            $anterior = Database::una('SELECT csd_cer_archivo, csd_key_archivo FROM colegio_fiscal WHERE colegio_id = ?', [$cid]);

            Database::ejecutar(
                'INSERT INTO colegio_fiscal (colegio_id, csd_cer_archivo, csd_key_archivo, csd_key_password, csd_numero_serie, csd_titular, csd_vigente_desde, csd_vigente_hasta)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?)
                 ON DUPLICATE KEY UPDATE csd_cer_archivo = VALUES(csd_cer_archivo), csd_key_archivo = VALUES(csd_key_archivo),
                    csd_key_password = VALUES(csd_key_password), csd_numero_serie = VALUES(csd_numero_serie),
                    csd_titular = VALUES(csd_titular), csd_vigente_desde = VALUES(csd_vigente_desde), csd_vigente_hasta = VALUES(csd_vigente_hasta)',
                [$cid, $cer['archivo'], $key['archivo'], Cifrado::cifrar($password),
                 $meta['numero_serie'], $meta['titular'], $meta['vigente_desde'], $meta['vigente_hasta']]);

            if ($anterior) {
                Archivos::eliminar($anterior['csd_cer_archivo']);
                Archivos::eliminar($anterior['csd_key_archivo']);
            }
            flash('success', "Sello digital cargado y validado. Vigente hasta {$meta['vigente_hasta']}.");
        } catch (RuntimeException $e) {
            flash('danger', $e->getMessage());
        }
        $volver();
    }

    public function eliminarCsd(): void
    {
        $volver = fn() => redirigir('configuracion', ['pestana' => 'fiscal']);
        if ($this->esPost()) {
            $cid = $this->colegioId();
            $actual = Database::una('SELECT csd_cer_archivo, csd_key_archivo FROM colegio_fiscal WHERE colegio_id = ?', [$cid]);
            if ($actual) {
                Archivos::eliminar($actual['csd_cer_archivo']);
                Archivos::eliminar($actual['csd_key_archivo']);
                Database::ejecutar(
                    'UPDATE colegio_fiscal SET csd_cer_archivo = NULL, csd_key_archivo = NULL, csd_key_password = NULL,
                        csd_numero_serie = NULL, csd_titular = NULL, csd_vigente_desde = NULL, csd_vigente_hasta = NULL
                     WHERE colegio_id = ?', [$cid]);
                flash('success', 'Sello digital eliminado.');
            }
        }
        $volver();
    }

    // ------------------------------------------------------------------
    // 2. Correo (SMTP)
    // ------------------------------------------------------------------
    public function guardarCorreo(): void
    {
        $volver = fn() => redirigir('configuracion', ['pestana' => 'correo']);
        if (!$this->esPost()) {
            $volver();
        }
        $cid = $this->colegioId();
        $email = $this->post('remitente_email');
        if ($email && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            flash('danger', 'El correo remitente no es válido.');
            $volver();
        }
        $seguridad = in_array($this->post('smtp_seguridad'), ['ninguna', 'tls', 'ssl'], true)
            ? $this->post('smtp_seguridad') : 'tls';

        $passwordNueva = (string) ($_POST['smtp_password'] ?? '');
        $existente = Database::una('SELECT smtp_password FROM colegio_correo WHERE colegio_id = ?', [$cid]);
        $passwordCifrada = $passwordNueva !== '' ? Cifrado::cifrar($passwordNueva) : ($existente['smtp_password'] ?? null);

        Database::ejecutar(
            'INSERT INTO colegio_correo (colegio_id, smtp_host, smtp_puerto, smtp_seguridad, smtp_usuario, smtp_password, remitente_nombre, remitente_email)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)
             ON DUPLICATE KEY UPDATE smtp_host = VALUES(smtp_host), smtp_puerto = VALUES(smtp_puerto),
                smtp_seguridad = VALUES(smtp_seguridad), smtp_usuario = VALUES(smtp_usuario),
                smtp_password = VALUES(smtp_password), remitente_nombre = VALUES(remitente_nombre),
                remitente_email = VALUES(remitente_email)',
            [$cid, $this->post('smtp_host'), (int) ($this->post('smtp_puerto', '587') ?? 587), $seguridad,
             $this->post('smtp_usuario'), $passwordCifrada, $this->post('remitente_nombre'), $email]);
        flash('success', 'Configuración de correo actualizada.');
        $volver();
    }

    /** Prueba la conexión y autenticación SMTP con los datos ya guardados. */
    public function probarCorreo(): void
    {
        $volver = fn() => redirigir('configuracion', ['pestana' => 'correo']);
        if (!$this->esPost()) {
            $volver();
        }
        $correo = Database::una('SELECT * FROM colegio_correo WHERE colegio_id = ?', [$this->colegioId()]);
        if (!$correo || !$correo['smtp_host']) {
            flash('warning', 'Guarde primero el host y los datos del SMTP.');
            $volver();
        }
        $password = Cifrado::descifrar($correo['smtp_password']) ?? '';
        $resultado = SmtpCliente::probar(
            $correo['smtp_host'], (int) $correo['smtp_puerto'], $correo['smtp_seguridad'],
            (string) $correo['smtp_usuario'], $password);
        flash($resultado['ok'] ? 'success' : 'danger', $resultado['mensaje']);
        $volver();
    }

    // ------------------------------------------------------------------
    // 3. SAT PAC (timbrado de CFDI)
    // ------------------------------------------------------------------
    public function guardarPac(): void
    {
        $volver = fn() => redirigir('configuracion', ['pestana' => 'pac']);
        if (!$this->esPost()) {
            $volver();
        }
        $cid = $this->colegioId();
        $proveedor = isset(Catalogos::PAC_PROVEEDORES[$this->post('proveedor', '')])
            ? $this->post('proveedor') : 'timbox';
        $modo = $this->post('modo') === 'produccion' ? 'produccion' : 'pruebas';

        $existente = Database::una('SELECT password, api_key FROM colegio_pac WHERE colegio_id = ?', [$cid]);
        $passwordNueva = (string) ($_POST['password'] ?? '');
        $apiKeyNueva   = (string) ($_POST['api_key'] ?? '');
        $passwordCifrada = $passwordNueva !== '' ? Cifrado::cifrar($passwordNueva) : ($existente['password'] ?? null);
        $apiKeyCifrada   = $apiKeyNueva !== ''   ? Cifrado::cifrar($apiKeyNueva)   : ($existente['api_key'] ?? null);

        Database::ejecutar(
            'INSERT INTO colegio_pac (colegio_id, proveedor, modo, usuario, password, api_key, url_servicio)
             VALUES (?, ?, ?, ?, ?, ?, ?)
             ON DUPLICATE KEY UPDATE proveedor = VALUES(proveedor), modo = VALUES(modo), usuario = VALUES(usuario),
                password = VALUES(password), api_key = VALUES(api_key), url_servicio = VALUES(url_servicio)',
            [$cid, $proveedor, $modo, $this->post('usuario'), $passwordCifrada, $apiKeyCifrada, $this->post('url_servicio')]);
        flash('success', 'Configuración del PAC actualizada.');
        $volver();
    }

    // ------------------------------------------------------------------
    // 4. Constancia (por ahora, una sola plantilla general del colegio)
    // ------------------------------------------------------------------
    public function guardarConstancia(): void
    {
        $volver = fn() => redirigir('configuracion', ['pestana' => 'constancia']);
        if (!$this->esPost()) {
            $volver();
        }
        $cid = $this->colegioId();
        $titulo = $this->post('titulo') ?? 'Constancia de participación';
        $orientacion = $this->post('orientacion') === 'vertical' ? 'vertical' : 'horizontal';

        $imagenNueva = null;
        if (Archivos::enviado($_FILES['imagen'] ?? null)) {
            try {
                $imagenNueva = Archivos::guardar($_FILES['imagen'], "colegios/{$cid}/constancia", Archivos::EXT_IMAGEN)['archivo'];
            } catch (RuntimeException $e) {
                flash('danger', $e->getMessage());
                $volver();
            }
        }

        $actual = Database::una('SELECT imagen FROM colegio_constancia WHERE colegio_id = ?', [$cid]);
        $imagenFinal = $imagenNueva ?? ($this->post('quitar_imagen') === '1' ? null : ($actual['imagen'] ?? null));

        Database::ejecutar(
            'INSERT INTO colegio_constancia (colegio_id, titulo, cuerpo, orientacion, imagen)
             VALUES (?, ?, ?, ?, ?)
             ON DUPLICATE KEY UPDATE titulo = VALUES(titulo), cuerpo = VALUES(cuerpo),
                orientacion = VALUES(orientacion), imagen = VALUES(imagen)',
            [$cid, $titulo, $this->post('cuerpo'), $orientacion, $imagenFinal]);

        if ($imagenNueva !== null && $actual && $actual['imagen'] && $actual['imagen'] !== $imagenNueva) {
            Archivos::eliminar($actual['imagen']);
        } elseif ($imagenFinal === null && $actual && $actual['imagen']) {
            Archivos::eliminar($actual['imagen']);
        }
        flash('success', 'Plantilla de constancia actualizada.');
        $volver();
    }

    /** Imagen (logo/firma) de la constancia del colegio activo. */
    public function imagenConstancia(): void
    {
        $fila = Database::una('SELECT imagen FROM colegio_constancia WHERE colegio_id = ?', [$this->colegioId()]);
        if (!$fila || !$fila['imagen']) {
            http_response_code(404);
            exit('Sin imagen configurada.');
        }
        $ext = strtolower(pathinfo((string) $fila['imagen'], PATHINFO_EXTENSION));
        $mime = match ($ext) { 'png' => 'image/png', 'webp' => 'image/webp', default => 'image/jpeg' };
        Archivos::enviar($fila['imagen'], $mime, 'constancia.' . $ext);
    }
}

<?php
declare(strict_types=1);

namespace Diana\Controllers;

use Diana\Core\Archivos;
use Diana\Core\Catalogos;
use Diana\Core\Controller;
use Diana\Core\Database;
use RuntimeException;

/**
 * Padrón de socios del colegio activo: datos generales y adicionales,
 * foto, documentos digitales y perfiles fiscales para facturación.
 */
final class SociosController extends Controller
{
    public const MODULO = 'socios';

    private const PESTANAS = ['generales', 'adicionales', 'documentos', 'fiscal'];
    private const ESTATUS  = ['activo', 'suspendido', 'baja'];
    private const MIME_POR_EXT = [
        'pdf' => 'application/pdf', 'jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg',
        'png' => 'image/png', 'webp' => 'image/webp',
    ];

    // ------------------------------------------------------------------
    // Listado
    // ------------------------------------------------------------------
    public function index(): void
    {
        $cid = $this->colegioId();
        $q = trim((string) ($_GET['q'] ?? ''));

        $sql = "SELECT s.*,
                       COALESCE((SELECT SUM(CASE WHEN c.tipo = 'cargo' THEN c.importe ELSE -c.importe END)
                                 FROM cuentas c WHERE c.socio_id = s.id AND c.estatus = 'vigente'), 0) AS saldo
                FROM socios s WHERE s.colegio_id = ?";
        $params = [$cid];
        if ($q !== '') {
            $sql .= ' AND (s.nombre_completo LIKE ? OR s.numero LIKE ? OR s.rfc LIKE ? OR s.email LIKE ? OR s.celular LIKE ?)';
            $like = "%{$q}%";
            array_push($params, $like, $like, $like, $like, $like);
        }
        $sql .= ' ORDER BY s.nombre_completo LIMIT 300';

        $this->vista('socios/index', [
            'titulo' => 'Socios',
            'socios' => Database::todas($sql, $params),
            'q' => $q,
            'tiposSocio' => Catalogos::TIPOS_SOCIO,
        ]);
    }

    // ------------------------------------------------------------------
    // Alta y edición (pestañas Generales / Adicionales)
    // ------------------------------------------------------------------
    public function crear(): void
    {
        $this->formulario(null);
    }

    public function editar(string $id = '0'): void
    {
        $this->formulario($this->socioOAbortar($id));
    }

    private function formulario(?array $socio): void
    {
        $cid = $this->colegioId();
        $pestana = in_array($_GET['pestana'] ?? '', self::PESTANAS, true) ? $_GET['pestana'] : 'generales';

        if ($this->esPost()) {
            $datos = $this->datosDelFormulario();
            $error = $this->validar($datos, (int) ($socio['id'] ?? 0));

            $fotoNueva = null;
            if ($error === null && Archivos::enviado($_FILES['foto'] ?? null)) {
                try {
                    $fotoNueva = Archivos::guardar($_FILES['foto'], "socios/{$cid}/fotos", Archivos::EXT_IMAGEN)['archivo'];
                } catch (RuntimeException $e) {
                    $error = 'Foto: ' . $e->getMessage();
                }
            }

            if ($error === null) {
                if ($fotoNueva !== null) {
                    $datos['foto'] = $fotoNueva;
                } elseif ($socio && $this->post('quitar_foto') === '1') {
                    $datos['foto'] = null;
                }

                if ($socio) {
                    $sets = implode(', ', array_map(fn($k) => "`$k` = :$k", array_keys($datos)));
                    Database::ejecutar(
                        "UPDATE socios SET $sets WHERE id = :id AND colegio_id = :colegio_id",
                        $datos + ['id' => (int) $socio['id'], 'colegio_id' => $cid]);
                    if (array_key_exists('foto', $datos) && $socio['foto'] && $socio['foto'] !== $datos['foto']) {
                        Archivos::eliminar($socio['foto']);
                    }
                    flash('success', 'Socio actualizado.');
                    redirigir('socios/editar/' . (int) $socio['id'], ['pestana' => $pestana]);
                }

                $datos['colegio_id'] = $cid;
                $cols   = implode(', ', array_map(fn($k) => "`$k`", array_keys($datos)));
                $marcas = implode(', ', array_map(fn($k) => ":$k", array_keys($datos)));
                Database::ejecutar("INSERT INTO socios ($cols) VALUES ($marcas)", $datos);
                flash('success', 'Socio registrado. Ya puede agregar su foto, documentos y perfiles fiscales.');
                redirigir('socios/editar/' . Database::ultimoId());
            }

            flash('danger', $error);
            $socio = array_merge($socio ?? [], $datos);
        }

        $esEdicion = isset($socio['id']);
        $this->vista('socios/formulario', [
            'titulo'     => $esEdicion ? 'Editar socio' : 'Nuevo socio',
            'socio'      => $socio,
            'pestana'    => $esEdicion ? $pestana : (in_array($pestana, ['generales', 'adicionales'], true) ? $pestana : 'generales'),
            'documentos' => $esEdicion ? $this->documentosDe((int) $socio['id']) : [],
            'perfiles'   => $esEdicion ? $this->perfilesDe((int) $socio['id']) : [],
            'perfilEdicion' => $esEdicion ? $this->perfilOAbortarSuave((int) $socio['id'], (int) ($_GET['perfil'] ?? 0)) : null,
            'maxMb'      => (int) cfg('archivos.max_mb', 10),
        ]);
    }

    /** Recoge y normaliza los campos del formulario del socio. */
    private function datosDelFormulario(): array
    {
        $entero = fn(?string $v, int $min, int $max): ?int =>
            ($v !== null && ctype_digit($v) && (int) $v >= $min && (int) $v <= $max) ? (int) $v : null;

        $tipo    = (string) $this->post('tipo', '');
        $genero  = (string) $this->post('genero', '');
        $estado  = $this->post('estado');
        $estatus = $this->post('estatus');
        $rfc     = $this->post('rfc');

        return [
            'numero'            => $this->post('numero'),
            'titulo'            => $this->post('titulo'),
            'nombre'            => $this->post('nombre'),
            'apellido_paterno'  => $this->post('apellido_paterno'),
            'apellido_materno'  => $this->post('apellido_materno'),
            'rfc'               => $rfc !== null ? strtoupper($rfc) : null,
            'tipo'              => isset(Catalogos::TIPOS_SOCIO[$tipo]) ? $tipo : 'normal',
            'genero'            => isset(Catalogos::GENEROS[$genero]) ? $genero : 'sin_especificar',
            'cumple_dia'        => $entero($this->post('cumple_dia'), 1, 31),
            'cumple_mes'        => $entero($this->post('cumple_mes'), 1, 12),
            'limite_credito'    => max(0.0, (float) ($this->post('limite_credito', '0') ?? 0)),
            'paga_cuota_anual'  => $this->post('paga_cuota_anual') === '1' ? 1 : 0,
            'direccion'         => $this->post('direccion'),
            'colonia'           => $this->post('colonia'),
            'codigo_postal'     => $this->post('codigo_postal'),
            'localidad'         => $this->post('localidad'),
            'ciudad'            => $this->post('ciudad'),
            'estado'            => in_array($estado, Catalogos::ESTADOS, true) ? $estado : null,
            'email'             => $this->post('email'),
            'email2'            => $this->post('email2'),
            'telefono_oficina'  => $this->post('telefono_oficina'),
            'telefono_oficina2' => $this->post('telefono_oficina2'),
            'celular'           => $this->post('celular'),
            'estatus'           => in_array($estatus, self::ESTATUS, true) ? $estatus : 'activo',
            'observaciones'     => $this->post('observaciones'),
        ];
    }

    private function validar(array $d, int $idActual): ?string
    {
        if (!$d['numero'] || !$d['nombre']) {
            return 'Número de socio y nombre son obligatorios.';
        }
        foreach (['email' => 'El correo', 'email2' => 'El correo 2'] as $campo => $etiqueta) {
            if ($d[$campo] && !filter_var($d[$campo], FILTER_VALIDATE_EMAIL)) {
                return "$etiqueta no es válido.";
            }
        }
        if ($d['rfc'] && !Catalogos::rfcValido($d['rfc'])) {
            return 'El RFC no tiene un formato válido (12 o 13 caracteres).';
        }
        if (($d['cumple_dia'] === null) !== ($d['cumple_mes'] === null)) {
            return 'Capture día y mes de cumpleaños, o deje ambos vacíos.';
        }
        if ($d['codigo_postal'] && !preg_match('/^[0-9]{5}$/', $d['codigo_postal'])) {
            return 'El código postal debe tener 5 dígitos.';
        }
        $duplicado = Database::una(
            'SELECT id FROM socios WHERE colegio_id = ? AND numero = ? AND id <> ?',
            [$this->colegioId(), $d['numero'], $idActual]);
        if ($duplicado) {
            return "El número de socio {$d['numero']} ya existe en este colegio.";
        }
        return null;
    }

    /** Baja lógica: no se borran registros con historial de cuentas. */
    public function baja(string $id = '0'): void
    {
        if ($this->esPost()) {
            Database::ejecutar(
                "UPDATE socios SET estatus = 'baja' WHERE id = ? AND colegio_id = ?",
                [(int) $id, $this->colegioId()]);
            flash('success', 'Socio dado de baja.');
        }
        redirigir('socios');
    }

    /** Foto del socio (se sirve desde storage, con sesión y colegio verificados). */
    public function foto(string $id = '0'): void
    {
        $socio = $this->socioOAbortar($id);
        $ext = strtolower(pathinfo((string) $socio['foto'], PATHINFO_EXTENSION));
        Archivos::enviar($socio['foto'], self::MIME_POR_EXT[$ext] ?? 'application/octet-stream', 'foto-' . $socio['numero'] . '.' . $ext);
    }

    // ------------------------------------------------------------------
    // Documentos digitales
    // ------------------------------------------------------------------
    public function subirDocumento(string $id = '0'): void
    {
        $socio = $this->socioOAbortar($id);
        if (!$this->esPost()) {
            redirigir('socios/editar/' . (int) $socio['id'], ['pestana' => 'documentos']);
        }

        $tipo = (string) $this->post('tipo', '');
        try {
            if (!isset(Catalogos::TIPOS_DOCUMENTO[$tipo])) {
                throw new RuntimeException('Seleccione el tipo de documento.');
            }
            if (!Archivos::enviado($_FILES['archivo'] ?? null)) {
                throw new RuntimeException('Seleccione un archivo PDF o imagen.');
            }
            $cid = $this->colegioId();
            $info = Archivos::guardar($_FILES['archivo'], "socios/{$cid}/{$socio['id']}/documentos", Archivos::EXT_DOCUMENTO);
            Database::ejecutar(
                'INSERT INTO socio_documentos (colegio_id, socio_id, tipo, descripcion, archivo, nombre_original, mime, tamano, subido_por)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)',
                [$cid, (int) $socio['id'], $tipo, $this->post('descripcion'),
                 $info['archivo'], $info['nombre_original'], $info['mime'], $info['tamano'], $this->usuarioId()]);
            flash('success', 'Documento guardado.');
        } catch (RuntimeException $e) {
            flash('danger', $e->getMessage());
        }
        redirigir('socios/editar/' . (int) $socio['id'], ['pestana' => 'documentos']);
    }

    /** Ver (o descargar con ?descargar=1) un documento del socio. */
    public function documento(string $id = '0', string $docId = '0'): void
    {
        $socio = $this->socioOAbortar($id);
        $doc = Database::una('SELECT * FROM socio_documentos WHERE id = ? AND socio_id = ? AND colegio_id = ?',
            [(int) $docId, (int) $socio['id'], $this->colegioId()]);
        if (!$doc) {
            http_response_code(404);
            exit('Documento no encontrado.');
        }
        Archivos::enviar($doc['archivo'], $doc['mime'], $doc['nombre_original'], empty($_GET['descargar']));
    }

    public function eliminarDocumento(string $id = '0', string $docId = '0'): void
    {
        $socio = $this->socioOAbortar($id);
        if ($this->esPost()) {
            $doc = Database::una('SELECT * FROM socio_documentos WHERE id = ? AND socio_id = ? AND colegio_id = ?',
                [(int) $docId, (int) $socio['id'], $this->colegioId()]);
            if ($doc) {
                Database::ejecutar('DELETE FROM socio_documentos WHERE id = ?', [(int) $doc['id']]);
                Archivos::eliminar($doc['archivo']);
                flash('success', 'Documento eliminado.');
            }
        }
        redirigir('socios/editar/' . (int) $socio['id'], ['pestana' => 'documentos']);
    }

    // ------------------------------------------------------------------
    // Perfiles fiscales
    // ------------------------------------------------------------------
    public function guardarFiscal(string $id = '0', string $perfilId = '0'): void
    {
        $socio = $this->socioOAbortar($id);
        $volver = fn(array $extra = []) => redirigir('socios/editar/' . (int) $socio['id'], ['pestana' => 'fiscal'] + $extra);
        if (!$this->esPost()) {
            $volver();
        }
        $cid = $this->colegioId();
        $perfil = (int) $perfilId > 0 ? $this->perfilOAbortarSuave((int) $socio['id'], (int) $perfilId) : null;
        if ((int) $perfilId > 0 && !$perfil) {
            flash('warning', 'Perfil fiscal no encontrado.');
            $volver();
        }

        $d = [
            'alias'             => $this->post('alias'),
            'razon_social'      => $this->post('razon_social') !== null ? mb_strtoupper($this->post('razon_social')) : null,
            'rfc'               => $this->post('rfc') !== null ? strtoupper($this->post('rfc')) : null,
            'regimen_fiscal'    => $this->post('regimen_fiscal'),
            'uso_cfdi'          => $this->post('uso_cfdi') ?? 'G03',
            'codigo_postal'     => $this->post('codigo_postal'),
            'email_facturacion' => $this->post('email_facturacion'),
            'activo'            => $this->post('activo', '1') === '1' ? 1 : 0,
        ];
        $predeterminado = $this->post('predeterminado') === '1';

        $error = match (true) {
            !$d['alias'] || !$d['razon_social'] || !$d['rfc'] || !$d['regimen_fiscal'] || !$d['codigo_postal']
                => 'Alias, razón social, RFC, régimen fiscal y código postal son obligatorios.',
            !Catalogos::rfcValido($d['rfc'])                       => 'El RFC no tiene un formato válido.',
            !isset(Catalogos::REGIMEN_FISCAL[$d['regimen_fiscal']]) => 'Régimen fiscal no válido.',
            !isset(Catalogos::USO_CFDI[$d['uso_cfdi']])             => 'Uso de CFDI no válido.',
            !preg_match('/^[0-9]{5}$/', $d['codigo_postal'])        => 'El código postal fiscal debe tener 5 dígitos.',
            $d['email_facturacion'] && !filter_var($d['email_facturacion'], FILTER_VALIDATE_EMAIL)
                => 'El correo de facturación no es válido.',
            default => null,
        };
        if ($error !== null) {
            flash('danger', $error);
            $volver($perfil ? ['perfil' => (int) $perfil['id']] : []);
        }

        $pdo = Database::pdo();
        $pdo->beginTransaction();
        try {
            $existentes = (int) Database::valor('SELECT COUNT(*) FROM socio_perfiles_fiscales WHERE socio_id = ?', [(int) $socio['id']]);
            if ($predeterminado || ($existentes === 0 && !$perfil)) {
                Database::ejecutar('UPDATE socio_perfiles_fiscales SET predeterminado = 0 WHERE socio_id = ?', [(int) $socio['id']]);
                $d['predeterminado'] = 1;
            } elseif ($perfil) {
                $d['predeterminado'] = (int) $perfil['predeterminado'];
            } else {
                $d['predeterminado'] = 0;
            }

            if ($perfil) {
                $sets = implode(', ', array_map(fn($k) => "`$k` = :$k", array_keys($d)));
                Database::ejecutar("UPDATE socio_perfiles_fiscales SET $sets WHERE id = :id", $d + ['id' => (int) $perfil['id']]);
                flash('success', 'Perfil fiscal actualizado.');
            } else {
                $d += ['colegio_id' => $cid, 'socio_id' => (int) $socio['id']];
                $cols   = implode(', ', array_map(fn($k) => "`$k`", array_keys($d)));
                $marcas = implode(', ', array_map(fn($k) => ":$k", array_keys($d)));
                Database::ejecutar("INSERT INTO socio_perfiles_fiscales ($cols) VALUES ($marcas)", $d);
                flash('success', 'Perfil fiscal agregado.');
            }
            $pdo->commit();
        } catch (\Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
        $volver();
    }

    public function predeterminarFiscal(string $id = '0', string $perfilId = '0'): void
    {
        $socio = $this->socioOAbortar($id);
        if ($this->esPost() && $this->perfilOAbortarSuave((int) $socio['id'], (int) $perfilId)) {
            Database::ejecutar('UPDATE socio_perfiles_fiscales SET predeterminado = 0 WHERE socio_id = ?', [(int) $socio['id']]);
            Database::ejecutar('UPDATE socio_perfiles_fiscales SET predeterminado = 1, activo = 1 WHERE id = ?', [(int) $perfilId]);
            flash('success', 'Perfil fiscal predeterminado actualizado.');
        }
        redirigir('socios/editar/' . (int) $socio['id'], ['pestana' => 'fiscal']);
    }

    public function eliminarFiscal(string $id = '0', string $perfilId = '0'): void
    {
        $socio = $this->socioOAbortar($id);
        if ($this->esPost() && ($perfil = $this->perfilOAbortarSuave((int) $socio['id'], (int) $perfilId))) {
            Database::ejecutar('DELETE FROM socio_perfiles_fiscales WHERE id = ?', [(int) $perfil['id']]);
            if ((int) $perfil['predeterminado'] === 1) {
                // Que siempre quede un predeterminado si hay perfiles
                Database::ejecutar(
                    'UPDATE socio_perfiles_fiscales SET predeterminado = 1
                     WHERE socio_id = ? ORDER BY activo DESC, id LIMIT 1', [(int) $socio['id']]);
            }
            flash('success', 'Perfil fiscal eliminado.');
        }
        redirigir('socios/editar/' . (int) $socio['id'], ['pestana' => 'fiscal']);
    }

    // ------------------------------------------------------------------
    // Auxiliares
    // ------------------------------------------------------------------
    private function socioOAbortar(string $id): array
    {
        $socio = Database::una('SELECT * FROM socios WHERE id = ? AND colegio_id = ?', [(int) $id, $this->colegioId()]);
        if (!$socio) {
            flash('warning', 'Socio no encontrado.');
            redirigir('socios');
        }
        return $socio;
    }

    private function documentosDe(int $socioId): array
    {
        return Database::todas(
            'SELECT d.*, u.nombre AS subido_por_nombre
             FROM socio_documentos d LEFT JOIN usuarios u ON u.id = d.subido_por
             WHERE d.socio_id = ? AND d.colegio_id = ? ORDER BY d.creado_en DESC, d.id DESC',
            [$socioId, $this->colegioId()]);
    }

    private function perfilesDe(int $socioId): array
    {
        return Database::todas(
            'SELECT * FROM socio_perfiles_fiscales WHERE socio_id = ? AND colegio_id = ?
             ORDER BY predeterminado DESC, activo DESC, alias',
            [$socioId, $this->colegioId()]);
    }

    /** Perfil fiscal del socio, o null si no existe (sin redirigir). */
    private function perfilOAbortarSuave(int $socioId, int $perfilId): ?array
    {
        if ($perfilId <= 0) {
            return null;
        }
        return Database::una(
            'SELECT * FROM socio_perfiles_fiscales WHERE id = ? AND socio_id = ? AND colegio_id = ?',
            [$perfilId, $socioId, $this->colegioId()]);
    }
}

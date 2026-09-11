<?php
declare(strict_types=1);

namespace Diana\Controllers;

use Diana\Core\Archivos;
use Diana\Core\Catalogos;
use Diana\Core\Controller;
use Diana\Core\Database;
use RuntimeException;

/**
 * Eventos del colegio activo: datos generales y modalidad (presencial, en
 * línea con enlace de sesión, o híbrido), precios por categoría de asistente
 * y modalidad, módulos con puntos DPC por disciplina, y galería de imágenes.
 */
final class EventosController extends Controller
{
    public const MODULO = 'eventos';

    private const PESTANAS = ['generales', 'precios', 'modulos', 'imagenes'];
    private const ESTATUS  = ['borrador', 'publicado', 'cerrado', 'cancelado'];

    // ------------------------------------------------------------------
    // Listado
    // ------------------------------------------------------------------
    public function index(): void
    {
        $cid = $this->colegioId();
        $q = trim((string) ($_GET['q'] ?? ''));

        $sql = "SELECT e.*,
                       (SELECT COUNT(*) FROM asistencias a WHERE a.evento_id = e.id) AS registrados,
                       (SELECT COALESCE(SUM(p.puntos), 0) FROM evento_puntos p WHERE p.evento_id = e.id) AS puntos_dpc,
                       (SELECT COUNT(*) FROM evento_modulos m WHERE m.evento_id = e.id) AS modulos,
                       (SELECT MIN(pr.precio) FROM evento_precios pr WHERE pr.evento_id = e.id) AS precio_min,
                       (SELECT MAX(pr.precio) FROM evento_precios pr WHERE pr.evento_id = e.id) AS precio_max,
                       (SELECT i.id FROM evento_imagenes i WHERE i.evento_id = e.id
                        ORDER BY i.principal DESC, i.orden, i.id LIMIT 1) AS imagen_id
                FROM eventos e WHERE e.colegio_id = ?";
        $params = [$cid];
        if ($q !== '') {
            $sql .= ' AND (e.nombre LIKE ? OR e.expositores LIKE ? OR e.sede LIKE ?)';
            $like = "%{$q}%";
            array_push($params, $like, $like, $like);
        }
        $sql .= ' ORDER BY e.fecha_inicio DESC LIMIT 200';

        $this->vista('eventos/index', [
            'titulo'  => 'Eventos',
            'eventos' => Database::todas($sql, $params),
            'q'       => $q,
        ]);
    }

    // ------------------------------------------------------------------
    // Alta y edición
    // ------------------------------------------------------------------
    public function crear(): void
    {
        $this->formulario(null);
    }

    public function editar(string $id = '0'): void
    {
        $this->formulario($this->eventoOAbortar($id));
    }

    private function formulario(?array $evento): void
    {
        $cid = $this->colegioId();
        $pestana = in_array($_GET['pestana'] ?? '', self::PESTANAS, true) ? $_GET['pestana'] : 'generales';

        if ($this->esPost()) {
            $datos = $this->datosDelFormulario();
            $error = $this->validar($datos);

            if ($error === null) {
                if ($evento) {
                    $sets = implode(', ', array_map(fn($k) => "`$k` = :$k", array_keys($datos)));
                    Database::ejecutar(
                        "UPDATE eventos SET $sets WHERE id = :id AND colegio_id = :colegio_id",
                        $datos + ['id' => (int) $evento['id'], 'colegio_id' => $cid]);
                    flash('success', 'Evento actualizado.');
                    redirigir('eventos/editar/' . (int) $evento['id'], ['pestana' => $pestana]);
                }

                $datos += ['colegio_id' => $cid, 'creado_por' => $this->usuarioId()];
                $cols   = implode(', ', array_map(fn($k) => "`$k`", array_keys($datos)));
                $marcas = implode(', ', array_map(fn($k) => ":$k", array_keys($datos)));
                Database::ejecutar("INSERT INTO eventos ($cols) VALUES ($marcas)", $datos);
                $nuevoId = Database::ultimoId();

                // Precios en cero para todas las categorías, listos para capturar
                $this->sembrarPrecios($nuevoId, $datos['modalidad']);
                flash('success', 'Evento creado. Capture precios, módulos, puntos DPC e imágenes.');
                redirigir('eventos/editar/' . $nuevoId, ['pestana' => 'precios']);
            }

            flash('danger', $error);
            $evento = array_merge($evento ?? [], $datos);
        }

        $esEdicion = isset($evento['id']);
        $idEvento = (int) ($evento['id'] ?? 0);

        $this->vista('eventos/formulario', [
            'titulo'    => $esEdicion ? 'Editar evento' : 'Nuevo evento',
            'evento'    => $evento,
            'pestana'   => $esEdicion ? $pestana : 'generales',
            'precios'   => $esEdicion ? $this->preciosDe($idEvento) : [],
            'modulos'   => $esEdicion ? $this->modulosDe($idEvento) : [],
            'puntos'    => $esEdicion ? $this->puntosDe($idEvento) : [],
            'imagenes'  => $esEdicion ? $this->imagenesDe($idEvento) : [],
            'moduloEdicion' => $esEdicion ? $this->moduloDe($idEvento, (int) ($_GET['modulo'] ?? 0)) : null,
            'disciplinas'   => $esEdicion ? $this->disciplinasParaFormulario($idEvento) : [],
            'listaEstatus'  => self::ESTATUS,
            'maxMb'     => (int) cfg('archivos.max_mb', 10),
        ]);
    }

    private function datosDelFormulario(): array
    {
        $modalidad = (string) $this->post('modalidad', '');
        $esquema   = (string) $this->post('esquema_puntos', '');
        $estatus   = (string) $this->post('estatus', '');
        $enlace    = $this->post('enlace_sesion');

        return [
            'nombre'         => $this->post('nombre'),
            'tipo'           => $this->post('tipo'),
            'modalidad'      => isset(Catalogos::MODALIDADES[$modalidad]) ? $modalidad : 'presencial',
            'fecha_inicio'   => $this->post('fecha_inicio'),
            'fecha_fin'      => $this->post('fecha_fin'),
            'hora_inicio'    => $this->post('hora_inicio'),
            'hora_fin'       => $this->post('hora_fin'),
            'sede'           => $this->post('sede'),
            'expositores'    => $this->post('expositores'),
            'enlace_sesion'  => $enlace,
            'clave_sesion'   => $this->post('clave_sesion'),
            'esquema_puntos' => in_array($esquema, ['evento', 'modulo'], true) ? $esquema : 'evento',
            'cupo'           => $this->post('cupo') !== null ? max(0, (int) $this->post('cupo')) : null,
            'descripcion'    => $this->post('descripcion'),
            'estatus'        => in_array($estatus, self::ESTATUS, true) ? $estatus : 'publicado',
        ];
    }

    private function validar(array $d): ?string
    {
        if (!$d['nombre'] || !$d['fecha_inicio']) {
            return 'Nombre y fecha de inicio son obligatorios.';
        }
        if ($d['fecha_fin'] && $d['fecha_fin'] < $d['fecha_inicio']) {
            return 'La fecha final no puede ser anterior a la inicial.';
        }
        if ($d['enlace_sesion'] !== null && !filter_var($d['enlace_sesion'], FILTER_VALIDATE_URL)) {
            return 'El enlace de la sesión en línea no es una URL válida.';
        }
        if ($d['modalidad'] !== 'presencial' && $d['enlace_sesion'] === null && $d['estatus'] === 'publicado') {
            return 'Un evento en línea publicado necesita el enlace de la sesión (Webex, Zoom, Teams…).';
        }
        return null;
    }

    // ------------------------------------------------------------------
    // Precios por categoría y modalidad
    // ------------------------------------------------------------------
    public function guardarPrecios(string $id = '0'): void
    {
        $evento = $this->eventoOAbortar($id);
        if (!$this->esPost()) {
            redirigir('eventos/editar/' . (int) $evento['id'], ['pestana' => 'precios']);
        }
        $cid = $this->colegioId();
        $enviados = (array) ($_POST['precio'] ?? []);

        foreach (self::modalidadesDe($evento['modalidad']) as $modalidad) {
            foreach (array_keys(Catalogos::CATEGORIAS_ASISTENTE) as $categoria) {
                $valor = $enviados[$modalidad][$categoria] ?? null;
                if ($valor === null || trim((string) $valor) === '') {
                    Database::ejecutar(
                        'DELETE FROM evento_precios WHERE evento_id = ? AND categoria = ? AND modalidad = ?',
                        [(int) $evento['id'], $categoria, $modalidad]);
                    continue;
                }
                Database::ejecutar(
                    'INSERT INTO evento_precios (colegio_id, evento_id, categoria, modalidad, precio)
                     VALUES (?, ?, ?, ?, ?)
                     ON DUPLICATE KEY UPDATE precio = VALUES(precio)',
                    [$cid, (int) $evento['id'], $categoria, $modalidad, max(0.0, (float) $valor)]);
            }
        }
        flash('success', 'Precios actualizados.');
        redirigir('eventos/editar/' . (int) $evento['id'], ['pestana' => 'precios']);
    }

    /** Crea los renglones de precio en cero al dar de alta el evento. */
    private function sembrarPrecios(int $eventoId, string $modalidadEvento): void
    {
        foreach (self::modalidadesDe($modalidadEvento) as $modalidad) {
            foreach (array_keys(Catalogos::CATEGORIAS_ASISTENTE) as $categoria) {
                Database::ejecutar(
                    'INSERT IGNORE INTO evento_precios (colegio_id, evento_id, categoria, modalidad, precio)
                     VALUES (?, ?, ?, ?, 0)',
                    [$this->colegioId(), $eventoId, $categoria, $modalidad]);
            }
        }
    }

    // ------------------------------------------------------------------
    // Módulos
    // ------------------------------------------------------------------
    public function guardarModulo(string $id = '0', string $moduloId = '0'): void
    {
        $evento = $this->eventoOAbortar($id);
        $volver = fn(array $extra = []) => redirigir('eventos/editar/' . (int) $evento['id'], ['pestana' => 'modulos'] + $extra);
        if (!$this->esPost()) {
            $volver();
        }
        $modulo = $this->moduloDe((int) $evento['id'], (int) $moduloId);
        if ((int) $moduloId > 0 && !$modulo) {
            flash('warning', 'Módulo no encontrado.');
            $volver();
        }

        $d = [
            'orden'       => max(1, (int) ($this->post('orden', '1') ?? 1)),
            'nombre'      => $this->post('nombre'),
            'fecha'       => $this->post('fecha'),
            'hora_inicio' => $this->post('hora_inicio'),
            'hora_fin'    => $this->post('hora_fin'),
            'expositores' => $this->post('expositores'),
            'sede'        => $this->post('sede'),
        ];
        if (!$d['nombre']) {
            flash('danger', 'El nombre del módulo es obligatorio.');
            $volver($modulo ? ['modulo' => (int) $modulo['id']] : []);
        }

        if ($modulo) {
            $sets = implode(', ', array_map(fn($k) => "`$k` = :$k", array_keys($d)));
            Database::ejecutar("UPDATE evento_modulos SET $sets WHERE id = :id", $d + ['id' => (int) $modulo['id']]);
            flash('success', 'Módulo actualizado.');
        } else {
            $d += ['colegio_id' => $this->colegioId(), 'evento_id' => (int) $evento['id']];
            $cols   = implode(', ', array_map(fn($k) => "`$k`", array_keys($d)));
            $marcas = implode(', ', array_map(fn($k) => ":$k", array_keys($d)));
            Database::ejecutar("INSERT INTO evento_modulos ($cols) VALUES ($marcas)", $d);
            flash('success', 'Módulo agregado.');
        }
        $volver();
    }

    public function eliminarModulo(string $id = '0', string $moduloId = '0'): void
    {
        $evento = $this->eventoOAbortar($id);
        if ($this->esPost() && $this->moduloDe((int) $evento['id'], (int) $moduloId)) {
            // Los puntos del módulo se van con él (ON DELETE CASCADE)
            Database::ejecutar('DELETE FROM evento_modulos WHERE id = ?', [(int) $moduloId]);
            flash('success', 'Módulo eliminado.');
        }
        redirigir('eventos/editar/' . (int) $evento['id'], ['pestana' => 'modulos']);
    }

    /**
     * Genera un módulo por día entre la fecha inicial y la final del evento.
     * Atajo para diplomados largos (ej. 10 días = 10 módulos).
     */
    public function generarModulos(string $id = '0'): void
    {
        $evento = $this->eventoOAbortar($id);
        $volver = fn() => redirigir('eventos/editar/' . (int) $evento['id'], ['pestana' => 'modulos']);
        if (!$this->esPost()) {
            $volver();
        }
        if (!$evento['fecha_fin']) {
            flash('warning', 'Capture la fecha final del evento para generar un módulo por día.');
            $volver();
        }

        $inicio = new \DateTimeImmutable($evento['fecha_inicio']);
        $fin    = new \DateTimeImmutable($evento['fecha_fin']);
        $dias   = (int) $inicio->diff($fin)->days + 1;
        if ($dias > 60) {
            flash('warning', 'El rango de fechas supera 60 días; capture los módulos manualmente.');
            $volver();
        }

        $orden = (int) Database::valor('SELECT COALESCE(MAX(orden), 0) FROM evento_modulos WHERE evento_id = ?', [(int) $evento['id']]);
        $creados = 0;
        for ($i = 0; $i < $dias; $i++) {
            $fecha = $inicio->modify("+$i day")->format('Y-m-d');
            if (Database::una('SELECT id FROM evento_modulos WHERE evento_id = ? AND fecha = ?', [(int) $evento['id'], $fecha])) {
                continue; // no duplicar días ya capturados
            }
            Database::ejecutar(
                'INSERT INTO evento_modulos (colegio_id, evento_id, orden, nombre, fecha, hora_inicio, hora_fin, expositores, sede)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)',
                [$this->colegioId(), (int) $evento['id'], ++$orden,
                 'Módulo ' . $orden . ' · ' . fecha_corta($fecha), $fecha,
                 $evento['hora_inicio'], $evento['hora_fin'], $evento['expositores'], $evento['sede']]);
            $creados++;
        }
        flash($creados ? 'success' : 'warning',
            $creados ? "Se generaron $creados módulos (uno por día)." : 'Todos los días del rango ya tienen módulo.');
        $volver();
    }

    // ------------------------------------------------------------------
    // Puntos DPC por disciplina (a nivel evento o de un módulo)
    // ------------------------------------------------------------------
    public function guardarPuntos(string $id = '0', string $moduloId = '0'): void
    {
        $evento = $this->eventoOAbortar($id);
        $volver = fn() => redirigir('eventos/editar/' . (int) $evento['id'],
            ['pestana' => (int) $moduloId > 0 ? 'modulos' : 'generales']);
        if (!$this->esPost()) {
            $volver();
        }
        $modulo = (int) $moduloId > 0 ? $this->moduloDe((int) $evento['id'], (int) $moduloId) : null;
        if ((int) $moduloId > 0 && !$modulo) {
            flash('warning', 'Módulo no encontrado.');
            $volver();
        }

        // Solo se aceptan disciplinas del catálogo de este colegio (activas
        // o ya usadas en este evento); cualquier otro id enviado se ignora.
        $disciplinasValidas = array_column($this->disciplinasParaFormulario((int) $evento['id']), null, 'id');
        $enviados = (array) ($_POST['puntos'] ?? []);

        foreach ($disciplinasValidas as $disciplinaId => $disciplina) {
            $valor = $enviados[$disciplinaId] ?? '';
            $puntos = trim((string) $valor) === '' ? 0.0 : max(0.0, (float) $valor);

            $existente = $modulo
                ? Database::una('SELECT id FROM evento_puntos WHERE evento_id = ? AND modulo_id = ? AND disciplina_id = ?',
                    [(int) $evento['id'], (int) $modulo['id'], $disciplinaId])
                : Database::una('SELECT id FROM evento_puntos WHERE evento_id = ? AND modulo_id IS NULL AND disciplina_id = ?',
                    [(int) $evento['id'], $disciplinaId]);

            if ($puntos <= 0) {
                if ($existente) {
                    Database::ejecutar('DELETE FROM evento_puntos WHERE id = ?', [(int) $existente['id']]);
                }
                continue;
            }
            if ($existente) {
                Database::ejecutar('UPDATE evento_puntos SET puntos = ? WHERE id = ?', [$puntos, (int) $existente['id']]);
            } else {
                Database::ejecutar(
                    'INSERT INTO evento_puntos (colegio_id, evento_id, modulo_id, disciplina_id, puntos) VALUES (?, ?, ?, ?, ?)',
                    [$this->colegioId(), (int) $evento['id'], $modulo ? (int) $modulo['id'] : null, $disciplinaId, $puntos]);
            }
        }
        flash('success', $modulo ? 'Puntos DPC del módulo actualizados.' : 'Puntos DPC del evento actualizados.');
        $volver();
    }

    // ------------------------------------------------------------------
    // Imágenes
    // ------------------------------------------------------------------
    public function subirImagen(string $id = '0'): void
    {
        $evento = $this->eventoOAbortar($id);
        $volver = fn() => redirigir('eventos/editar/' . (int) $evento['id'], ['pestana' => 'imagenes']);
        if (!$this->esPost()) {
            $volver();
        }
        try {
            if (!Archivos::enviado($_FILES['imagen'] ?? null)) {
                throw new RuntimeException('Seleccione una imagen.');
            }
            $cid = $this->colegioId();
            $info = Archivos::guardar($_FILES['imagen'], "eventos/{$cid}/{$evento['id']}", Archivos::EXT_IMAGEN);

            $hayPrincipal = Database::valor('SELECT COUNT(*) FROM evento_imagenes WHERE evento_id = ? AND principal = 1', [(int) $evento['id']]);
            $principal = ($this->post('principal') === '1' || (int) $hayPrincipal === 0) ? 1 : 0;
            if ($principal === 1) {
                Database::ejecutar('UPDATE evento_imagenes SET principal = 0 WHERE evento_id = ?', [(int) $evento['id']]);
            }
            $orden = (int) Database::valor('SELECT COALESCE(MAX(orden), 0) + 1 FROM evento_imagenes WHERE evento_id = ?', [(int) $evento['id']]);

            Database::ejecutar(
                'INSERT INTO evento_imagenes (colegio_id, evento_id, archivo, nombre_original, mime, tamano, titulo, principal, orden, subido_por)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
                [$cid, (int) $evento['id'], $info['archivo'], $info['nombre_original'], $info['mime'], $info['tamano'],
                 $this->post('titulo'), $principal, $orden, $this->usuarioId()]);
            flash('success', $principal ? 'Imagen guardada como principal.' : 'Imagen agregada a la galería.');
        } catch (RuntimeException $e) {
            flash('danger', $e->getMessage());
        }
        $volver();
    }

    /** Sirve una imagen del evento (verifica sesión, permiso y colegio). */
    public function imagen(string $id = '0', string $imagenId = '0'): void
    {
        $evento = $this->eventoOAbortar($id);
        $img = Database::una('SELECT * FROM evento_imagenes WHERE id = ? AND evento_id = ? AND colegio_id = ?',
            [(int) $imagenId, (int) $evento['id'], $this->colegioId()]);
        if (!$img) {
            http_response_code(404);
            exit('Imagen no encontrada.');
        }
        Archivos::enviar($img['archivo'], $img['mime'], $img['nombre_original']);
    }

    public function principalImagen(string $id = '0', string $imagenId = '0'): void
    {
        $evento = $this->eventoOAbortar($id);
        if ($this->esPost() && $this->imagenDe((int) $evento['id'], (int) $imagenId)) {
            Database::ejecutar('UPDATE evento_imagenes SET principal = 0 WHERE evento_id = ?', [(int) $evento['id']]);
            Database::ejecutar('UPDATE evento_imagenes SET principal = 1 WHERE id = ?', [(int) $imagenId]);
            flash('success', 'Imagen principal actualizada.');
        }
        redirigir('eventos/editar/' . (int) $evento['id'], ['pestana' => 'imagenes']);
    }

    public function eliminarImagen(string $id = '0', string $imagenId = '0'): void
    {
        $evento = $this->eventoOAbortar($id);
        if ($this->esPost() && ($img = $this->imagenDe((int) $evento['id'], (int) $imagenId))) {
            Database::ejecutar('DELETE FROM evento_imagenes WHERE id = ?', [(int) $img['id']]);
            Archivos::eliminar($img['archivo']);
            if ((int) $img['principal'] === 1) {
                // Que siempre quede una principal si aún hay imágenes
                Database::ejecutar(
                    'UPDATE evento_imagenes SET principal = 1 WHERE evento_id = ? ORDER BY orden, id LIMIT 1',
                    [(int) $evento['id']]);
            }
            flash('success', 'Imagen eliminada.');
        }
        redirigir('eventos/editar/' . (int) $evento['id'], ['pestana' => 'imagenes']);
    }

    // ------------------------------------------------------------------
    // Auxiliares
    // ------------------------------------------------------------------
    /** Modalidades de asistencia posibles según la modalidad del evento. */
    public static function modalidadesDe(string $modalidadEvento): array
    {
        return match ($modalidadEvento) {
            'linea'   => ['linea'],
            'hibrido' => ['presencial', 'linea'],
            default   => ['presencial'],
        };
    }

    private function eventoOAbortar(string $id): array
    {
        $evento = Database::una('SELECT * FROM eventos WHERE id = ? AND colegio_id = ?', [(int) $id, $this->colegioId()]);
        if (!$evento) {
            flash('warning', 'Evento no encontrado.');
            redirigir('eventos');
        }
        return $evento;
    }

    /** Precios indexados como [modalidad][categoria] => precio. */
    private function preciosDe(int $eventoId): array
    {
        $mapa = [];
        foreach (Database::todas('SELECT * FROM evento_precios WHERE evento_id = ?', [$eventoId]) as $p) {
            $mapa[$p['modalidad']][$p['categoria']] = $p['precio'];
        }
        return $mapa;
    }

    private function modulosDe(int $eventoId): array
    {
        return Database::todas(
            'SELECT m.*, (SELECT COALESCE(SUM(p.puntos), 0) FROM evento_puntos p WHERE p.modulo_id = m.id) AS puntos_dpc
             FROM evento_modulos m WHERE m.evento_id = ? ORDER BY m.orden, m.id',
            [$eventoId]);
    }

    private function moduloDe(int $eventoId, int $moduloId): ?array
    {
        if ($moduloId <= 0) {
            return null;
        }
        return Database::una('SELECT * FROM evento_modulos WHERE id = ? AND evento_id = ?', [$moduloId, $eventoId]);
    }

    /** Puntos indexados: [''|modulo_id][disciplina_id] => puntos. */
    private function puntosDe(int $eventoId): array
    {
        $mapa = [];
        foreach (Database::todas('SELECT * FROM evento_puntos WHERE evento_id = ?', [$eventoId]) as $p) {
            $mapa[$p['modulo_id'] === null ? '' : (string) $p['modulo_id']][(int) $p['disciplina_id']] = $p['puntos'];
        }
        return $mapa;
    }

    /**
     * Disciplinas que se ofrecen para capturar puntos en este evento: las
     * activas del colegio, más cualquiera ya usada aquí aunque después se
     * haya desactivado (para no perder de vista puntos ya otorgados).
     */
    private function disciplinasParaFormulario(int $eventoId): array
    {
        return Database::todas(
            'SELECT DISTINCT d.* FROM disciplinas d
             WHERE d.colegio_id = ?
               AND (d.activo = 1 OR d.id IN (SELECT disciplina_id FROM evento_puntos WHERE evento_id = ?))
             ORDER BY d.orden, d.nombre',
            [$this->colegioId(), $eventoId]);
    }

    private function imagenesDe(int $eventoId): array
    {
        return Database::todas(
            'SELECT * FROM evento_imagenes WHERE evento_id = ? ORDER BY principal DESC, orden, id',
            [$eventoId]);
    }

    private function imagenDe(int $eventoId, int $imagenId): ?array
    {
        if ($imagenId <= 0) {
            return null;
        }
        return Database::una('SELECT * FROM evento_imagenes WHERE id = ? AND evento_id = ?', [$imagenId, $eventoId]);
    }
}

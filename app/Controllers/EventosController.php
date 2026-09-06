<?php
declare(strict_types=1);

namespace Diana\Controllers;

use Diana\Core\Controller;
use Diana\Core\Database;

/** Catálogo de eventos (cursos, congresos) del colegio activo. */
final class EventosController extends Controller
{
    public const MODULO = 'eventos';

    private const ESTATUS = ['borrador', 'publicado', 'cerrado', 'cancelado'];

    public function index(): void
    {
        $cid = $this->colegioId();
        $q = trim((string) ($_GET['q'] ?? ''));

        $sql = "SELECT e.*,
                       (SELECT COUNT(*) FROM asistencias a WHERE a.evento_id = e.id) AS registrados
                FROM eventos e WHERE e.colegio_id = ?";
        $params = [$cid];
        if ($q !== '') {
            $sql .= ' AND (e.nombre LIKE ? OR e.expositores LIKE ? OR e.sede LIKE ?)';
            $like = "%{$q}%";
            array_push($params, $like, $like, $like);
        }
        $sql .= ' ORDER BY e.fecha_inicio DESC LIMIT 200';

        $this->vista('eventos/index', [
            'titulo' => 'Eventos',
            'eventos' => Database::todas($sql, $params),
            'q' => $q,
        ]);
    }

    public function crear(): void
    {
        $this->formulario(null);
    }

    public function editar(string $id = '0'): void
    {
        $evento = Database::una('SELECT * FROM eventos WHERE id = ? AND colegio_id = ?',
            [(int) $id, $this->colegioId()]);
        if (!$evento) {
            flash('warning', 'Evento no encontrado.');
            redirigir('eventos');
        }
        $this->formulario($evento);
    }

    private function formulario(?array $evento): void
    {
        $cid = $this->colegioId();

        if ($this->esPost()) {
            $datos = [
                'nombre'         => $this->post('nombre'),
                'tipo'           => $this->post('tipo'),
                'fecha_inicio'   => $this->post('fecha_inicio'),
                'fecha_fin'      => $this->post('fecha_fin'),
                'hora_inicio'    => $this->post('hora_inicio'),
                'hora_fin'       => $this->post('hora_fin'),
                'sede'           => $this->post('sede'),
                'expositores'    => $this->post('expositores'),
                'puntos_epc'     => (float) ($this->post('puntos_epc', '0') ?? 0),
                'precio_socio'   => (float) ($this->post('precio_socio', '0') ?? 0),
                'precio_publico' => (float) ($this->post('precio_publico', '0') ?? 0),
                'cupo'           => $this->post('cupo') !== null ? (int) $this->post('cupo') : null,
                'descripcion'    => $this->post('descripcion'),
                'estatus'        => in_array($this->post('estatus'), self::ESTATUS, true)
                                      ? $this->post('estatus') : 'publicado',
            ];

            $error = null;
            if (!$datos['nombre'] || !$datos['fecha_inicio']) {
                $error = 'Nombre y fecha de inicio son obligatorios.';
            } elseif ($datos['fecha_fin'] && $datos['fecha_fin'] < $datos['fecha_inicio']) {
                $error = 'La fecha final no puede ser anterior a la inicial.';
            }

            if ($error === null) {
                if ($evento) {
                    Database::ejecutar(
                        'UPDATE eventos SET nombre=?, tipo=?, fecha_inicio=?, fecha_fin=?, hora_inicio=?, hora_fin=?,
                                sede=?, expositores=?, puntos_epc=?, precio_socio=?, precio_publico=?, cupo=?,
                                descripcion=?, estatus=?
                         WHERE id = ? AND colegio_id = ?',
                        [...array_values($datos), (int) $evento['id'], $cid]);
                    flash('success', 'Evento actualizado.');
                } else {
                    Database::ejecutar(
                        'INSERT INTO eventos (colegio_id, nombre, tipo, fecha_inicio, fecha_fin, hora_inicio, hora_fin,
                                sede, expositores, puntos_epc, precio_socio, precio_publico, cupo, descripcion, estatus, creado_por)
                         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
                        [$cid, ...array_values($datos), $this->usuarioId()]);
                    flash('success', 'Evento creado.');
                }
                redirigir('eventos');
            }

            flash('danger', $error);
            $evento = array_merge($evento ?? [], $datos);
        }

        $this->vista('eventos/formulario', [
            'titulo' => $evento && isset($evento['id']) ? 'Editar evento' : 'Nuevo evento',
            'evento' => $evento,
            'listaEstatus' => self::ESTATUS,
        ]);
    }
}

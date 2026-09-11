<?php
declare(strict_types=1);

namespace Diana\Controllers;

use Diana\Core\Controller;
use Diana\Core\Database;

/**
 * Catálogo de disciplinas para puntos DPC, propio de cada colegio.
 *
 * Nunca se elimina un renglón: una disciplina ya usada en evento_puntos no
 * puede perder su fila sin romper los puntos DPC ya otorgados en eventos
 * pasados. Por eso solo existen alta, edición del nombre y activar/desactivar
 * (una disciplina desactivada deja de ofrecerse en eventos nuevos, pero sigue
 * mostrándose donde ya tenga puntos capturados).
 */
final class DisciplinasController extends Controller
{
    public const MODULO = 'eventos';

    /** Catálogo de arranque para un colegio nuevo (ver ColegiosController). */
    public const SEMILLA = [
        'Fiscal', 'Auditoría', 'Contabilidad', 'Finanzas', 'Ética profesional',
        'Administración', 'Costos', 'Legal y laboral', 'Tecnologías de información',
        'Sector gubernamental', 'Docencia y educación', 'Otras disciplinas',
    ];

    public function index(): void
    {
        $disciplinas = Database::todas(
            'SELECT d.*, (SELECT COUNT(*) FROM evento_puntos p WHERE p.disciplina_id = d.id) AS usos
             FROM disciplinas d WHERE d.colegio_id = ?
             ORDER BY d.activo DESC, d.orden, d.nombre',
            [$this->colegioId()]);

        $this->vista('eventos/disciplinas', ['titulo' => 'Disciplinas DPC', 'disciplinas' => $disciplinas]);
    }

    /** Alta de una disciplina nueva. */
    public function crear(): void
    {
        if ($this->esPost()) {
            $this->guardar(null);
        }
        redirigir('disciplinas');
    }

    /** Renombrar una disciplina existente (el histórico de puntos no cambia). */
    public function editar(string $id = '0'): void
    {
        if ($this->esPost()) {
            $this->guardar($this->disciplinaOAbortar($id));
        }
        redirigir('disciplinas');
    }

    private function guardar(?array $disciplina): void
    {
        $cid = $this->colegioId();
        $nombre = $this->post('nombre');

        if (!$nombre) {
            flash('danger', 'Capture el nombre de la disciplina.');
            return;
        }
        $duplicada = Database::una(
            'SELECT id FROM disciplinas WHERE colegio_id = ? AND nombre = ? AND id <> ?',
            [$cid, $nombre, (int) ($disciplina['id'] ?? 0)]);
        if ($duplicada) {
            flash('danger', "Ya existe una disciplina llamada «{$nombre}».");
            return;
        }

        if ($disciplina) {
            Database::ejecutar('UPDATE disciplinas SET nombre = ? WHERE id = ? AND colegio_id = ?',
                [$nombre, (int) $disciplina['id'], $cid]);
            flash('success', 'Disciplina actualizada.');
        } else {
            $orden = (int) Database::valor('SELECT COALESCE(MAX(orden), 0) + 1 FROM disciplinas WHERE colegio_id = ?', [$cid]);
            Database::ejecutar('INSERT INTO disciplinas (colegio_id, nombre, orden, activo) VALUES (?, ?, ?, 1)',
                [$cid, $nombre, $orden]);
            flash('success', 'Disciplina agregada.');
        }
    }

    /** Activar o desactivar. Nunca se borra: rompería los puntos ya otorgados. */
    public function alternar(string $id = '0'): void
    {
        $d = $this->disciplinaOAbortar($id);
        if ($this->esPost()) {
            $nuevoEstado = (int) $d['activo'] === 1 ? 0 : 1;
            Database::ejecutar('UPDATE disciplinas SET activo = ? WHERE id = ? AND colegio_id = ?',
                [$nuevoEstado, (int) $d['id'], $this->colegioId()]);
            flash('success', $nuevoEstado === 1 ? 'Disciplina reactivada.' : 'Disciplina desactivada.');
        }
        redirigir('disciplinas');
    }

    private function disciplinaOAbortar(string $id): array
    {
        $d = Database::una('SELECT * FROM disciplinas WHERE id = ? AND colegio_id = ?', [(int) $id, $this->colegioId()]);
        if (!$d) {
            flash('warning', 'Disciplina no encontrada.');
            redirigir('disciplinas');
        }
        return $d;
    }

    /** Crea el catálogo inicial de disciplinas para un colegio recién dado de alta. */
    public static function sembrar(int $colegioId): void
    {
        foreach (self::SEMILLA as $i => $nombre) {
            Database::ejecutar(
                'INSERT IGNORE INTO disciplinas (colegio_id, nombre, orden, activo) VALUES (?, ?, ?, 1)',
                [$colegioId, $nombre, $i + 1]);
        }
    }
}

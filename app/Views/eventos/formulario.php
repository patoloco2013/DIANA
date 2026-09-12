<?php
use Diana\Core\Catalogos;
use Diana\Core\Csrf;

$esEdicion = isset($evento['id']);
$idEvento = (int) ($evento['id'] ?? 0);
$v = fn(string $campo, string $porDefecto = '') => e($evento[$campo] ?? $porDefecto);
$modalidad = $evento['modalidad'] ?? 'presencial';
$esquema = $evento['esquema_puntos'] ?? 'evento';
$imagenPrincipal = null;
foreach ($imagenes as $img) {
    if ((int) $img['principal'] === 1) { $imagenPrincipal = $img; break; }
}
$totalPuntos = 0.0;
foreach ($puntos as $porNivel) { $totalPuntos += array_sum(array_map('floatval', $porNivel)); }

$pestanas = [
    'generales' => ['Generales', 'bi-calendar-event', true],
    'precios'   => ['Precios',   'bi-tags',           $esEdicion],
    'modulos'   => ['Módulos y DPC', 'bi-list-ol',    $esEdicion],
    'imagenes'  => ['Imágenes',  'bi-images',         $esEdicion],
    'archivos'  => ['Archivos',  'bi-paperclip',      $esEdicion],
];
$conteo = ['modulos' => count($modulos), 'imagenes' => count($imagenes), 'archivos' => count($documentos)];
?>

<div class="d-flex flex-wrap align-items-center gap-3 mb-4">
    <?php if ($imagenPrincipal): ?>
        <img class="diana-evento-miniatura" src="<?= e(url('eventos/imagen/' . $idEvento . '/' . (int) $imagenPrincipal['id'])) ?>" alt="Imagen del evento">
    <?php else: ?>
        <span class="diana-evento-miniatura diana-foto-vacia"><i class="bi bi-calendar-event"></i></span>
    <?php endif; ?>
    <div>
        <h1 class="h4 mb-0"><?= $esEdicion ? $v('nombre') : 'Nuevo evento' ?></h1>
        <?php if ($esEdicion): ?>
        <div class="small text-muted">
            <?= e(fecha_corta($evento['fecha_inicio'])) ?><?= $evento['fecha_fin'] ? ' al ' . e(fecha_corta($evento['fecha_fin'])) : '' ?>
            · <?= e(Catalogos::MODALIDADES[$modalidad] ?? $modalidad) ?>
            · <?= e(number_format($totalPuntos, 2)) ?> pts DPC
            · <span class="badge text-bg-<?= ['publicado' => 'success', 'borrador' => 'secondary', 'cerrado' => 'dark', 'cancelado' => 'danger'][$evento['estatus']] ?? 'secondary' ?>"><?= $v('estatus') ?></span>
        </div>
        <?php endif; ?>
    </div>
    <div class="ms-auto d-flex flex-wrap gap-2">
        <?php if ($esEdicion): ?>
        <a class="btn btn-outline-secondary" href="<?= e(url('registro/evento/' . $idEvento)) ?>">
            <i class="bi bi-clipboard2-check me-1"></i>Registro
        </a>
        <?php endif; ?>
        <a class="btn btn-outline-secondary" href="<?= e(url('eventos')) ?>"><i class="bi bi-arrow-left me-1"></i>Listado</a>
    </div>
</div>

<ul class="nav nav-tabs diana-tabs" role="tablist">
    <?php foreach ($pestanas as $clave => [$etiqueta, $icono, $habilitada]): ?>
    <li class="nav-item" role="presentation">
        <button class="nav-link <?= $pestana === $clave ? 'active' : '' ?> <?= $habilitada ? '' : 'disabled' ?>"
                data-bs-toggle="tab" data-bs-target="#tab-<?= $clave ?>" type="button" role="tab"
                <?= $habilitada ? '' : 'title="Guarde el evento para habilitar esta pestaña"' ?>>
            <i class="bi <?= $icono ?> me-1"></i><?= $etiqueta ?>
            <?php if (isset($conteo[$clave]) && $habilitada): ?>
                <span class="badge rounded-pill text-bg-secondary ms-1"><?= (int) $conteo[$clave] ?></span>
            <?php endif; ?>
        </button>
    </li>
    <?php endforeach; ?>
</ul>

<div class="card diana-card-tabs">
    <div class="card-body">
        <div class="tab-content">

            <!-- ============ GENERALES ============ -->
            <div class="tab-pane fade <?= $pestana === 'generales' ? 'show active' : '' ?>" id="tab-generales" role="tabpanel">
                <form method="post" action="<?= e(url($esEdicion ? 'eventos/editar/' . $idEvento : 'eventos/crear', ['pestana' => 'generales'])) ?>">
                    <?= Csrf::campo() ?>
                    <div class="row g-3">
                        <div class="col-12 col-md-6">
                            <label class="form-label" for="nombre">Nombre del evento *</label>
                            <input class="form-control" id="nombre" name="nombre" maxlength="200" required value="<?= $v('nombre') ?>">
                        </div>
                        <div class="col-6 col-md-3">
                            <label class="form-label" for="tipo">Tipo</label>
                            <input class="form-control" id="tipo" name="tipo" maxlength="60" placeholder="Curso, diplomado…" value="<?= $v('tipo') ?>">
                        </div>
                        <div class="col-6 col-md-3">
                            <label class="form-label" for="modalidad">Modalidad *</label>
                            <select class="form-select" id="modalidad" name="modalidad">
                                <?php foreach (Catalogos::MODALIDADES as $clave => $etiqueta): ?>
                                <option value="<?= $clave ?>" <?= $modalidad === $clave ? 'selected' : '' ?>><?= e($etiqueta) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-6 col-md-3">
                            <label class="form-label" for="fecha_inicio">Fecha inicio *</label>
                            <input class="form-control" id="fecha_inicio" name="fecha_inicio" type="date" required value="<?= $v('fecha_inicio') ?>">
                        </div>
                        <div class="col-6 col-md-3">
                            <label class="form-label" for="fecha_fin">Fecha fin</label>
                            <input class="form-control" id="fecha_fin" name="fecha_fin" type="date" value="<?= $v('fecha_fin') ?>">
                        </div>
                        <div class="col-6 col-md-3">
                            <label class="form-label" for="hora_inicio">Hora inicio</label>
                            <input class="form-control" id="hora_inicio" name="hora_inicio" type="time" value="<?= $v('hora_inicio') ?>">
                        </div>
                        <div class="col-6 col-md-3">
                            <label class="form-label" for="hora_fin">Hora fin</label>
                            <input class="form-control" id="hora_fin" name="hora_fin" type="time" value="<?= $v('hora_fin') ?>">
                        </div>

                        <div class="col-12 col-md-6">
                            <label class="form-label" for="sede">Sede <span class="text-muted">(presencial)</span></label>
                            <input class="form-control" id="sede" name="sede" maxlength="150" value="<?= $v('sede') ?>">
                        </div>
                        <div class="col-12 col-md-6">
                            <label class="form-label" for="expositores">Expositores</label>
                            <input class="form-control" id="expositores" name="expositores" maxlength="255" value="<?= $v('expositores') ?>">
                        </div>

                        <div class="col-12 col-md-8">
                            <label class="form-label" for="enlace_sesion">Enlace de la sesión en línea <span class="text-muted">(Webex, Zoom, Teams…)</span></label>
                            <input class="form-control" id="enlace_sesion" name="enlace_sesion" type="url" maxlength="500"
                                   placeholder="https://colegio.webex.com/meet/…" value="<?= $v('enlace_sesion') ?>">
                        </div>
                        <div class="col-12 col-md-4">
                            <label class="form-label" for="clave_sesion">Clave de acceso</label>
                            <input class="form-control" id="clave_sesion" name="clave_sesion" maxlength="60" value="<?= $v('clave_sesion') ?>">
                        </div>

                        <div class="col-6 col-md-3">
                            <label class="form-label" for="cupo">Cupo</label>
                            <input class="form-control" id="cupo" name="cupo" type="number" min="0" value="<?= $v('cupo') ?>">
                        </div>
                        <div class="col-6 col-md-3">
                            <label class="form-label" for="estatus">Estatus</label>
                            <select class="form-select" id="estatus" name="estatus">
                                <?php foreach ($listaEstatus as $op): ?>
                                <option value="<?= $op ?>" <?= ($evento['estatus'] ?? 'publicado') === $op ? 'selected' : '' ?>><?= ucfirst($op) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-12 col-md-6">
                            <label class="form-label" for="esquema_puntos">Puntos DPC</label>
                            <select class="form-select" id="esquema_puntos" name="esquema_puntos">
                                <option value="evento" <?= $esquema === 'evento' ? 'selected' : '' ?>>Por evento (un total para todo)</option>
                                <option value="modulo" <?= $esquema === 'modulo' ? 'selected' : '' ?>>Por módulo (cada módulo tiene los suyos)</option>
                            </select>
                        </div>

                        <div class="col-12">
                            <label class="form-label" for="descripcion">Descripción / objetivo</label>
                            <textarea class="form-control" id="descripcion" name="descripcion" rows="3"><?= $v('descripcion') ?></textarea>
                        </div>
                    </div>

                    <div class="mt-4 d-flex gap-2">
                        <button class="btn btn-primary" type="submit"><i class="bi bi-check-lg me-1"></i><?= $esEdicion ? 'Guardar cambios' : 'Crear evento' ?></button>
                        <a class="btn btn-outline-secondary" href="<?= e(url('eventos')) ?>">Cancelar</a>
                    </div>
                </form>

                <?php if ($esEdicion && $esquema === 'evento'): ?>
                <hr class="my-4">
                <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-1">
                    <h2 class="h6 fw-semibold mb-0">Puntos DPC por disciplina</h2>
                    <a class="small" href="<?= e(url('disciplinas')) ?>"><i class="bi bi-gear me-1"></i>Catálogo de disciplinas</a>
                </div>
                <p class="small text-muted">Puntos que otorga el evento completo. Para repartirlos por día o sesión, cambie el esquema a «por módulo».</p>
                <?php if (!$disciplinas): ?>
                    <p class="text-muted">No hay disciplinas activas. <a href="<?= e(url('disciplinas')) ?>">Agregue alguna en el catálogo</a>.</p>
                <?php else: ?>
                <form method="post" action="<?= e(url('eventos/guardarPuntos/' . $idEvento)) ?>">
                    <?= Csrf::campo() ?>
                    <?php $puntosEvento = $puntos[''] ?? []; ?>
                    <div class="row g-2">
                        <?php foreach ($disciplinas as $d): ?>
                        <div class="col-6 col-md-4 col-xl-3">
                            <label class="form-label small mb-1" for="pt_<?= (int) $d['id'] ?>">
                                <?= e($d['nombre']) ?><?= (int) $d['activo'] === 0 ? ' <span class="text-muted">(inactiva)</span>' : '' ?>
                            </label>
                            <input class="form-control form-control-sm" id="pt_<?= (int) $d['id'] ?>" name="puntos[<?= (int) $d['id'] ?>]"
                                   type="number" step="0.5" min="0" placeholder="0"
                                   value="<?= isset($puntosEvento[$d['id']]) && (float) $puntosEvento[$d['id']] > 0 ? e((string) (float) $puntosEvento[$d['id']]) : '' ?>">
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <button class="btn btn-outline-primary btn-sm mt-3" type="submit"><i class="bi bi-check-lg me-1"></i>Guardar puntos DPC</button>
                </form>
                <?php endif; ?>
                <?php elseif ($esEdicion): ?>
                <hr class="my-4">
                <p class="small text-muted mb-0">
                    <i class="bi bi-info-circle me-1"></i>Este evento otorga sus puntos DPC <strong>por módulo</strong>:
                    captúrelos en la pestaña «Módulos y DPC». Total actual: <strong><?= e(number_format($totalPuntos, 2)) ?></strong> puntos.
                </p>
                <?php endif; ?>
            </div>

            <?php if ($esEdicion): ?>
            <div class="tab-pane fade <?= $pestana === 'precios' ? 'show active' : '' ?>" id="tab-precios" role="tabpanel">
                <?php require __DIR__ . '/_precios.php'; ?>
            </div>
            <div class="tab-pane fade <?= $pestana === 'modulos' ? 'show active' : '' ?>" id="tab-modulos" role="tabpanel">
                <?php require __DIR__ . '/_modulos.php'; ?>
            </div>
            <div class="tab-pane fade <?= $pestana === 'imagenes' ? 'show active' : '' ?>" id="tab-imagenes" role="tabpanel">
                <?php require __DIR__ . '/_imagenes.php'; ?>
            </div>
            <div class="tab-pane fade <?= $pestana === 'archivos' ? 'show active' : '' ?>" id="tab-archivos" role="tabpanel">
                <?php require __DIR__ . '/_archivos.php'; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

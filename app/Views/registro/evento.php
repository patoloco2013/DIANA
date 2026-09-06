<?php
use Diana\Core\Catalogos;
use Diana\Core\Csrf;

$idEvento = (int) $evento['id'];
$unaModalidad = count($modalidades) === 1 ? $modalidades[0] : null;
?>
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-1">
    <h1 class="h4 mb-0"><?= e($evento['nombre']) ?></h1>
    <div class="d-flex gap-2">
        <a class="btn btn-outline-secondary btn-sm" href="<?= e(url('eventos/editar/' . $idEvento)) ?>">
            <i class="bi bi-pencil me-1"></i>Editar evento
        </a>
        <a class="btn btn-outline-secondary btn-sm" href="<?= e(url('registro')) ?>">
            <i class="bi bi-arrow-left me-1"></i>Eventos
        </a>
    </div>
</div>
<p class="text-muted mb-3">
    <?= e(fecha_corta($evento['fecha_inicio'])) ?><?= $evento['fecha_fin'] ? ' al ' . e(fecha_corta($evento['fecha_fin'])) : '' ?>
    · <?= e(Catalogos::MODALIDADES[$evento['modalidad']] ?? $evento['modalidad']) ?>
    <?php if ($evento['sede']): ?> · <?= e($evento['sede']) ?><?php endif; ?>
    · <?= e(number_format($puntosTotales, 2)) ?> pts DPC
    <?php if ($modulos): ?> · <?= count($modulos) ?> módulos<?php endif; ?>
</p>

<?php if ($evento['enlace_sesion']): ?>
<div class="alert alert-info d-flex flex-wrap align-items-center gap-2 py-2">
    <i class="bi bi-camera-video"></i>
    <span>Sesión en línea:</span>
    <a href="<?= e($evento['enlace_sesion']) ?>" target="_blank" rel="noopener noreferrer"><?= e($evento['enlace_sesion']) ?></a>
    <?php if ($evento['clave_sesion']): ?>
        <span class="ms-2">Clave: <strong><?= e($evento['clave_sesion']) ?></strong></span>
    <?php endif; ?>
</div>
<?php endif; ?>

<div class="row g-3">
    <div class="col-12 col-lg-4">
        <div class="card">
            <div class="card-header bg-white fw-semibold">Registrar asistente</div>
            <div class="card-body">
                <form method="post" action="<?= e(url('registro/agregar/' . $idEvento)) ?>">
                    <?= Csrf::campo() ?>

                    <?php if ($unaModalidad !== null): ?>
                        <input type="hidden" name="modalidad" value="<?= e($unaModalidad) ?>">
                    <?php else: ?>
                    <div class="mb-3">
                        <label class="form-label" for="modalidad">Asiste</label>
                        <select class="form-select" id="modalidad" name="modalidad">
                            <?php foreach ($modalidades as $m): ?>
                            <option value="<?= $m ?>"><?= e(Catalogos::MODALIDADES_ASISTENCIA[$m]) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <?php endif; ?>

                    <div class="mb-3">
                        <label class="form-label" for="categoria">Categoría (precio)</label>
                        <select class="form-select" id="categoria" name="categoria">
                            <option value="">Según el tipo de socio</option>
                            <?php foreach (Catalogos::CATEGORIAS_ASISTENTE as $clave => $etiqueta): ?>
                                <?php
                                $partes = [];
                                foreach ($modalidades as $m) {
                                    if (isset($precios[$m][$clave])) {
                                        $partes[] = (count($modalidades) > 1 ? Catalogos::MODALIDADES_ASISTENCIA[$m] . ' ' : '')
                                            . dinero($precios[$m][$clave]);
                                    }
                                }
                                ?>
                            <option value="<?= $clave ?>"><?= e($etiqueta) ?><?= $partes ? ' · ' . e(implode(' / ', $partes)) : ' · sin precio' ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label" for="socio_id">Socio activo</label>
                        <select class="form-select" id="socio_id" name="socio_id">
                            <option value="">— Seleccionar —</option>
                            <?php foreach ($sociosActivos as $s): ?>
                            <option value="<?= (int) $s['id'] ?>"><?= e($s['numero'] . ' · ' . $s['nombre']) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <div class="form-text">El cargo se genera en su estado de cuenta.</div>
                    </div>
                    <button class="btn btn-primary w-100" name="tipo" value="socio" type="submit">
                        <i class="bi bi-person-check me-1"></i>Registrar socio
                    </button>

                    <hr>
                    <div class="mb-3">
                        <label class="form-label" for="asistente">Público en general</label>
                        <input class="form-control" id="asistente" name="asistente" maxlength="150" placeholder="Nombre completo">
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="email">Correo <span class="text-muted">(para enviarle el enlace)</span></label>
                        <input class="form-control" id="email" name="email" type="email" maxlength="120">
                    </div>
                    <button class="btn btn-outline-primary w-100" name="tipo" value="publico" type="submit">
                        <i class="bi bi-person-plus me-1"></i>Registrar público
                    </button>
                </form>
            </div>
        </div>

        <?php if ($modulos): ?>
        <div class="card mt-3">
            <div class="card-header bg-white fw-semibold">Módulos</div>
            <ul class="list-group list-group-flush">
                <?php foreach ($modulos as $mod): ?>
                <li class="list-group-item d-flex justify-content-between align-items-start gap-2">
                    <div>
                        <div class="small fw-semibold"><?= (int) $mod['orden'] ?>. <?= e($mod['nombre']) ?></div>
                        <div class="small text-muted">
                            <?= $mod['fecha'] ? e(fecha_corta($mod['fecha'])) : '' ?>
                            <?php if ($mod['hora_inicio']): ?> · <?= e(substr((string) $mod['hora_inicio'], 0, 5)) ?><?php endif; ?>
                        </div>
                    </div>
                    <span class="badge text-bg-light border text-nowrap"><?= e(number_format((float) $mod['puntos_dpc'], 2)) ?> pts</span>
                </li>
                <?php endforeach; ?>
            </ul>
        </div>
        <?php endif; ?>
    </div>

    <div class="col-12 col-lg-8">
        <div class="card">
            <div class="card-header bg-white fw-semibold">
                Asistentes registrados
                <span class="badge text-bg-secondary ms-1"><?= count($asistentes) ?><?= $evento['cupo'] !== null ? ' / ' . (int) $evento['cupo'] : '' ?></span>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Asistente</th>
                            <th>Categoría</th>
                            <th class="d-none d-md-table-cell">Modalidad</th>
                            <th class="d-none d-lg-table-cell">Registro</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if (!$asistentes): ?>
                        <tr><td colspan="5" class="text-center text-muted py-4">Aún no hay registros.</td></tr>
                    <?php endif; ?>
                    <?php foreach ($asistentes as $a): ?>
                        <tr>
                            <td>
                                <?= e($a['socio_id'] ? ($a['numero'] . ' · ' . $a['socio_nombre']) : $a['asistente']) ?>
                                <?php if ($a['email']): ?><div class="small text-muted"><?= e($a['email']) ?></div><?php endif; ?>
                            </td>
                            <td>
                                <span class="badge text-bg-<?= $a['tipo'] === 'socio' ? 'success' : 'info' ?>">
                                    <?= e(Catalogos::CATEGORIAS_ASISTENTE[$a['categoria']] ?? $a['categoria']) ?>
                                </span>
                            </td>
                            <td class="d-none d-md-table-cell small"><?= e(Catalogos::MODALIDADES_ASISTENCIA[$a['modalidad']] ?? $a['modalidad']) ?></td>
                            <td class="d-none d-lg-table-cell small text-muted"><?= e(fecha_corta(substr((string) $a['creado_en'], 0, 10))) ?></td>
                            <td class="text-end">
                                <form method="post" action="<?= e(url('registro/quitar/' . $idEvento . '/' . (int) $a['id'])) ?>"
                                      onsubmit="return confirm('¿Quitar este registro? El cargo del evento se cancelará.');">
                                    <?= Csrf::campo() ?>
                                    <button class="btn btn-sm btn-outline-danger" title="Quitar"><i class="bi bi-x-lg"></i></button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

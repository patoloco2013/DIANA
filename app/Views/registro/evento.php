<?php use Diana\Core\Csrf; ?>
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-1">
    <h1 class="h4 mb-0"><?= e($evento['nombre']) ?></h1>
    <a class="btn btn-outline-secondary btn-sm" href="<?= e(url('registro')) ?>">
        <i class="bi bi-arrow-left me-1"></i>Eventos
    </a>
</div>
<p class="text-muted mb-4">
    <?= e(fecha_corta($evento['fecha_inicio'])) ?>
    · <?= e($evento['sede'] ?? 'Sin sede') ?>
    · EPC: <?= e($evento['puntos_epc']) ?>
    · Socio: <?= e(dinero($evento['precio_socio'])) ?>
    · Público: <?= e(dinero($evento['precio_publico'])) ?>
</p>

<div class="row g-3">
    <div class="col-12 col-lg-4">
        <div class="card">
            <div class="card-header bg-white fw-semibold">Registrar asistente</div>
            <div class="card-body">
                <form method="post" action="<?= e(url('registro/agregar/' . $evento['id'])) ?>">
                    <?= Csrf::campo() ?>
                    <div class="mb-3">
                        <label class="form-label" for="socio_id">Socio activo</label>
                        <select class="form-select" id="socio_id" name="socio_id">
                            <option value="">— Seleccionar —</option>
                            <?php foreach ($sociosActivos as $s): ?>
                            <option value="<?= (int) $s['id'] ?>"><?= e($s['numero'] . ' · ' . $s['nombre']) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <div class="form-text">Se generará el cargo de <?= e(dinero($evento['precio_socio'])) ?> en su estado de cuenta.</div>
                    </div>
                    <button class="btn btn-primary w-100" name="tipo" value="socio" type="submit">
                        <i class="bi bi-person-check me-1"></i>Registrar socio
                    </button>
                    <hr>
                    <div class="mb-3">
                        <label class="form-label" for="asistente">Público en general</label>
                        <input class="form-control" id="asistente" name="asistente" maxlength="150" placeholder="Nombre completo">
                    </div>
                    <button class="btn btn-outline-primary w-100" name="tipo" value="publico" type="submit">
                        <i class="bi bi-person-plus me-1"></i>Registrar público
                    </button>
                </form>
            </div>
        </div>
    </div>

    <div class="col-12 col-lg-8">
        <div class="card">
            <div class="card-header bg-white fw-semibold">
                Asistentes registrados
                <span class="badge text-bg-secondary ms-1"><?= count($asistentes) ?><?= $evento['cupo'] !== null ? ' / ' . (int) $evento['cupo'] : '' ?></span>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead><tr><th>Asistente</th><th>Tipo</th><th class="d-none d-md-table-cell">Fecha registro</th><th></th></tr></thead>
                    <tbody>
                    <?php if (!$asistentes): ?>
                        <tr><td colspan="4" class="text-center text-muted py-4">Aún no hay registros.</td></tr>
                    <?php endif; ?>
                    <?php foreach ($asistentes as $a): ?>
                        <tr>
                            <td>
                                <?= e($a['socio_id'] ? ($a['numero'] . ' · ' . $a['socio_nombre']) : $a['asistente']) ?>
                            </td>
                            <td><span class="badge text-bg-<?= $a['tipo'] === 'socio' ? 'success' : 'info' ?>"><?= e($a['tipo']) ?></span></td>
                            <td class="d-none d-md-table-cell"><?= e(fecha_corta(substr((string) $a['creado_en'], 0, 10))) ?></td>
                            <td class="text-end">
                                <form method="post" action="<?= e(url('registro/quitar/' . $evento['id'] . '/' . $a['id'])) ?>"
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

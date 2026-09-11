<?php
use Diana\Core\Catalogos;
use Diana\Core\Csrf;

$idEvento = (int) $evento['id'];
$totalAsistio = 0;
foreach ($asistentes as $a) {
    $totalAsistio += (int) $a['asistio'];
}
?>
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-1">
    <h1 class="h4 mb-0"><?= e($evento['nombre']) ?></h1>
    <div class="d-flex gap-2">
        <a class="btn btn-outline-secondary btn-sm" href="<?= e(url('registro')) ?>">
            <i class="bi bi-arrow-left me-1"></i>Eventos
        </a>
    </div>
</div>
<p class="text-muted mb-3">
    <?= e(fecha_corta($evento['fecha_inicio'])) ?><?= $evento['fecha_fin'] ? ' al ' . e(fecha_corta($evento['fecha_fin'])) : '' ?>
    · <?= e(number_format($puntosTotales, 2)) ?> pts DPC al asistir
</p>

<ul class="nav nav-pills mb-3">
    <li class="nav-item">
        <a class="nav-link" href="<?= e(url('registro/evento/' . $idEvento)) ?>">
            <i class="bi bi-clipboard2-check me-1"></i>1. Confirmaciones
        </a>
    </li>
    <li class="nav-item">
        <span class="nav-link active"><i class="bi bi-person-check me-1"></i>2. Asistencia</span>
    </li>
</ul>

<div class="card">
    <div class="card-header bg-white fw-semibold d-flex justify-content-between align-items-center">
        <span>Pase de lista</span>
        <span class="badge text-bg-success"><?= $totalAsistio ?> de <?= count($asistentes) ?> asistieron</span>
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead>
                <tr>
                    <th>Asistente</th>
                    <th class="d-none d-md-table-cell">Categoría</th>
                    <th class="d-none d-lg-table-cell">Lugar / comentarios</th>
                    <th class="text-end d-none d-md-table-cell">Puntos DPC</th>
                    <th class="text-end">Asistió</th>
                </tr>
            </thead>
            <tbody>
            <?php if (!$asistentes): ?>
                <tr><td colspan="5" class="text-center text-muted py-4">No hay confirmados para pasar lista.</td></tr>
            <?php endif; ?>
            <?php foreach ($asistentes as $a): ?>
                <tr class="<?= (int) $a['asistio'] === 1 ? '' : 'table-light' ?>">
                    <td>
                        <?= e($a['socio_id'] ? ($a['numero'] . ' · ' . $a['socio_nombre']) : $a['asistente']) ?>
                        <div class="small text-muted d-md-none"><?= e(Catalogos::CATEGORIAS_ASISTENTE[$a['categoria']] ?? $a['categoria']) ?></div>
                    </td>
                    <td class="d-none d-md-table-cell">
                        <span class="badge text-bg-<?= $a['tipo'] === 'socio' ? 'success' : 'info' ?>">
                            <?= e(Catalogos::CATEGORIAS_ASISTENTE[$a['categoria']] ?? $a['categoria']) ?>
                        </span>
                    </td>
                    <td class="d-none d-lg-table-cell">
                        <form method="post" action="<?= e(url('registro/actualizarAsistencia/' . $idEvento . '/' . (int) $a['id'])) ?>" class="d-flex gap-1">
                            <?= Csrf::campo() ?>
                            <input class="form-control form-control-sm" type="text" name="lugar" placeholder="Lugar/mesa" maxlength="50"
                                   style="max-width: 100px;" value="<?= e($a['lugar'] ?? '') ?>">
                            <input class="form-control form-control-sm" type="text" name="comentarios" placeholder="Comentarios" maxlength="255"
                                   value="<?= e($a['comentarios'] ?? '') ?>">
                            <button class="btn btn-sm btn-outline-secondary text-nowrap" type="submit" title="Guardar"><i class="bi bi-check-lg"></i></button>
                        </form>
                    </td>
                    <td class="text-end d-none d-md-table-cell">
                        <?= (int) $a['asistio'] === 1 ? e(number_format((float) $a['puntos_dpc'], 2)) : '—' ?>
                    </td>
                    <td class="text-end text-nowrap">
                        <?php if ((int) $a['asistio'] === 1): ?>
                            <div class="small text-muted mb-1 d-none d-xl-block">
                                <?= e(fecha_corta(substr((string) $a['fecha_asistio'], 0, 10))) ?>
                                <?= e(substr((string) $a['fecha_asistio'], 11, 5)) ?>
                            </div>
                            <form method="post" action="<?= e(url('registro/quitarAsistencia/' . $idEvento . '/' . (int) $a['id'])) ?>">
                                <?= Csrf::campo() ?>
                                <button class="btn btn-sm btn-success" type="submit"><i class="bi bi-check-circle-fill me-1"></i>Asistió</button>
                            </form>
                        <?php else: ?>
                            <form method="post" action="<?= e(url('registro/marcarAsistencia/' . $idEvento . '/' . (int) $a['id'])) ?>">
                                <?= Csrf::campo() ?>
                                <button class="btn btn-sm btn-outline-secondary" type="submit"><i class="bi bi-circle me-1"></i>Dar asistencia</button>
                            </form>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

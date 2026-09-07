<?php use Diana\Core\Catalogos; ?>
<h1 class="h4 mb-4">Registro de eventos</h1>

<div class="card">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead>
                <tr>
                    <th>Evento</th><th>Fecha</th>
                    <th class="d-none d-md-table-cell">Modalidad</th>
                    <th class="d-none d-lg-table-cell text-end">DPC</th>
                    <th class="text-end">Registrados</th><th class="text-end">Cupo</th><th></th>
                </tr>
            </thead>
            <tbody>
            <?php if (!$eventos): ?>
                <tr><td colspan="7" class="text-center text-muted py-4">No hay eventos publicados.</td></tr>
            <?php endif; ?>
            <?php foreach ($eventos as $ev): ?>
                <tr>
                    <td>
                        <?= e($ev['nombre']) ?>
                        <?php if ($ev['sede']): ?><div class="small text-muted"><?= e($ev['sede']) ?></div><?php endif; ?>
                    </td>
                    <td class="text-nowrap"><?= e(fecha_corta($ev['fecha_inicio'])) ?></td>
                    <td class="d-none d-md-table-cell small">
                        <?= e($modalidades[$ev['modalidad']] ?? $ev['modalidad']) ?>
                    </td>
                    <td class="d-none d-lg-table-cell text-end"><?= e(number_format((float) $ev['puntos_dpc'], 2)) ?></td>
                    <td class="text-end"><?= (int) $ev['registrados'] ?></td>
                    <td class="text-end"><?= $ev['cupo'] !== null ? (int) $ev['cupo'] : '—' ?></td>
                    <td class="text-end">
                        <a class="btn btn-sm btn-primary text-nowrap" href="<?= e(url('registro/evento/' . (int) $ev['id'])) ?>">
                            <i class="bi bi-clipboard2-check me-1"></i>Tomar registro
                        </a>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

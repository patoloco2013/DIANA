<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h4 mb-0">Reporte de eventos</h1>
    <div class="d-flex gap-2">
        <button class="btn btn-outline-secondary btn-sm" onclick="window.print()"><i class="bi bi-printer me-1"></i>Imprimir</button>
        <a class="btn btn-outline-secondary btn-sm" href="<?= e(url('reportes')) ?>"><i class="bi bi-arrow-left me-1"></i>Reportes</a>
    </div>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table table-striped align-middle mb-0">
            <thead>
                <tr>
                    <th>Evento</th><th>Fecha</th><th>Estatus</th>
                    <th class="text-end">Socios</th><th class="text-end">Público</th>
                    <th class="text-end">Facturado</th><th class="text-end">Cobrado</th>
                </tr>
            </thead>
            <tbody>
            <?php if (!$filas): ?>
                <tr><td colspan="7" class="text-center text-muted py-4">Sin eventos.</td></tr>
            <?php endif; ?>
            <?php foreach ($filas as $f): ?>
                <tr>
                    <td><?= e($f['nombre']) ?></td>
                    <td class="text-nowrap"><?= e(fecha_corta($f['fecha_inicio'])) ?></td>
                    <td><?= e($f['estatus']) ?></td>
                    <td class="text-end"><?= (int) $f['socios'] ?></td>
                    <td class="text-end"><?= (int) $f['publico'] ?></td>
                    <td class="text-end"><?= e(dinero($f['facturado'])) ?></td>
                    <td class="text-end"><?= e(dinero($f['cobrado'])) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

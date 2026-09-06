<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h4 mb-0">Morosidad</h1>
    <div class="d-flex gap-2">
        <button class="btn btn-outline-secondary btn-sm" onclick="window.print()"><i class="bi bi-printer me-1"></i>Imprimir</button>
        <a class="btn btn-outline-secondary btn-sm" href="<?= e(url('reportes')) ?>"><i class="bi bi-arrow-left me-1"></i>Reportes</a>
    </div>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table table-striped align-middle mb-0">
            <thead><tr><th>No.</th><th>Socio</th><th>Concepto</th><th>Vencimiento</th><th class="text-end">Días</th><th class="text-end">Importe</th></tr></thead>
            <tbody>
            <?php if (!$filas): ?>
                <tr><td colspan="6" class="text-center text-muted py-4">Sin cargos vencidos. 🎉</td></tr>
            <?php endif; ?>
            <?php $total = 0.0; ?>
            <?php foreach ($filas as $f): $total += (float) $f['importe']; ?>
                <tr>
                    <td><?= e($f['numero']) ?></td>
                    <td><?= e($f['nombre']) ?></td>
                    <td><?= e($f['concepto']) ?></td>
                    <td class="text-nowrap"><?= e(fecha_corta($f['fecha_vencimiento'])) ?></td>
                    <td class="text-end text-danger fw-semibold"><?= (int) $f['dias_vencido'] ?></td>
                    <td class="text-end"><?= e(dinero($f['importe'])) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
            <?php if ($filas): ?>
            <tfoot class="table-light fw-semibold">
                <tr><td colspan="5">Total vencido</td><td class="text-end"><?= e(dinero($total)) ?></td></tr>
            </tfoot>
            <?php endif; ?>
        </table>
    </div>
</div>

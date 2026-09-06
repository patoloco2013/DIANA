<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
    <h1 class="h4 mb-0">Cobranza</h1>
    <div class="d-flex gap-2">
        <button class="btn btn-outline-secondary btn-sm" onclick="window.print()"><i class="bi bi-printer me-1"></i>Imprimir</button>
        <a class="btn btn-outline-secondary btn-sm" href="<?= e(url('reportes')) ?>"><i class="bi bi-arrow-left me-1"></i>Reportes</a>
    </div>
</div>

<form method="get" action="<?= e(url('reportes/cobranza')) ?>" class="row g-2 align-items-end mb-4" style="max-width: 520px;">
    <input type="hidden" name="r" value="reportes/cobranza">
    <div class="col">
        <label class="form-label" for="desde">Desde</label>
        <input class="form-control" id="desde" name="desde" type="date" value="<?= e($desde) ?>">
    </div>
    <div class="col">
        <label class="form-label" for="hasta">Hasta</label>
        <input class="form-control" id="hasta" name="hasta" type="date" value="<?= e($hasta) ?>">
    </div>
    <div class="col-auto">
        <button class="btn btn-primary" type="submit">Consultar</button>
    </div>
</form>

<div class="card">
    <div class="table-responsive">
        <table class="table table-striped align-middle mb-0">
            <thead><tr><th>Fecha</th><th>Socio</th><th>Concepto</th><th class="d-none d-md-table-cell">Forma</th><th class="d-none d-md-table-cell">Ref.</th><th class="text-end">Importe</th></tr></thead>
            <tbody>
            <?php if (!$filas): ?>
                <tr><td colspan="6" class="text-center text-muted py-4">Sin pagos en el periodo.</td></tr>
            <?php endif; ?>
            <?php foreach ($filas as $f): ?>
                <tr>
                    <td class="text-nowrap"><?= e(fecha_corta($f['fecha'])) ?></td>
                    <td><?= e($f['numero'] . ' · ' . $f['nombre']) ?></td>
                    <td><?= e($f['concepto']) ?></td>
                    <td class="d-none d-md-table-cell"><?= e($f['forma_pago']) ?></td>
                    <td class="d-none d-md-table-cell"><?= e($f['referencia']) ?></td>
                    <td class="text-end"><?= e(dinero($f['importe'])) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
            <tfoot class="table-light fw-semibold">
                <tr><td colspan="5">Total cobrado (<?= count($filas) ?> pagos)</td><td class="text-end"><?= e(dinero($total)) ?></td></tr>
            </tfoot>
        </table>
    </div>
</div>

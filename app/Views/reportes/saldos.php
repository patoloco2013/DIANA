<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h4 mb-0">Saldos de socios</h1>
    <div class="d-flex gap-2">
        <button class="btn btn-outline-secondary btn-sm" onclick="window.print()"><i class="bi bi-printer me-1"></i>Imprimir</button>
        <a class="btn btn-outline-secondary btn-sm" href="<?= e(url('reportes')) ?>"><i class="bi bi-arrow-left me-1"></i>Reportes</a>
    </div>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table table-striped align-middle mb-0">
            <thead><tr><th>No.</th><th>Socio</th><th>Estatus</th><th class="text-end">Cargos</th><th class="text-end">Pagos</th><th class="text-end">Saldo</th></tr></thead>
            <tbody>
            <?php $tCargos = $tPagos = $tSaldo = 0.0; ?>
            <?php foreach ($filas as $f): $tCargos += (float) $f['cargos']; $tPagos += (float) $f['pagos']; $tSaldo += (float) $f['saldo']; ?>
                <tr>
                    <td><?= e($f['numero']) ?></td>
                    <td><?= e($f['nombre']) ?></td>
                    <td><?= e($f['estatus']) ?></td>
                    <td class="text-end"><?= e(dinero($f['cargos'])) ?></td>
                    <td class="text-end"><?= e(dinero($f['pagos'])) ?></td>
                    <td class="text-end <?= (float) $f['saldo'] > 0 ? 'text-danger fw-semibold' : '' ?>"><?= e(dinero($f['saldo'])) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
            <tfoot class="table-light fw-semibold">
                <tr>
                    <td colspan="3">Totales (<?= count($filas) ?> socios)</td>
                    <td class="text-end"><?= e(dinero($tCargos)) ?></td>
                    <td class="text-end"><?= e(dinero($tPagos)) ?></td>
                    <td class="text-end"><?= e(dinero($tSaldo)) ?></td>
                </tr>
            </tfoot>
        </table>
    </div>
</div>

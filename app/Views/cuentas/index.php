<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
    <h1 class="h4 mb-0">Cuentas de socios</h1>
    <form method="get" action="<?= e(url('cuentas')) ?>" class="d-flex gap-2">
        <input type="hidden" name="r" value="cuentas">
        <input class="form-control" type="search" name="q" value="<?= e($q) ?>"
               placeholder="Buscar socio" style="min-width: 220px;">
        <button class="btn btn-outline-secondary" type="submit"><i class="bi bi-search"></i></button>
    </form>
</div>

<p class="text-muted small">Sin búsqueda se muestran solo socios con saldo distinto de cero.</p>

<div class="card">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead><tr><th>No.</th><th>Socio</th><th>Estatus</th><th class="text-end">Saldo</th><th></th></tr></thead>
            <tbody>
            <?php if (!$filas): ?>
                <tr><td colspan="5" class="text-center text-muted py-4">Sin resultados.</td></tr>
            <?php endif; ?>
            <?php foreach ($filas as $f): ?>
                <tr>
                    <td><?= e($f['numero']) ?></td>
                    <td><?= e($f['nombre']) ?></td>
                    <td><span class="badge text-bg-<?= $f['estatus'] === 'activo' ? 'success' : 'secondary' ?>"><?= e($f['estatus']) ?></span></td>
                    <td class="text-end <?= (float) $f['saldo'] > 0 ? 'text-danger fw-semibold' : 'text-success' ?>">
                        <?= e(dinero($f['saldo'])) ?>
                    </td>
                    <td class="text-end">
                        <a class="btn btn-sm btn-primary" href="<?= e(url('cuentas/socio/' . $f['id'])) ?>">
                            <i class="bi bi-wallet2 me-1"></i>Estado de cuenta
                        </a>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

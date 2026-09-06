<?php use Diana\Core\Csrf; ?>
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
    <h1 class="h4 mb-0">Socios</h1>
    <div class="d-flex gap-2">
        <form method="get" action="<?= e(url('socios')) ?>" class="d-flex gap-2">
            <input type="hidden" name="r" value="socios">
            <input class="form-control" type="search" name="q" value="<?= e($q) ?>"
                   placeholder="Nombre, número, RFC o correo" style="min-width: 220px;">
            <button class="btn btn-outline-secondary" type="submit"><i class="bi bi-search"></i></button>
        </form>
        <a class="btn btn-primary text-nowrap" href="<?= e(url('socios/crear')) ?>">
            <i class="bi bi-person-plus me-1"></i>Nuevo
        </a>
    </div>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead>
                <tr>
                    <th>No.</th><th>Nombre</th><th class="d-none d-md-table-cell">Correo</th>
                    <th class="d-none d-lg-table-cell">Teléfono</th><th>Estatus</th>
                    <th class="text-end">Saldo</th><th class="text-end">Acciones</th>
                </tr>
            </thead>
            <tbody>
            <?php if (!$socios): ?>
                <tr><td colspan="7" class="text-center text-muted py-4">Sin resultados.</td></tr>
            <?php endif; ?>
            <?php foreach ($socios as $s): ?>
                <tr>
                    <td><?= e($s['numero']) ?></td>
                    <td><?= e(trim(($s['titulo'] ?? '') . ' ' . $s['nombre'])) ?></td>
                    <td class="d-none d-md-table-cell"><?= e($s['email']) ?></td>
                    <td class="d-none d-lg-table-cell"><?= e($s['telefono']) ?></td>
                    <td>
                        <?php $color = ['activo' => 'success', 'suspendido' => 'warning', 'baja' => 'secondary'][$s['estatus']] ?? 'secondary'; ?>
                        <span class="badge text-bg-<?= $color ?>"><?= e($s['estatus']) ?></span>
                    </td>
                    <td class="text-end <?= (float) $s['saldo'] > 0 ? 'text-danger fw-semibold' : '' ?>">
                        <?= e(dinero($s['saldo'])) ?>
                    </td>
                    <td class="text-end text-nowrap">
                        <a class="btn btn-sm btn-outline-secondary" title="Estado de cuenta"
                           href="<?= e(url('cuentas/socio/' . $s['id'])) ?>"><i class="bi bi-wallet2"></i></a>
                        <a class="btn btn-sm btn-outline-primary" title="Editar"
                           href="<?= e(url('socios/editar/' . $s['id'])) ?>"><i class="bi bi-pencil"></i></a>
                        <?php if ($s['estatus'] !== 'baja'): ?>
                        <form class="d-inline" method="post" action="<?= e(url('socios/baja/' . $s['id'])) ?>"
                              onsubmit="return confirm('¿Dar de baja a este socio?');">
                            <?= Csrf::campo() ?>
                            <button class="btn btn-sm btn-outline-danger" title="Baja"><i class="bi bi-person-dash"></i></button>
                        </form>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

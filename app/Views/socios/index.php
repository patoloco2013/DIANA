<?php use Diana\Core\Csrf; ?>
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
    <h1 class="h4 mb-0">Socios</h1>
    <div class="d-flex flex-wrap gap-2">
        <form method="get" action="<?= e(url('socios')) ?>" class="d-flex gap-2">
            <input type="hidden" name="r" value="socios">
            <input class="form-control" type="search" name="q" value="<?= e($q) ?>"
                   placeholder="Nombre, ID, RFC, correo o celular" style="min-width: 220px;">
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
                    <th>ID</th><th>Socio</th>
                    <th class="d-none d-lg-table-cell">Tipo</th>
                    <th class="d-none d-md-table-cell">Contacto</th>
                    <th>Estatus</th>
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
                    <td>
                        <div class="d-flex align-items-center gap-2">
                            <?php if (!empty($s['foto'])): ?>
                                <img class="diana-avatar-sm" src="<?= e(url('socios/foto/' . (int) $s['id'])) ?>" alt="">
                            <?php else: ?>
                                <span class="diana-avatar-sm diana-avatar-ini"><?= e(iniciales($s['nombre_completo'])) ?></span>
                            <?php endif; ?>
                            <div>
                                <a class="fw-semibold text-decoration-none" href="<?= e(url('socios/editar/' . (int) $s['id'])) ?>">
                                    <?= e(trim(($s['titulo'] ?? '') . ' ' . $s['nombre_completo'])) ?>
                                </a>
                                <?php if ($s['rfc']): ?><div class="small text-muted"><?= e($s['rfc']) ?></div><?php endif; ?>
                            </div>
                        </div>
                    </td>
                    <td class="d-none d-lg-table-cell small"><?= e($tiposSocio[$s['tipo']] ?? $s['tipo']) ?></td>
                    <td class="d-none d-md-table-cell small">
                        <?php if ($s['celular']): ?><div><i class="bi bi-phone me-1 text-muted"></i><?= e($s['celular']) ?></div><?php endif; ?>
                        <?php if ($s['email']): ?><div><i class="bi bi-envelope me-1 text-muted"></i><?= e($s['email']) ?></div><?php endif; ?>
                    </td>
                    <td>
                        <?php $color = ['activo' => 'success', 'suspendido' => 'warning', 'baja' => 'secondary'][$s['estatus']] ?? 'secondary'; ?>
                        <span class="badge text-bg-<?= $color ?>"><?= e($s['estatus']) ?></span>
                    </td>
                    <td class="text-end <?= (float) $s['saldo'] > 0 ? 'text-danger fw-semibold' : '' ?>">
                        <?= e(dinero($s['saldo'])) ?>
                    </td>
                    <td class="text-end text-nowrap">
                        <a class="btn btn-sm btn-outline-secondary" title="Estado de cuenta"
                           href="<?= e(url('cuentas/socio/' . (int) $s['id'])) ?>"><i class="bi bi-wallet2"></i></a>
                        <a class="btn btn-sm btn-outline-primary" title="Editar"
                           href="<?= e(url('socios/editar/' . (int) $s['id'])) ?>"><i class="bi bi-pencil"></i></a>
                        <?php if ($s['estatus'] !== 'baja'): ?>
                        <form class="d-inline" method="post" action="<?= e(url('socios/baja/' . (int) $s['id'])) ?>"
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

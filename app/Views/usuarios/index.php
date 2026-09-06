<?php use Diana\Core\Csrf; ?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h4 mb-0">Usuarios del sistema</h1>
    <a class="btn btn-primary" href="<?= e(url('usuarios/crear')) ?>"><i class="bi bi-person-plus me-1"></i>Nuevo</a>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead>
                <tr>
                    <th>Usuario</th><th>Nombre</th><th>Rol</th>
                    <th class="d-none d-md-table-cell">Colegio</th>
                    <th class="d-none d-lg-table-cell">Último acceso</th>
                    <th>Activo</th><th class="text-end">Acciones</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($usuarios as $u): ?>
                <tr>
                    <td class="fw-semibold"><?= e($u['usuario']) ?></td>
                    <td><?= e($u['nombre']) ?></td>
                    <td><?= e($u['rol_nombre']) ?></td>
                    <td class="d-none d-md-table-cell"><?= e($u['colegio_nombre'] ?? 'Todos') ?></td>
                    <td class="d-none d-lg-table-cell small text-muted"><?= e($u['ultimo_acceso'] ?? 'Nunca') ?></td>
                    <td>
                        <span class="badge text-bg-<?= (int) $u['activo'] === 1 ? 'success' : 'secondary' ?>">
                            <?= (int) $u['activo'] === 1 ? 'Sí' : 'No' ?>
                        </span>
                    </td>
                    <td class="text-end text-nowrap">
                        <a class="btn btn-sm btn-outline-primary" title="Editar"
                           href="<?= e(url('usuarios/editar/' . $u['id'])) ?>"><i class="bi bi-pencil"></i></a>
                        <?php if ((int) $u['activo'] === 1): ?>
                        <form class="d-inline" method="post" action="<?= e(url('usuarios/desactivar/' . $u['id'])) ?>"
                              onsubmit="return confirm('¿Desactivar esta cuenta?');">
                            <?= Csrf::campo() ?>
                            <button class="btn btn-sm btn-outline-danger" title="Desactivar"><i class="bi bi-person-x"></i></button>
                        </form>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

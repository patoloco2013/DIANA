<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h4 mb-0">Colegios</h1>
    <a class="btn btn-primary" href="<?= e(url('colegios/crear')) ?>"><i class="bi bi-building-add me-1"></i>Nuevo colegio</a>
</div>

<div class="row g-3">
    <?php foreach ($colegios as $c): ?>
    <div class="col-12 col-md-6 col-xl-4">
        <div class="card h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <span class="badge mb-2" style="background: <?= e($c['color_primario']) ?>;"><?= e($c['clave']) ?></span>
                        <h2 class="h6 mb-1"><?= e($c['nombre_corto']) ?></h2>
                        <div class="small text-muted"><?= e($c['nombre']) ?></div>
                        <div class="small text-muted"><?= e($c['ciudad']) ?></div>
                    </div>
                    <span class="badge text-bg-<?= (int) $c['activo'] === 1 ? 'success' : 'secondary' ?>">
                        <?= (int) $c['activo'] === 1 ? 'Activo' : 'Inactivo' ?>
                    </span>
                </div>
                <div class="d-flex gap-3 mt-3 small text-muted">
                    <span><i class="bi bi-people me-1"></i><?= (int) $c['socios'] ?> socios</span>
                    <span><i class="bi bi-person-gear me-1"></i><?= (int) $c['usuarios'] ?> usuarios</span>
                </div>
            </div>
            <div class="card-footer bg-white text-end">
                <a class="btn btn-sm btn-outline-primary" href="<?= e(url('colegios/editar/' . $c['id'])) ?>">
                    <i class="bi bi-pencil me-1"></i>Editar
                </a>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>

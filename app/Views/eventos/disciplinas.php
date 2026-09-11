<?php use Diana\Core\Csrf; ?>
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-1">
    <h1 class="h4 mb-0">Disciplinas DPC</h1>
    <a class="btn btn-outline-secondary btn-sm" href="<?= e(url('eventos')) ?>">
        <i class="bi bi-arrow-left me-1"></i>Eventos
    </a>
</div>
<p class="text-muted mb-4">
    Catálogo de disciplinas para repartir puntos DPC en eventos y módulos.
    Una disciplina nunca se elimina —solo se desactiva— para no romper los
    puntos ya otorgados en eventos pasados.
</p>

<div class="row g-4">
    <div class="col-12 col-lg-4">
        <h2 class="h6 fw-semibold mb-3">Nueva disciplina</h2>
        <form method="post" action="<?= e(url('disciplinas/crear')) ?>">
            <?= Csrf::campo() ?>
            <div class="mb-3">
                <label class="form-label" for="nombre">Nombre *</label>
                <input class="form-control" id="nombre" name="nombre" maxlength="100" required placeholder="Ej. Sostenibilidad">
            </div>
            <button class="btn btn-primary w-100" type="submit"><i class="bi bi-plus-lg me-1"></i>Agregar</button>
        </form>
    </div>

    <div class="col-12 col-lg-8">
        <h2 class="h6 fw-semibold mb-3">Catálogo <span class="badge text-bg-secondary"><?= count($disciplinas) ?></span></h2>
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead><tr><th>Nombre</th><th class="text-end d-none d-md-table-cell">Usos</th><th>Estatus</th><th class="text-end">Acciones</th></tr></thead>
                <tbody>
                <?php foreach ($disciplinas as $d): ?>
                    <tr class="<?= (int) $d['activo'] === 0 ? 'text-muted' : '' ?>">
                        <td style="min-width: 220px;">
                            <form method="post" action="<?= e(url('disciplinas/editar/' . (int) $d['id'])) ?>" class="d-flex gap-2">
                                <?= Csrf::campo() ?>
                                <input class="form-control form-control-sm" name="nombre" maxlength="100" required value="<?= e($d['nombre']) ?>">
                                <button class="btn btn-sm btn-outline-primary text-nowrap" type="submit" title="Guardar nombre"><i class="bi bi-check-lg"></i></button>
                            </form>
                        </td>
                        <td class="text-end d-none d-md-table-cell">
                            <span class="badge text-bg-light border"><?= (int) $d['usos'] ?></span>
                        </td>
                        <td>
                            <span class="badge text-bg-<?= (int) $d['activo'] === 1 ? 'success' : 'secondary' ?>">
                                <?= (int) $d['activo'] === 1 ? 'Activa' : 'Inactiva' ?>
                            </span>
                        </td>
                        <td class="text-end">
                            <form method="post" action="<?= e(url('disciplinas/alternar/' . (int) $d['id'])) ?>">
                                <?= Csrf::campo() ?>
                                <button class="btn btn-sm btn-outline-<?= (int) $d['activo'] === 1 ? 'danger' : 'success' ?>" type="submit">
                                    <?= (int) $d['activo'] === 1 ? 'Desactivar' : 'Reactivar' ?>
                                </button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

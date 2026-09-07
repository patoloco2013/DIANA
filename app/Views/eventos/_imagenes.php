<?php
/** Pestaña Imágenes del evento. Variables: $evento, $imagenes, $maxMb */
use Diana\Core\Csrf;

$idEvento = (int) $evento['id'];
?>
<div class="row g-4">
    <div class="col-12 col-lg-4">
        <h2 class="h6 fw-semibold mb-3">Agregar imagen</h2>
        <form method="post" action="<?= e(url('eventos/subirImagen/' . $idEvento)) ?>" enctype="multipart/form-data">
            <?= Csrf::campo() ?>
            <div class="mb-3">
                <label class="form-label" for="img_archivo">Imagen *</label>
                <input class="form-control" id="img_archivo" name="imagen" type="file" required
                       accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp">
                <div class="form-text">JPG, PNG o WEBP · máximo <?= (int) $maxMb ?> MB.</div>
            </div>
            <div class="mb-3">
                <label class="form-label" for="img_titulo">Título</label>
                <input class="form-control" id="img_titulo" name="titulo" maxlength="150" placeholder="Opcional">
            </div>
            <div class="form-check mb-3">
                <input class="form-check-input" type="checkbox" id="img_principal" name="principal" value="1"
                       <?= $imagenes ? '' : 'checked disabled' ?>>
                <label class="form-check-label" for="img_principal">
                    Usar como imagen principal
                    <?php if (!$imagenes): ?><span class="text-muted">(la primera siempre lo es)</span><?php endif; ?>
                </label>
            </div>
            <button class="btn btn-primary w-100" type="submit"><i class="bi bi-cloud-arrow-up me-1"></i>Subir imagen</button>
        </form>
    </div>

    <div class="col-12 col-lg-8">
        <h2 class="h6 fw-semibold mb-3">Galería <span class="badge text-bg-secondary"><?= count($imagenes) ?></span></h2>
        <?php if (!$imagenes): ?>
            <p class="text-muted">Sin imágenes. La primera que suba será la principal del evento.</p>
        <?php endif; ?>
        <div class="row g-3">
            <?php foreach ($imagenes as $img): ?>
            <div class="col-6 col-md-4">
                <div class="card h-100 <?= (int) $img['principal'] === 1 ? 'border-success' : '' ?>">
                    <img class="diana-galeria-img card-img-top"
                         src="<?= e(url('eventos/imagen/' . $idEvento . '/' . (int) $img['id'])) ?>"
                         alt="<?= e($img['titulo'] ?? $img['nombre_original']) ?>">
                    <div class="card-body p-2">
                        <?php if ((int) $img['principal'] === 1): ?>
                            <span class="badge text-bg-success mb-1"><i class="bi bi-star-fill me-1"></i>Principal</span>
                        <?php endif; ?>
                        <div class="small text-truncate" title="<?= e($img['titulo'] ?? $img['nombre_original']) ?>">
                            <?= e($img['titulo'] ?: $img['nombre_original']) ?>
                        </div>
                    </div>
                    <div class="card-footer bg-white p-2 d-flex gap-1 justify-content-end">
                        <?php if ((int) $img['principal'] !== 1): ?>
                        <form method="post" action="<?= e(url('eventos/principalImagen/' . $idEvento . '/' . (int) $img['id'])) ?>">
                            <?= Csrf::campo() ?>
                            <button class="btn btn-sm btn-outline-success" title="Hacer principal"><i class="bi bi-star"></i></button>
                        </form>
                        <?php endif; ?>
                        <form method="post" action="<?= e(url('eventos/eliminarImagen/' . $idEvento . '/' . (int) $img['id'])) ?>"
                              onsubmit="return confirm('¿Eliminar esta imagen?');">
                            <?= Csrf::campo() ?>
                            <button class="btn btn-sm btn-outline-danger" title="Eliminar"><i class="bi bi-trash"></i></button>
                        </form>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

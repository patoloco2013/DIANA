<?php
/** Pestaña Constancia. Variables: $constancia (?array) */
use Diana\Core\Csrf;

$c = $constancia ?? [];
$v = fn(string $campo, string $porDefecto = '') => e($c[$campo] ?? $porDefecto);
?>
<div class="row g-4">
    <div class="col-12 col-lg-8">
        <h2 class="h6 fw-semibold mb-1">Plantilla de constancia</h2>
        <p class="small text-muted">
            Por ahora es una sola plantilla general para todo el colegio; una plantilla distinta
            por evento se agregará más adelante. Esto guarda el texto y formato; la generación del
            PDF en sí es un módulo aparte, pendiente de construir.
        </p>
        <form method="post" action="<?= e(url('configuracion/guardarConstancia')) ?>" enctype="multipart/form-data">
            <?= Csrf::campo() ?>
            <div class="row g-3">
                <div class="col-12 col-md-8">
                    <label class="form-label" for="cn_titulo">Título</label>
                    <input class="form-control" id="cn_titulo" name="titulo" maxlength="150" value="<?= $v('titulo', 'Constancia de participación') ?>">
                </div>
                <div class="col-12 col-md-4">
                    <label class="form-label" for="cn_orientacion">Orientación de la hoja</label>
                    <select class="form-select" id="cn_orientacion" name="orientacion">
                        <option value="horizontal" <?= ($c['orientacion'] ?? 'horizontal') === 'horizontal' ? 'selected' : '' ?>>Horizontal</option>
                        <option value="vertical" <?= ($c['orientacion'] ?? '') === 'vertical' ? 'selected' : '' ?>>Vertical</option>
                    </select>
                </div>
                <div class="col-12">
                    <label class="form-label" for="cn_cuerpo">Texto del cuerpo</label>
                    <textarea class="form-control" id="cn_cuerpo" name="cuerpo" rows="6"><?= $v('cuerpo') ?></textarea>
                    <div class="form-text">
                        Puede usar: <code>{socio}</code>, <code>{evento}</code>, <code>{fecha_inicio}</code>,
                        <code>{fecha_fin}</code>, <code>{puntos_dpc}</code>, <code>{colegio}</code>.
                    </div>
                </div>
                <div class="col-12">
                    <label class="form-label" for="cn_imagen">Logo o firma <span class="text-muted">(opcional)</span></label>
                    <input class="form-control" id="cn_imagen" name="imagen" type="file" accept=".jpg,.jpeg,.png,.webp">
                    <?php if (!empty($c['imagen'])): ?>
                    <div class="form-check mt-2">
                        <input class="form-check-input" type="checkbox" id="cn_quitar" name="quitar_imagen" value="1">
                        <label class="form-check-label" for="cn_quitar">Quitar la imagen actual</label>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            <button class="btn btn-primary mt-3" type="submit"><i class="bi bi-check-lg me-1"></i>Guardar plantilla</button>
        </form>
    </div>

    <div class="col-12 col-lg-4">
        <h2 class="h6 fw-semibold mb-3">Vista previa</h2>
        <div class="card <?= ($c['orientacion'] ?? 'horizontal') === 'vertical' ? '' : '' ?>">
            <div class="card-body text-center">
                <?php if (!empty($c['imagen'])): ?>
                    <img src="<?= e(url('configuracion/imagenConstancia')) ?>" alt="Logo" class="img-fluid mb-3" style="max-height: 90px;">
                <?php endif; ?>
                <h3 class="h6"><?= $v('titulo', 'Constancia de participación') ?></h3>
                <p class="small text-muted mb-0">
                    <?= nl2br(str_replace(
                        ['{socio}', '{evento}', '{fecha_inicio}', '{fecha_fin}', '{puntos_dpc}', '{colegio}'],
                        ['Juan Pérez López', 'Evento de ejemplo', fecha_corta(date('Y-m-d')), fecha_corta(date('Y-m-d')), '5.00', e(\Diana\Core\Auth::colegio()['nombre_corto'] ?? '')],
                        e($c['cuerpo'] ?? 'Sin texto capturado todavía.')
                    )) ?>
                </p>
            </div>
        </div>
    </div>
</div>

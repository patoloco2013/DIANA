<?php
/** Pestaña Fiscal. Variables: $fiscal (?array) */
use Diana\Core\Catalogos;
use Diana\Core\Csrf;

$f = $fiscal ?? [];
$v = fn(string $campo, string $porDefecto = '') => e($f[$campo] ?? $porDefecto);
$tieneCsd = !empty($f['csd_cer_archivo']) && !empty($f['csd_key_archivo']);
$hoy = date('Y-m-d');
?>
<div class="row g-4">
    <div class="col-12 col-lg-7">
        <h2 class="h6 fw-semibold mb-3">Datos fiscales del colegio</h2>
        <p class="small text-muted">Los datos del emisor que aparecerán en los CFDI que se timbren a nombre del colegio.</p>
        <form method="post" action="<?= e(url('configuracion/guardarFiscal')) ?>">
            <?= Csrf::campo() ?>
            <div class="row g-3">
                <div class="col-12">
                    <label class="form-label" for="cf_razon">Razón social</label>
                    <input class="form-control text-uppercase" id="cf_razon" name="razon_social" maxlength="254"
                           placeholder="Sin régimen societario (S.A. de C.V., etc.)" value="<?= $v('razon_social') ?>">
                </div>
                <div class="col-6 col-md-4">
                    <label class="form-label" for="cf_rfc">RFC</label>
                    <input class="form-control text-uppercase" id="cf_rfc" name="rfc" maxlength="13" value="<?= $v('rfc') ?>">
                </div>
                <div class="col-6 col-md-8">
                    <label class="form-label" for="cf_regimen">Régimen fiscal</label>
                    <select class="form-select" id="cf_regimen" name="regimen_fiscal">
                        <option value="">— Seleccionar —</option>
                        <?php foreach (Catalogos::REGIMEN_FISCAL as $clave => $etiqueta): ?>
                        <option value="<?= $clave ?>" <?= ($f['regimen_fiscal'] ?? '') === $clave ? 'selected' : '' ?>><?= $clave ?> · <?= e($etiqueta) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-12 col-md-8">
                    <label class="form-label" for="cf_calle">Calle</label>
                    <input class="form-control" id="cf_calle" name="calle" maxlength="150" value="<?= $v('calle') ?>">
                </div>
                <div class="col-6 col-md-2">
                    <label class="form-label" for="cf_next">No. ext.</label>
                    <input class="form-control" id="cf_next" name="numero_ext" maxlength="20" value="<?= $v('numero_ext') ?>">
                </div>
                <div class="col-6 col-md-2">
                    <label class="form-label" for="cf_nint">No. int.</label>
                    <input class="form-control" id="cf_nint" name="numero_int" maxlength="20" value="<?= $v('numero_int') ?>">
                </div>
                <div class="col-12 col-md-4">
                    <label class="form-label" for="cf_colonia">Colonia</label>
                    <input class="form-control" id="cf_colonia" name="colonia" maxlength="100" value="<?= $v('colonia') ?>">
                </div>
                <div class="col-12 col-md-4">
                    <label class="form-label" for="cf_municipio">Municipio / alcaldía</label>
                    <input class="form-control" id="cf_municipio" name="municipio" maxlength="100" value="<?= $v('municipio') ?>">
                </div>
                <div class="col-6 col-md-3">
                    <label class="form-label" for="cf_estado">Estado</label>
                    <select class="form-select" id="cf_estado" name="estado">
                        <option value="">— Seleccionar —</option>
                        <?php foreach (Catalogos::ESTADOS as $edo): ?>
                        <option value="<?= e($edo) ?>" <?= ($f['estado'] ?? '') === $edo ? 'selected' : '' ?>><?= e($edo) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-6 col-md-1">
                    <label class="form-label" for="cf_cp">C.P.</label>
                    <input class="form-control" id="cf_cp" name="codigo_postal" maxlength="5" inputmode="numeric" value="<?= $v('codigo_postal') ?>">
                </div>
            </div>
            <button class="btn btn-primary mt-3" type="submit"><i class="bi bi-check-lg me-1"></i>Guardar datos fiscales</button>
        </form>
    </div>

    <div class="col-12 col-lg-5">
        <h2 class="h6 fw-semibold mb-3">Sello digital (CSD)</h2>
        <p class="small text-muted">Certificado (.cer) y llave privada (.key) del SAT, con su contraseña. Se validan al momento y se guardan cifrados; la llave nunca se puede volver a descargar desde aquí.</p>

        <?php if ($tieneCsd): ?>
        <div class="alert alert-success py-2 small mb-3">
            <i class="bi bi-patch-check-fill me-1"></i><strong>Sello digital cargado.</strong>
            <div class="mt-1">
                Titular: <?= e($f['csd_titular'] ?? '—') ?><br>
                No. de serie: <code><?= e($f['csd_numero_serie'] ?? '—') ?></code><br>
                Vigente del <?= e(fecha_corta($f['csd_vigente_desde'] ?? null)) ?> al <?= e(fecha_corta($f['csd_vigente_hasta'] ?? null)) ?>
                <?php if (($f['csd_vigente_hasta'] ?? $hoy) < $hoy): ?>
                    <span class="badge text-bg-danger ms-1">Vencido</span>
                <?php elseif (($f['csd_vigente_hasta'] ?? '') <= date('Y-m-d', strtotime('+30 days'))): ?>
                    <span class="badge text-bg-warning ms-1">Vence pronto</span>
                <?php else: ?>
                    <span class="badge text-bg-success ms-1">Vigente</span>
                <?php endif; ?>
            </div>
        </div>
        <form method="post" action="<?= e(url('configuracion/eliminarCsd')) ?>" class="mb-4"
              onsubmit="return confirm('¿Eliminar el sello digital cargado? Tendrá que volver a subirlo para timbrar.');">
            <?= Csrf::campo() ?>
            <button class="btn btn-outline-danger btn-sm" type="submit"><i class="bi bi-trash me-1"></i>Eliminar sello digital</button>
        </form>
        <p class="small text-muted">Para reemplazarlo, suba de nuevo los tres campos:</p>
        <?php else: ?>
        <div class="alert alert-warning py-2 small mb-3"><i class="bi bi-exclamation-triangle me-1"></i>Sin sello digital cargado.</div>
        <?php endif; ?>

        <form method="post" action="<?= e(url('configuracion/guardarCsd')) ?>" enctype="multipart/form-data">
            <?= Csrf::campo() ?>
            <div class="mb-3">
                <label class="form-label" for="csd_cer">Certificado (.cer)</label>
                <input class="form-control" id="csd_cer" name="csd_cer" type="file" accept=".cer">
            </div>
            <div class="mb-3">
                <label class="form-label" for="csd_key">Llave privada (.key)</label>
                <input class="form-control" id="csd_key" name="csd_key" type="file" accept=".key">
            </div>
            <div class="mb-3">
                <label class="form-label" for="csd_password">Contraseña de la llave</label>
                <input class="form-control" id="csd_password" name="csd_password" type="password" autocomplete="new-password">
            </div>
            <button class="btn btn-primary w-100" type="submit"><i class="bi bi-shield-lock me-1"></i>Validar y guardar sello digital</button>
        </form>
    </div>
</div>

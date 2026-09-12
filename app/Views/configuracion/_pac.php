<?php
/** Pestaña SAT PAC. Variables: $pac (?array), $mascara */
use Diana\Core\Catalogos;
use Diana\Core\Csrf;

$p = $pac ?? [];
$v = fn(string $campo, string $porDefecto = '') => e($p[$campo] ?? $porDefecto);
?>
<div class="row g-4">
    <div class="col-12 col-lg-7">
        <h2 class="h6 fw-semibold mb-3">Proveedor Autorizado de Certificación (PAC)</h2>
        <p class="small text-muted">
            Datos de acceso al PAC que timbrará los CFDI (ejemplo: Timbox). Esto guarda la
            configuración; la integración de timbrado en sí (generar y enviar el XML) es un
            módulo aparte, pendiente de construir.
        </p>
        <form method="post" action="<?= e(url('configuracion/guardarPac')) ?>">
            <?= Csrf::campo() ?>
            <div class="row g-3">
                <div class="col-12 col-md-6">
                    <label class="form-label" for="cp_proveedor">Proveedor</label>
                    <select class="form-select" id="cp_proveedor" name="proveedor">
                        <?php foreach (Catalogos::PAC_PROVEEDORES as $clave => $etiqueta): ?>
                        <option value="<?= $clave ?>" <?= ($p['proveedor'] ?? 'timbox') === $clave ? 'selected' : '' ?>><?= e($etiqueta) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-12 col-md-6">
                    <label class="form-label" for="cp_modo">Modo</label>
                    <select class="form-select" id="cp_modo" name="modo">
                        <option value="pruebas" <?= ($p['modo'] ?? 'pruebas') === 'pruebas' ? 'selected' : '' ?>>Pruebas (sandbox)</option>
                        <option value="produccion" <?= ($p['modo'] ?? '') === 'produccion' ? 'selected' : '' ?>>Producción</option>
                    </select>
                </div>
                <div class="col-12 col-md-6">
                    <label class="form-label" for="cp_usuario">Usuario / Client ID</label>
                    <input class="form-control" id="cp_usuario" name="usuario" maxlength="150" autocomplete="off" value="<?= $v('usuario') ?>">
                </div>
                <div class="col-12 col-md-6">
                    <label class="form-label" for="cp_password">
                        Contraseña / Client Secret <?= !empty($p['password']) ? '(dejar vacío para no cambiar)' : '' ?>
                    </label>
                    <input class="form-control" id="cp_password" name="password" type="password" autocomplete="new-password"
                           placeholder="<?= !empty($p['password']) ? e($mascara) : '' ?>">
                </div>
                <div class="col-12 col-md-6">
                    <label class="form-label" for="cp_apikey">
                        API Key / Token <?= !empty($p['api_key']) ? '(dejar vacío para no cambiar)' : '' ?>
                    </label>
                    <input class="form-control" id="cp_apikey" name="api_key" type="password" autocomplete="new-password"
                           placeholder="<?= !empty($p['api_key']) ? e($mascara) : '' ?>">
                </div>
                <div class="col-12 col-md-6">
                    <label class="form-label" for="cp_url">URL del servicio <span class="text-muted">(opcional)</span></label>
                    <input class="form-control" id="cp_url" name="url_servicio" maxlength="255" placeholder="Solo si el proveedor lo requiere" value="<?= $v('url_servicio') ?>">
                </div>
            </div>
            <button class="btn btn-primary mt-3" type="submit"><i class="bi bi-check-lg me-1"></i>Guardar</button>
        </form>
    </div>
</div>

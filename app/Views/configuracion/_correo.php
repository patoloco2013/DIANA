<?php
/** Pestaña Correo. Variables: $correo (?array), $mascara */
use Diana\Core\Catalogos;
use Diana\Core\Csrf;

$c = $correo ?? [];
$v = fn(string $campo, string $porDefecto = '') => e($c[$campo] ?? $porDefecto);
?>
<div class="row g-4">
    <div class="col-12 col-lg-7">
        <h2 class="h6 fw-semibold mb-3">Correo saliente (SMTP)</h2>
        <p class="small text-muted">Para el envío de notificaciones y confirmaciones a socios. El envío en sí (recordatorios, confirmaciones) se activará en un módulo aparte; por ahora esto guarda y verifica la conexión.</p>
        <form method="post" action="<?= e(url('configuracion/guardarCorreo')) ?>">
            <?= Csrf::campo() ?>
            <div class="row g-3">
                <div class="col-12 col-md-8">
                    <label class="form-label" for="cc_host">Servidor SMTP</label>
                    <input class="form-control" id="cc_host" name="smtp_host" maxlength="150" placeholder="smtp.ejemplo.com" value="<?= $v('smtp_host') ?>">
                </div>
                <div class="col-6 col-md-2">
                    <label class="form-label" for="cc_puerto">Puerto</label>
                    <input class="form-control" id="cc_puerto" name="smtp_puerto" type="number" min="1" max="65535" value="<?= $v('smtp_puerto', '587') ?>">
                </div>
                <div class="col-6 col-md-2">
                    <label class="form-label" for="cc_seguridad">Seguridad</label>
                    <select class="form-select" id="cc_seguridad" name="smtp_seguridad">
                        <?php foreach (Catalogos::SMTP_SEGURIDAD as $clave => $etiqueta): ?>
                        <option value="<?= $clave ?>" <?= ($c['smtp_seguridad'] ?? 'tls') === $clave ? 'selected' : '' ?>><?= $clave ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-12 col-md-6">
                    <label class="form-label" for="cc_usuario">Usuario</label>
                    <input class="form-control" id="cc_usuario" name="smtp_usuario" maxlength="150" autocomplete="off" value="<?= $v('smtp_usuario') ?>">
                </div>
                <div class="col-12 col-md-6">
                    <label class="form-label" for="cc_password">
                        Contraseña <?= !empty($c['smtp_password']) ? '(dejar vacío para no cambiar)' : '' ?>
                    </label>
                    <input class="form-control" id="cc_password" name="smtp_password" type="password" autocomplete="new-password"
                           placeholder="<?= !empty($c['smtp_password']) ? e($mascara) : '' ?>">
                </div>
                <div class="col-12 col-md-6">
                    <label class="form-label" for="cc_remitente_nombre">Nombre del remitente</label>
                    <input class="form-control" id="cc_remitente_nombre" name="remitente_nombre" maxlength="150" value="<?= $v('remitente_nombre') ?>">
                </div>
                <div class="col-12 col-md-6">
                    <label class="form-label" for="cc_remitente_email">Correo remitente</label>
                    <input class="form-control" id="cc_remitente_email" name="remitente_email" type="email" maxlength="120" value="<?= $v('remitente_email') ?>">
                </div>
            </div>
            <div class="mt-3 d-flex gap-2">
                <button class="btn btn-primary" type="submit"><i class="bi bi-check-lg me-1"></i>Guardar</button>
            </div>
        </form>

        <?php if (!empty($c['smtp_host'])): ?>
        <form method="post" action="<?= e(url('configuracion/probarCorreo')) ?>" class="mt-3">
            <?= Csrf::campo() ?>
            <button class="btn btn-outline-secondary btn-sm" type="submit"><i class="bi bi-plug me-1"></i>Probar conexión</button>
            <span class="small text-muted ms-2">Verifica host, puerto y credenciales; no envía ningún correo.</span>
        </form>
        <?php endif; ?>
    </div>
</div>

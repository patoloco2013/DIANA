<?php use Diana\Core\Csrf; ?>
<h1 class="h4 mb-4"><?= e($titulo) ?></h1>

<div class="card" style="max-width: 720px;">
    <div class="card-body">
        <form method="post">
            <?= Csrf::campo() ?>
            <div class="row g-3">
                <div class="col-6 col-md-3">
                    <label class="form-label" for="clave">Clave *</label>
                    <input class="form-control text-uppercase" id="clave" name="clave" maxlength="20" required
                           placeholder="MOR" value="<?= e($colegio['clave'] ?? '') ?>">
                </div>
                <div class="col-6 col-md-9">
                    <label class="form-label" for="nombre_corto">Nombre corto *</label>
                    <input class="form-control" id="nombre_corto" name="nombre_corto" maxlength="60" required
                           value="<?= e($colegio['nombre_corto'] ?? '') ?>">
                </div>
                <div class="col-12">
                    <label class="form-label" for="nombre">Nombre / razón social *</label>
                    <input class="form-control" id="nombre" name="nombre" maxlength="150" required
                           value="<?= e($colegio['nombre'] ?? '') ?>">
                </div>
                <div class="col-12 col-md-4">
                    <label class="form-label" for="ciudad">Ciudad</label>
                    <input class="form-control" id="ciudad" name="ciudad" maxlength="80"
                           value="<?= e($colegio['ciudad'] ?? '') ?>">
                </div>
                <div class="col-12 col-md-4">
                    <label class="form-label" for="email_contacto">Correo de contacto</label>
                    <input class="form-control" id="email_contacto" name="email_contacto" type="email" maxlength="120"
                           value="<?= e($colegio['email_contacto'] ?? '') ?>">
                </div>
                <div class="col-12 col-md-4">
                    <label class="form-label" for="telefono">Teléfono</label>
                    <input class="form-control" id="telefono" name="telefono" maxlength="30"
                           value="<?= e($colegio['telefono'] ?? '') ?>">
                </div>
                <div class="col-6 col-md-3">
                    <label class="form-label" for="color_primario">Color del tema</label>
                    <input class="form-control form-control-color w-100" id="color_primario" name="color_primario"
                           type="color" value="<?= e($colegio['color_primario'] ?? '#1f0512') ?>">
                </div>
                <div class="col-6 col-md-9">
                    <label class="form-label" for="logo_url">URL del logotipo</label>
                    <input class="form-control" id="logo_url" name="logo_url" maxlength="255"
                           value="<?= e($colegio['logo_url'] ?? '') ?>">
                </div>
                <div class="col-12">
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" id="activo" name="activo" value="1"
                               <?= (int) ($colegio['activo'] ?? 1) === 1 ? 'checked' : '' ?>>
                        <label class="form-check-label" for="activo">Colegio activo</label>
                    </div>
                </div>
            </div>
            <div class="mt-4 d-flex gap-2">
                <button class="btn btn-primary" type="submit"><i class="bi bi-check-lg me-1"></i>Guardar</button>
                <a class="btn btn-outline-secondary" href="<?= e(url('colegios')) ?>">Cancelar</a>
            </div>
        </form>
    </div>
</div>

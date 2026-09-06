<?php use Diana\Core\Csrf; ?>
<h1 class="h4 mb-4"><?= e($titulo) ?></h1>

<div class="card" style="max-width: 760px;">
    <div class="card-body">
        <form method="post">
            <?= Csrf::campo() ?>
            <div class="row g-3">
                <div class="col-6 col-md-3">
                    <label class="form-label" for="numero">Número *</label>
                    <input class="form-control" id="numero" name="numero" maxlength="20" required
                           value="<?= e($socio['numero'] ?? '') ?>">
                </div>
                <div class="col-6 col-md-3">
                    <label class="form-label" for="titulo_c">Título</label>
                    <input class="form-control" id="titulo_c" name="titulo" maxlength="30"
                           placeholder="C.P., L.C., Dr." value="<?= e($socio['titulo'] ?? '') ?>">
                </div>
                <div class="col-12 col-md-6">
                    <label class="form-label" for="nombre">Nombre completo *</label>
                    <input class="form-control" id="nombre" name="nombre" maxlength="150" required
                           value="<?= e($socio['nombre'] ?? '') ?>">
                </div>
                <div class="col-6 col-md-4">
                    <label class="form-label" for="rfc">RFC</label>
                    <input class="form-control text-uppercase" id="rfc" name="rfc" maxlength="13"
                           value="<?= e($socio['rfc'] ?? '') ?>">
                </div>
                <div class="col-6 col-md-4">
                    <label class="form-label" for="telefono">Teléfono</label>
                    <input class="form-control" id="telefono" name="telefono" maxlength="30"
                           value="<?= e($socio['telefono'] ?? '') ?>">
                </div>
                <div class="col-12 col-md-4">
                    <label class="form-label" for="email">Correo</label>
                    <input class="form-control" id="email" name="email" type="email" maxlength="120"
                           value="<?= e($socio['email'] ?? '') ?>">
                </div>
                <div class="col-12 col-md-4">
                    <label class="form-label" for="estatus">Estatus</label>
                    <select class="form-select" id="estatus" name="estatus">
                        <?php foreach (['activo', 'suspendido', 'baja'] as $op): ?>
                        <option value="<?= $op ?>" <?= ($socio['estatus'] ?? 'activo') === $op ? 'selected' : '' ?>><?= ucfirst($op) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-12">
                    <label class="form-label" for="observaciones">Observaciones</label>
                    <textarea class="form-control" id="observaciones" name="observaciones" rows="3"><?= e($socio['observaciones'] ?? '') ?></textarea>
                </div>
            </div>
            <div class="mt-4 d-flex gap-2">
                <button class="btn btn-primary" type="submit"><i class="bi bi-check-lg me-1"></i>Guardar</button>
                <a class="btn btn-outline-secondary" href="<?= e(url('socios')) ?>">Cancelar</a>
            </div>
        </form>
    </div>
</div>

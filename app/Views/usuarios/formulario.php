<?php use Diana\Core\Auth; use Diana\Core\Csrf; ?>
<h1 class="h4 mb-4"><?= e($titulo) ?></h1>

<div class="card" style="max-width: 640px;">
    <div class="card-body">
        <form method="post" autocomplete="off">
            <?= Csrf::campo() ?>
            <div class="row g-3">
                <div class="col-12 col-md-6">
                    <label class="form-label" for="usuario">Usuario (login) *</label>
                    <input class="form-control" id="usuario" name="usuario" maxlength="50" required
                           value="<?= e($u['usuario'] ?? '') ?>">
                </div>
                <div class="col-12 col-md-6">
                    <label class="form-label" for="nombre">Nombre completo *</label>
                    <input class="form-control" id="nombre" name="nombre" maxlength="120" required
                           value="<?= e($u['nombre'] ?? '') ?>">
                </div>
                <div class="col-12 col-md-6">
                    <label class="form-label" for="email">Correo</label>
                    <input class="form-control" id="email" name="email" type="email" maxlength="120"
                           value="<?= e($u['email'] ?? '') ?>">
                </div>
                <div class="col-12 col-md-6">
                    <label class="form-label" for="rol_id">Rol *</label>
                    <select class="form-select" id="rol_id" name="rol_id" required>
                        <option value="">— Seleccionar —</option>
                        <?php foreach ($roles as $r): ?>
                        <option value="<?= (int) $r['id'] ?>" <?= (int) ($u['rol_id'] ?? 0) === (int) $r['id'] ? 'selected' : '' ?>><?= e($r['nombre']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <?php if (Auth::esGlobal()): ?>
                <div class="col-12 col-md-6">
                    <label class="form-label" for="colegio_id">Colegio</label>
                    <select class="form-select" id="colegio_id" name="colegio_id">
                        <option value="">Todos (superadmin)</option>
                        <?php foreach ($colegios as $c): ?>
                        <option value="<?= (int) $c['id'] ?>" <?= (int) ($u['colegio_id'] ?? 0) === (int) $c['id'] ? 'selected' : '' ?>><?= e($c['nombre_corto']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <?php endif; ?>
                <div class="col-12 col-md-6">
                    <label class="form-label" for="password">
                        Contraseña <?= isset($u['id']) ? '(dejar vacío para no cambiar)' : '*' ?>
                    </label>
                    <input class="form-control" id="password" name="password" type="password"
                           minlength="10" maxlength="100" autocomplete="new-password"
                           <?= isset($u['id']) ? '' : 'required' ?>>
                    <div class="form-text">Mínimo 10 caracteres.</div>
                </div>
                <div class="col-12">
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" id="activo" name="activo" value="1"
                               <?= (int) ($u['activo'] ?? 1) === 1 ? 'checked' : '' ?>>
                        <label class="form-check-label" for="activo">Cuenta activa</label>
                    </div>
                </div>
            </div>
            <div class="mt-4 d-flex gap-2">
                <button class="btn btn-primary" type="submit"><i class="bi bi-check-lg me-1"></i>Guardar</button>
                <a class="btn btn-outline-secondary" href="<?= e(url('usuarios')) ?>">Cancelar</a>
            </div>
        </form>
    </div>
</div>

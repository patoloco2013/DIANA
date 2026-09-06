<?php use Diana\Core\Csrf; ?>
<h1 class="h4 mb-4"><?= e($titulo) ?></h1>

<div class="card" style="max-width: 900px;">
    <div class="card-body">
        <form method="post">
            <?= Csrf::campo() ?>
            <div class="row g-3">
                <div class="col-12 col-md-8">
                    <label class="form-label" for="nombre">Nombre del evento *</label>
                    <input class="form-control" id="nombre" name="nombre" maxlength="200" required
                           value="<?= e($evento['nombre'] ?? '') ?>">
                </div>
                <div class="col-12 col-md-4">
                    <label class="form-label" for="tipo">Tipo</label>
                    <input class="form-control" id="tipo" name="tipo" maxlength="60"
                           placeholder="Curso, congreso, taller..." value="<?= e($evento['tipo'] ?? '') ?>">
                </div>
                <div class="col-6 col-md-3">
                    <label class="form-label" for="fecha_inicio">Fecha inicio *</label>
                    <input class="form-control" id="fecha_inicio" name="fecha_inicio" type="date" required
                           value="<?= e($evento['fecha_inicio'] ?? '') ?>">
                </div>
                <div class="col-6 col-md-3">
                    <label class="form-label" for="fecha_fin">Fecha fin</label>
                    <input class="form-control" id="fecha_fin" name="fecha_fin" type="date"
                           value="<?= e($evento['fecha_fin'] ?? '') ?>">
                </div>
                <div class="col-6 col-md-3">
                    <label class="form-label" for="hora_inicio">Hora inicio</label>
                    <input class="form-control" id="hora_inicio" name="hora_inicio" type="time"
                           value="<?= e($evento['hora_inicio'] ?? '') ?>">
                </div>
                <div class="col-6 col-md-3">
                    <label class="form-label" for="hora_fin">Hora fin</label>
                    <input class="form-control" id="hora_fin" name="hora_fin" type="time"
                           value="<?= e($evento['hora_fin'] ?? '') ?>">
                </div>
                <div class="col-12 col-md-6">
                    <label class="form-label" for="sede">Sede</label>
                    <input class="form-control" id="sede" name="sede" maxlength="150"
                           value="<?= e($evento['sede'] ?? '') ?>">
                </div>
                <div class="col-12 col-md-6">
                    <label class="form-label" for="expositores">Expositores</label>
                    <input class="form-control" id="expositores" name="expositores" maxlength="255"
                           value="<?= e($evento['expositores'] ?? '') ?>">
                </div>
                <div class="col-6 col-md-3">
                    <label class="form-label" for="puntos_epc">Puntos EPC</label>
                    <input class="form-control" id="puntos_epc" name="puntos_epc" type="number" step="0.5" min="0"
                           value="<?= e($evento['puntos_epc'] ?? '0') ?>">
                </div>
                <div class="col-6 col-md-3">
                    <label class="form-label" for="precio_socio">Precio socio</label>
                    <input class="form-control" id="precio_socio" name="precio_socio" type="number" step="0.01" min="0"
                           value="<?= e($evento['precio_socio'] ?? '0') ?>">
                </div>
                <div class="col-6 col-md-3">
                    <label class="form-label" for="precio_publico">Precio público</label>
                    <input class="form-control" id="precio_publico" name="precio_publico" type="number" step="0.01" min="0"
                           value="<?= e($evento['precio_publico'] ?? '0') ?>">
                </div>
                <div class="col-6 col-md-3">
                    <label class="form-label" for="cupo">Cupo</label>
                    <input class="form-control" id="cupo" name="cupo" type="number" min="0"
                           value="<?= e($evento['cupo'] ?? '') ?>">
                </div>
                <div class="col-12 col-md-9">
                    <label class="form-label" for="descripcion">Descripción / objetivo</label>
                    <textarea class="form-control" id="descripcion" name="descripcion" rows="3"><?= e($evento['descripcion'] ?? '') ?></textarea>
                </div>
                <div class="col-12 col-md-3">
                    <label class="form-label" for="estatus">Estatus</label>
                    <select class="form-select" id="estatus" name="estatus">
                        <?php foreach ($listaEstatus as $op): ?>
                        <option value="<?= $op ?>" <?= ($evento['estatus'] ?? 'publicado') === $op ? 'selected' : '' ?>><?= ucfirst($op) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="mt-4 d-flex gap-2">
                <button class="btn btn-primary" type="submit"><i class="bi bi-check-lg me-1"></i>Guardar</button>
                <a class="btn btn-outline-secondary" href="<?= e(url('eventos')) ?>">Cancelar</a>
            </div>
        </form>
    </div>
</div>

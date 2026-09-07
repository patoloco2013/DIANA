<?php
use Diana\Core\Catalogos;
use Diana\Core\Csrf;

$esEdicion = isset($socio['id']);
$v = fn(string $campo, string $porDefecto = '') => e($socio[$campo] ?? $porDefecto);
$nombreCompleto = trim(implode(' ', array_filter([
    $socio['nombre'] ?? '', $socio['apellido_paterno'] ?? '', $socio['apellido_materno'] ?? '',
])));
$accionForm = $esEdicion ? 'socios/editar/' . (int) $socio['id'] : 'socios/crear';
$pestanas = [
    'generales'   => ['Generales',   'bi-person-vcard', true],
    'adicionales' => ['Adicionales', 'bi-geo-alt',      true],
    'documentos'  => ['Documentos',  'bi-folder2-open', $esEdicion],
    'fiscal'      => ['Fiscal',      'bi-receipt',      $esEdicion],
];
$conteo = ['documentos' => count($documentos), 'fiscal' => count($perfiles)];
?>

<div class="d-flex flex-wrap align-items-center gap-3 mb-4">
    <?php if ($esEdicion && !empty($socio['foto'])): ?>
        <img class="diana-foto-socio" src="<?= e(url('socios/foto/' . (int) $socio['id'])) ?>" alt="Foto del socio">
    <?php else: ?>
        <span class="diana-foto-socio diana-foto-vacia"><i class="bi bi-person"></i></span>
    <?php endif; ?>
    <div>
        <h1 class="h4 mb-0"><?= $esEdicion ? e(trim(($socio['titulo'] ?? '') . ' ' . $nombreCompleto)) : 'Nuevo socio' ?></h1>
        <?php if ($esEdicion): ?>
        <div class="small text-muted">
            No. <?= $v('numero') ?>
            · <?= e(Catalogos::TIPOS_SOCIO[$socio['tipo'] ?? 'normal'] ?? $socio['tipo']) ?>
            · <span class="badge text-bg-<?= ['activo' => 'success', 'suspendido' => 'warning', 'baja' => 'secondary'][$socio['estatus'] ?? 'activo'] ?? 'secondary' ?>"><?= $v('estatus') ?></span>
        </div>
        <?php endif; ?>
    </div>
    <div class="ms-auto d-flex flex-wrap gap-2">
        <?php if ($esEdicion): ?>
        <a class="btn btn-outline-secondary" href="<?= e(url('cuentas/socio/' . (int) $socio['id'])) ?>">
            <i class="bi bi-wallet2 me-1"></i>Estado de cuenta
        </a>
        <?php endif; ?>
        <a class="btn btn-outline-secondary" href="<?= e(url('socios')) ?>"><i class="bi bi-arrow-left me-1"></i>Listado</a>
    </div>
</div>

<ul class="nav nav-tabs diana-tabs" role="tablist">
    <?php foreach ($pestanas as $clave => [$etiqueta, $icono, $habilitada]): ?>
    <li class="nav-item" role="presentation">
        <button class="nav-link <?= $pestana === $clave ? 'active' : '' ?> <?= $habilitada ? '' : 'disabled' ?>"
                data-bs-toggle="tab" data-bs-target="#tab-<?= $clave ?>" type="button" role="tab"
                <?= $habilitada ? '' : 'title="Guarde el socio para habilitar esta pestaña"' ?>>
            <i class="bi <?= $icono ?> me-1"></i><?= $etiqueta ?>
            <?php if (isset($conteo[$clave]) && $habilitada): ?>
                <span class="badge rounded-pill text-bg-secondary ms-1"><?= (int) $conteo[$clave] ?></span>
            <?php endif; ?>
        </button>
    </li>
    <?php endforeach; ?>
</ul>

<div class="card diana-card-tabs">
    <div class="card-body">

        <form method="post" action="<?= e(url($accionForm, ['pestana' => $pestana])) ?>" enctype="multipart/form-data">
            <?= Csrf::campo() ?>
            <div class="tab-content">

                <!-- ============ GENERALES ============ -->
                <div class="tab-pane fade <?= $pestana === 'generales' ? 'show active' : '' ?>" id="tab-generales" role="tabpanel">
                    <div class="row g-3">
                        <div class="col-12 col-md-4">
                            <label class="form-label" for="nombre">Nombre(s) *</label>
                            <input class="form-control" id="nombre" name="nombre" maxlength="100" required value="<?= $v('nombre') ?>">
                        </div>
                        <div class="col-6 col-md-4">
                            <label class="form-label" for="apellido_paterno">Apellido paterno</label>
                            <input class="form-control" id="apellido_paterno" name="apellido_paterno" maxlength="60" value="<?= $v('apellido_paterno') ?>">
                        </div>
                        <div class="col-6 col-md-4">
                            <label class="form-label" for="apellido_materno">Apellido materno</label>
                            <input class="form-control" id="apellido_materno" name="apellido_materno" maxlength="60" value="<?= $v('apellido_materno') ?>">
                        </div>

                        <div class="col-6 col-md-3">
                            <label class="form-label" for="titulo_c">Título / profesión</label>
                            <input class="form-control" id="titulo_c" name="titulo" maxlength="30" placeholder="C.P., L.C., Dr." value="<?= $v('titulo') ?>">
                        </div>
                        <div class="col-6 col-md-3">
                            <label class="form-label" for="numero">ID socio *</label>
                            <input class="form-control" id="numero" name="numero" maxlength="20" required value="<?= $v('numero') ?>">
                        </div>
                        <div class="col-6 col-md-3">
                            <label class="form-label" for="rfc">RFC</label>
                            <input class="form-control text-uppercase" id="rfc" name="rfc" maxlength="13" value="<?= $v('rfc') ?>">
                        </div>
                        <div class="col-6 col-md-3">
                            <label class="form-label" for="limite_credito">Límite de crédito</label>
                            <div class="input-group">
                                <span class="input-group-text">$</span>
                                <input class="form-control" id="limite_credito" name="limite_credito" type="number" step="0.01" min="0" value="<?= $v('limite_credito', '0') ?>">
                            </div>
                        </div>

                        <div class="col-6 col-md-3">
                            <label class="form-label" for="tipo">Tipo de socio</label>
                            <select class="form-select" id="tipo" name="tipo">
                                <?php foreach (Catalogos::TIPOS_SOCIO as $clave => $etiqueta): ?>
                                <option value="<?= $clave ?>" <?= ($socio['tipo'] ?? 'normal') === $clave ? 'selected' : '' ?>><?= e($etiqueta) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-6 col-md-3">
                            <label class="form-label" for="genero">Género</label>
                            <select class="form-select" id="genero" name="genero">
                                <?php foreach (Catalogos::GENEROS as $clave => $etiqueta): ?>
                                <option value="<?= $clave ?>" <?= ($socio['genero'] ?? 'sin_especificar') === $clave ? 'selected' : '' ?>><?= e($etiqueta) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-6 col-md-3">
                            <label class="form-label" for="estatus">Estatus</label>
                            <select class="form-select" id="estatus" name="estatus">
                                <?php foreach (['activo', 'suspendido', 'baja'] as $op): ?>
                                <option value="<?= $op ?>" <?= ($socio['estatus'] ?? 'activo') === $op ? 'selected' : '' ?>><?= ucfirst($op) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-6 col-md-3">
                            <label class="form-label">Cumpleaños</label>
                            <div class="d-flex gap-2">
                                <select class="form-select" name="cumple_dia" aria-label="Día">
                                    <option value="">Día</option>
                                    <?php for ($d = 1; $d <= 31; $d++): ?>
                                    <option value="<?= $d ?>" <?= (int) ($socio['cumple_dia'] ?? 0) === $d ? 'selected' : '' ?>><?= str_pad((string) $d, 2, '0', STR_PAD_LEFT) ?></option>
                                    <?php endfor; ?>
                                </select>
                                <select class="form-select" name="cumple_mes" aria-label="Mes">
                                    <option value="">Mes</option>
                                    <?php foreach (Catalogos::MESES as $m => $nombreMes): ?>
                                    <option value="<?= $m ?>" <?= (int) ($socio['cumple_mes'] ?? 0) === $m ? 'selected' : '' ?>><?= $nombreMes ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <div class="col-12 col-md-6">
                            <label class="form-label" for="foto">Fotografía <span class="text-muted">(JPG, PNG o WEBP, máx. <?= (int) $maxMb ?> MB)</span></label>
                            <input class="form-control" id="foto" name="foto" type="file" accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp">
                            <?php if ($esEdicion && !empty($socio['foto'])): ?>
                            <div class="form-check mt-2">
                                <input class="form-check-input" type="checkbox" id="quitar_foto" name="quitar_foto" value="1">
                                <label class="form-check-label" for="quitar_foto">Quitar la foto actual</label>
                            </div>
                            <?php endif; ?>
                        </div>
                        <div class="col-12 col-md-6 d-flex align-items-end">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="paga_cuota_anual" name="paga_cuota_anual" value="1"
                                       <?= (int) ($socio['paga_cuota_anual'] ?? 1) === 1 ? 'checked' : '' ?>>
                                <label class="form-check-label" for="paga_cuota_anual">Paga cuota anual</label>
                            </div>
                        </div>
                    </div>

                    <div class="mt-4 d-flex gap-2">
                        <button class="btn btn-primary" type="submit"><i class="bi bi-check-lg me-1"></i><?= $esEdicion ? 'Guardar cambios' : 'Registrar socio' ?></button>
                        <button class="btn btn-outline-secondary" type="button" data-bs-toggle="tab" data-bs-target="#tab-adicionales">Siguiente: Adicionales <i class="bi bi-arrow-right ms-1"></i></button>
                    </div>
                </div>

                <!-- ============ ADICIONALES ============ -->
                <div class="tab-pane fade <?= $pestana === 'adicionales' ? 'show active' : '' ?>" id="tab-adicionales" role="tabpanel">
                    <div class="row g-3">
                        <div class="col-12 col-md-9">
                            <label class="form-label" for="direccion">Dirección</label>
                            <input class="form-control" id="direccion" name="direccion" maxlength="200" placeholder="Calle y número" value="<?= $v('direccion') ?>">
                        </div>
                        <div class="col-6 col-md-3">
                            <label class="form-label" for="codigo_postal">Código postal</label>
                            <input class="form-control" id="codigo_postal" name="codigo_postal" maxlength="5" inputmode="numeric" pattern="[0-9]{5}" value="<?= $v('codigo_postal') ?>">
                        </div>
                        <div class="col-12 col-md-6">
                            <label class="form-label" for="colonia">Colonia</label>
                            <input class="form-control" id="colonia" name="colonia" maxlength="100" value="<?= $v('colonia') ?>">
                        </div>
                        <div class="col-12 col-md-6">
                            <label class="form-label" for="localidad">Localidad</label>
                            <input class="form-control" id="localidad" name="localidad" maxlength="100" value="<?= $v('localidad') ?>">
                        </div>
                        <div class="col-12 col-md-6">
                            <label class="form-label" for="ciudad">Ciudad</label>
                            <input class="form-control" id="ciudad" name="ciudad" maxlength="100" value="<?= $v('ciudad') ?>">
                        </div>
                        <div class="col-12 col-md-6">
                            <label class="form-label" for="estado">Estado</label>
                            <select class="form-select" id="estado" name="estado">
                                <option value="">— Seleccionar —</option>
                                <?php foreach (Catalogos::ESTADOS as $edo): ?>
                                <option value="<?= e($edo) ?>" <?= ($socio['estado'] ?? '') === $edo ? 'selected' : '' ?>><?= e($edo) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-12 col-md-4">
                            <label class="form-label" for="celular">Celular</label>
                            <input class="form-control" id="celular" name="celular" maxlength="30" value="<?= $v('celular') ?>">
                        </div>
                        <div class="col-6 col-md-4">
                            <label class="form-label" for="telefono_oficina">Teléfono oficina 1</label>
                            <input class="form-control" id="telefono_oficina" name="telefono_oficina" maxlength="30" value="<?= $v('telefono_oficina') ?>">
                        </div>
                        <div class="col-6 col-md-4">
                            <label class="form-label" for="telefono_oficina2">Teléfono oficina 2</label>
                            <input class="form-control" id="telefono_oficina2" name="telefono_oficina2" maxlength="30" value="<?= $v('telefono_oficina2') ?>">
                        </div>
                        <div class="col-12 col-md-6">
                            <label class="form-label" for="email">Correo 1</label>
                            <input class="form-control" id="email" name="email" type="email" maxlength="120" value="<?= $v('email') ?>">
                        </div>
                        <div class="col-12 col-md-6">
                            <label class="form-label" for="email2">Correo 2</label>
                            <input class="form-control" id="email2" name="email2" type="email" maxlength="120" value="<?= $v('email2') ?>">
                        </div>
                        <div class="col-12">
                            <label class="form-label" for="observaciones">Observaciones / notas</label>
                            <textarea class="form-control" id="observaciones" name="observaciones" rows="3"><?= $v('observaciones') ?></textarea>
                        </div>
                    </div>

                    <div class="mt-4 d-flex gap-2">
                        <button class="btn btn-primary" type="submit"><i class="bi bi-check-lg me-1"></i><?= $esEdicion ? 'Guardar cambios' : 'Registrar socio' ?></button>
                        <button class="btn btn-outline-secondary" type="button" data-bs-toggle="tab" data-bs-target="#tab-generales"><i class="bi bi-arrow-left me-1"></i>Generales</button>
                    </div>
                </div>
            </div>
        </form>

        <?php if ($esEdicion): ?>
        <div class="tab-content">
            <div class="tab-pane fade <?= $pestana === 'documentos' ? 'show active' : '' ?>" id="tab-documentos" role="tabpanel">
                <?php require __DIR__ . '/_documentos.php'; ?>
            </div>
            <div class="tab-pane fade <?= $pestana === 'fiscal' ? 'show active' : '' ?>" id="tab-fiscal" role="tabpanel">
                <?php require __DIR__ . '/_fiscal.php'; ?>
            </div>
        </div>
        <?php endif; ?>

    </div>
</div>

<?php
/** Pestaña Fiscal del socio. Variables: $socio, $perfiles, $perfilEdicion */
use Diana\Core\Catalogos;
use Diana\Core\Csrf;

$p = $perfilEdicion ?? [];
$pv = fn(string $campo, string $porDefecto = '') => e($p[$campo] ?? $porDefecto);
$accion = $perfilEdicion
    ? 'socios/guardarFiscal/' . (int) $socio['id'] . '/' . (int) $perfilEdicion['id']
    : 'socios/guardarFiscal/' . (int) $socio['id'];
?>
<div class="row g-4">
    <div class="col-12 col-lg-5">
        <h2 class="h6 fw-semibold mb-3"><?= $perfilEdicion ? 'Editar perfil fiscal' : 'Nuevo perfil fiscal' ?></h2>
        <p class="small text-muted">Datos del receptor tal como aparecen en su Constancia de Situación Fiscal; se usan para emitir CFDI.</p>
        <form method="post" action="<?= e(url($accion)) ?>">
            <?= Csrf::campo() ?>
            <div class="row g-3">
                <div class="col-12 col-md-5">
                    <label class="form-label" for="pf_alias">Alias *</label>
                    <input class="form-control" id="pf_alias" name="alias" maxlength="60" required placeholder="Personal, Despacho…" value="<?= $pv('alias') ?>">
                </div>
                <div class="col-12 col-md-7">
                    <label class="form-label" for="pf_rfc">RFC *</label>
                    <input class="form-control text-uppercase" id="pf_rfc" name="rfc" maxlength="13" required value="<?= $pv('rfc') ?>">
                </div>
                <div class="col-12">
                    <label class="form-label" for="pf_razon">Razón social / nombre *</label>
                    <input class="form-control text-uppercase" id="pf_razon" name="razon_social" maxlength="254" required
                           placeholder="Sin régimen societario (S.A. de C.V., etc.)" value="<?= $pv('razon_social') ?>">
                </div>
                <div class="col-12">
                    <label class="form-label" for="pf_regimen">Régimen fiscal *</label>
                    <select class="form-select" id="pf_regimen" name="regimen_fiscal" required>
                        <option value="">— Seleccionar —</option>
                        <?php foreach (Catalogos::REGIMEN_FISCAL as $clave => $etiqueta): ?>
                        <option value="<?= $clave ?>" <?= ($p['regimen_fiscal'] ?? '') === $clave ? 'selected' : '' ?>><?= $clave ?> · <?= e($etiqueta) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-12 col-md-8">
                    <label class="form-label" for="pf_uso">Uso de CFDI</label>
                    <select class="form-select" id="pf_uso" name="uso_cfdi">
                        <?php foreach (Catalogos::USO_CFDI as $clave => $etiqueta): ?>
                        <option value="<?= $clave ?>" <?= ($p['uso_cfdi'] ?? 'G03') === $clave ? 'selected' : '' ?>><?= $clave ?> · <?= e($etiqueta) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-12 col-md-4">
                    <label class="form-label" for="pf_cp">C.P. fiscal *</label>
                    <input class="form-control" id="pf_cp" name="codigo_postal" maxlength="5" inputmode="numeric" pattern="[0-9]{5}" required value="<?= $pv('codigo_postal') ?>">
                </div>
                <div class="col-12">
                    <label class="form-label" for="pf_email">Correo para facturas</label>
                    <input class="form-control" id="pf_email" name="email_facturacion" type="email" maxlength="120" value="<?= $pv('email_facturacion') ?>">
                </div>
                <div class="col-12 d-flex flex-wrap gap-4">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="pf_predeterminado" name="predeterminado" value="1"
                               <?= (int) ($p['predeterminado'] ?? 0) === 1 ? 'checked' : '' ?>>
                        <label class="form-check-label" for="pf_predeterminado">Predeterminado</label>
                    </div>
                    <div class="form-check form-switch">
                        <input type="hidden" name="activo" value="0">
                        <input class="form-check-input" type="checkbox" id="pf_activo" name="activo" value="1"
                               <?= (int) ($p['activo'] ?? 1) === 1 ? 'checked' : '' ?>>
                        <label class="form-check-label" for="pf_activo">Activo</label>
                    </div>
                </div>
            </div>
            <div class="mt-3 d-flex gap-2">
                <button class="btn btn-primary" type="submit"><i class="bi bi-check-lg me-1"></i><?= $perfilEdicion ? 'Guardar perfil' : 'Agregar perfil' ?></button>
                <?php if ($perfilEdicion): ?>
                <a class="btn btn-outline-secondary" href="<?= e(url('socios/editar/' . (int) $socio['id'], ['pestana' => 'fiscal'])) ?>">Cancelar</a>
                <?php endif; ?>
            </div>
        </form>
    </div>

    <div class="col-12 col-lg-7">
        <h2 class="h6 fw-semibold mb-3">Perfiles fiscales <span class="badge text-bg-secondary"><?= count($perfiles) ?></span></h2>
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead><tr><th>Alias</th><th>Receptor</th><th class="d-none d-md-table-cell">Régimen / uso</th><th class="text-end">Acciones</th></tr></thead>
                <tbody>
                <?php if (!$perfiles): ?>
                    <tr><td colspan="4" class="text-center text-muted py-4">Sin perfiles fiscales. El primero que agregue quedará como predeterminado.</td></tr>
                <?php endif; ?>
                <?php foreach ($perfiles as $pf): ?>
                    <tr class="<?= (int) $pf['activo'] === 0 ? 'text-muted' : '' ?>">
                        <td>
                            <?= e($pf['alias']) ?>
                            <?php if ((int) $pf['predeterminado'] === 1): ?><span class="badge text-bg-success ms-1">Predeterminado</span><?php endif; ?>
                            <?php if ((int) $pf['activo'] === 0): ?><span class="badge text-bg-secondary ms-1">Inactivo</span><?php endif; ?>
                        </td>
                        <td>
                            <div class="fw-semibold"><?= e($pf['razon_social']) ?></div>
                            <div class="small"><?= e($pf['rfc']) ?> · C.P. <?= e($pf['codigo_postal']) ?></div>
                            <?php if ($pf['email_facturacion']): ?><div class="small text-muted"><?= e($pf['email_facturacion']) ?></div><?php endif; ?>
                        </td>
                        <td class="d-none d-md-table-cell small">
                            <div><?= e($pf['regimen_fiscal']) ?> · <?= e(Catalogos::REGIMEN_FISCAL[$pf['regimen_fiscal']] ?? '') ?></div>
                            <div class="text-muted"><?= e($pf['uso_cfdi']) ?> · <?= e(Catalogos::USO_CFDI[$pf['uso_cfdi']] ?? '') ?></div>
                        </td>
                        <td class="text-end text-nowrap">
                            <a class="btn btn-sm btn-outline-primary" title="Editar"
                               href="<?= e(url('socios/editar/' . (int) $socio['id'], ['pestana' => 'fiscal', 'perfil' => (int) $pf['id']])) ?>"><i class="bi bi-pencil"></i></a>
                            <?php if ((int) $pf['predeterminado'] !== 1): ?>
                            <form class="d-inline" method="post" action="<?= e(url('socios/predeterminarFiscal/' . (int) $socio['id'] . '/' . (int) $pf['id'])) ?>">
                                <?= Csrf::campo() ?>
                                <button class="btn btn-sm btn-outline-success" title="Hacer predeterminado"><i class="bi bi-star"></i></button>
                            </form>
                            <?php endif; ?>
                            <form class="d-inline" method="post" action="<?= e(url('socios/eliminarFiscal/' . (int) $socio['id'] . '/' . (int) $pf['id'])) ?>"
                                  onsubmit="return confirm('¿Eliminar este perfil fiscal?');">
                                <?= Csrf::campo() ?>
                                <button class="btn btn-sm btn-outline-danger" title="Eliminar"><i class="bi bi-trash"></i></button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

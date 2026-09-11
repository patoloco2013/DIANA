<?php
/** Pestaña Módulos y DPC. Variables: $evento, $modulos, $puntos, $moduloEdicion, $disciplinas */
use Diana\Core\Csrf;

$idEvento = (int) $evento['id'];
$m = $moduloEdicion ?? [];
$mv = fn(string $campo, string $porDefecto = '') => e($m[$campo] ?? $porDefecto);
$porModulo = ($evento['esquema_puntos'] ?? 'evento') === 'modulo';
$accion = $moduloEdicion
    ? 'eventos/guardarModulo/' . $idEvento . '/' . (int) $moduloEdicion['id']
    : 'eventos/guardarModulo/' . $idEvento;
$siguienteOrden = count($modulos) + 1;
?>
<div class="row g-4">
    <div class="col-12 col-lg-4">
        <h2 class="h6 fw-semibold mb-3"><?= $moduloEdicion ? 'Editar módulo' : 'Nuevo módulo' ?></h2>
        <form method="post" action="<?= e(url($accion)) ?>">
            <?= Csrf::campo() ?>
            <div class="row g-3">
                <div class="col-4">
                    <label class="form-label" for="mod_orden">Orden</label>
                    <input class="form-control" id="mod_orden" name="orden" type="number" min="1"
                           value="<?= $mv('orden', (string) $siguienteOrden) ?>">
                </div>
                <div class="col-8">
                    <label class="form-label" for="mod_fecha">Fecha</label>
                    <input class="form-control" id="mod_fecha" name="fecha" type="date" value="<?= $mv('fecha') ?>">
                </div>
                <div class="col-12">
                    <label class="form-label" for="mod_nombre">Nombre del módulo *</label>
                    <input class="form-control" id="mod_nombre" name="nombre" maxlength="200" required value="<?= $mv('nombre') ?>">
                </div>
                <div class="col-6">
                    <label class="form-label" for="mod_hi">Hora inicio</label>
                    <input class="form-control" id="mod_hi" name="hora_inicio" type="time" value="<?= $mv('hora_inicio') ?>">
                </div>
                <div class="col-6">
                    <label class="form-label" for="mod_hf">Hora fin</label>
                    <input class="form-control" id="mod_hf" name="hora_fin" type="time" value="<?= $mv('hora_fin') ?>">
                </div>
                <div class="col-12">
                    <label class="form-label" for="mod_exp">Expositores</label>
                    <input class="form-control" id="mod_exp" name="expositores" maxlength="255" value="<?= $mv('expositores') ?>">
                </div>
                <div class="col-12">
                    <label class="form-label" for="mod_sede">Sede</label>
                    <input class="form-control" id="mod_sede" name="sede" maxlength="150" value="<?= $mv('sede') ?>">
                </div>
            </div>
            <div class="mt-3 d-flex gap-2">
                <button class="btn btn-primary" type="submit"><i class="bi bi-check-lg me-1"></i><?= $moduloEdicion ? 'Guardar módulo' : 'Agregar módulo' ?></button>
                <?php if ($moduloEdicion): ?>
                <a class="btn btn-outline-secondary" href="<?= e(url('eventos/editar/' . $idEvento, ['pestana' => 'modulos'])) ?>">Cancelar</a>
                <?php endif; ?>
            </div>
        </form>

        <?php if ($evento['fecha_fin']): ?>
        <hr class="my-4">
        <form method="post" action="<?= e(url('eventos/generarModulos/' . $idEvento)) ?>"
              onsubmit="return confirm('¿Generar un módulo por cada día del evento?');">
            <?= Csrf::campo() ?>
            <button class="btn btn-outline-primary btn-sm w-100" type="submit">
                <i class="bi bi-magic me-1"></i>Generar un módulo por día
            </button>
            <div class="form-text">Del <?= e(fecha_corta($evento['fecha_inicio'])) ?> al <?= e(fecha_corta($evento['fecha_fin'])) ?>; no duplica días ya capturados.</div>
        </form>
        <?php endif; ?>
    </div>

    <div class="col-12 col-lg-8">
        <h2 class="h6 fw-semibold mb-3">Módulos <span class="badge text-bg-secondary"><?= count($modulos) ?></span></h2>
        <?php if (!$modulos): ?>
            <p class="text-muted">Sin módulos. Un evento puede no tenerlos (una sola sesión) o tener uno por día.</p>
        <?php endif; ?>

        <?php foreach ($modulos as $mod): ?>
            <?php $puntosMod = $puntos[(string) $mod['id']] ?? []; ?>
            <div class="card mb-3">
                <div class="card-body py-3">
                    <div class="d-flex flex-wrap align-items-start gap-2">
                        <div class="flex-grow-1">
                            <div class="fw-semibold"><span class="text-muted me-1"><?= (int) $mod['orden'] ?>.</span><?= e($mod['nombre']) ?></div>
                            <div class="small text-muted">
                                <?php if ($mod['fecha']): ?><i class="bi bi-calendar3 me-1"></i><?= e(fecha_corta($mod['fecha'])) ?><?php endif; ?>
                                <?php if ($mod['hora_inicio']): ?> · <?= e(substr((string) $mod['hora_inicio'], 0, 5)) ?><?php if ($mod['hora_fin']): ?>–<?= e(substr((string) $mod['hora_fin'], 0, 5)) ?><?php endif; ?><?php endif; ?>
                                <?php if ($mod['expositores']): ?> · <?= e($mod['expositores']) ?><?php endif; ?>
                                <?php if ($mod['sede']): ?> · <?= e($mod['sede']) ?><?php endif; ?>
                            </div>
                        </div>
                        <span class="badge text-bg-<?= (float) $mod['puntos_dpc'] > 0 ? 'success' : 'secondary' ?>">
                            <?= e(number_format((float) $mod['puntos_dpc'], 2)) ?> pts DPC
                        </span>
                        <a class="btn btn-sm btn-outline-primary" title="Editar módulo"
                           href="<?= e(url('eventos/editar/' . $idEvento, ['pestana' => 'modulos', 'modulo' => (int) $mod['id']])) ?>"><i class="bi bi-pencil"></i></a>
                        <form class="d-inline" method="post" action="<?= e(url('eventos/eliminarModulo/' . $idEvento . '/' . (int) $mod['id'])) ?>"
                              onsubmit="return confirm('¿Eliminar este módulo y sus puntos DPC?');">
                            <?= Csrf::campo() ?>
                            <button class="btn btn-sm btn-outline-danger" title="Eliminar módulo"><i class="bi bi-trash"></i></button>
                        </form>
                    </div>

                    <?php if ($porModulo && $disciplinas): ?>
                    <details class="mt-3">
                        <summary class="small text-primary" style="cursor: pointer;">Puntos DPC por disciplina de este módulo</summary>
                        <form class="mt-2" method="post" action="<?= e(url('eventos/guardarPuntos/' . $idEvento . '/' . (int) $mod['id'])) ?>">
                            <?= Csrf::campo() ?>
                            <div class="row g-2">
                                <?php foreach ($disciplinas as $d): ?>
                                <div class="col-6 col-md-4">
                                    <label class="form-label small mb-1" for="pm<?= (int) $mod['id'] ?>_<?= (int) $d['id'] ?>">
                                        <?= e($d['nombre']) ?><?= (int) $d['activo'] === 0 ? ' <span class="text-muted">(inactiva)</span>' : '' ?>
                                    </label>
                                    <input class="form-control form-control-sm" id="pm<?= (int) $mod['id'] ?>_<?= (int) $d['id'] ?>"
                                           name="puntos[<?= (int) $d['id'] ?>]" type="number" step="0.5" min="0" placeholder="0"
                                           value="<?= isset($puntosMod[$d['id']]) && (float) $puntosMod[$d['id']] > 0 ? e((string) (float) $puntosMod[$d['id']]) : '' ?>">
                                </div>
                                <?php endforeach; ?>
                            </div>
                            <button class="btn btn-outline-primary btn-sm mt-2" type="submit">Guardar puntos del módulo</button>
                        </form>
                    </details>
                    <?php elseif ($porModulo): ?>
                    <p class="small text-muted mt-3 mb-0">No hay disciplinas activas. <a href="<?= e(url('disciplinas')) ?>">Agregue alguna en el catálogo</a>.</p>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>

        <?php if ($modulos && !$porModulo): ?>
        <p class="small text-muted">
            <i class="bi bi-info-circle me-1"></i>Los puntos DPC de este evento se fijan para el evento completo
            (pestaña «Generales»). Cambie el esquema a «por módulo» si cada sesión otorga puntos distintos.
        </p>
        <?php endif; ?>
    </div>
</div>

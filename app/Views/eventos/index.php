<?php use Diana\Core\Catalogos; ?>
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
    <h1 class="h4 mb-0">Eventos</h1>
    <div class="d-flex flex-wrap gap-2">
        <form method="get" action="<?= e(url('eventos')) ?>" class="d-flex gap-2">
            <input type="hidden" name="r" value="eventos">
            <input class="form-control" type="search" name="q" value="<?= e($q) ?>"
                   placeholder="Nombre, expositor o sede" style="min-width: 220px;">
            <button class="btn btn-outline-secondary" type="submit"><i class="bi bi-search"></i></button>
        </form>
        <a class="btn btn-outline-secondary text-nowrap" href="<?= e(url('disciplinas')) ?>">
            <i class="bi bi-mortarboard me-1"></i>Disciplinas DPC
        </a>
        <a class="btn btn-primary text-nowrap" href="<?= e(url('eventos/crear')) ?>">
            <i class="bi bi-calendar-plus me-1"></i>Nuevo
        </a>
    </div>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead>
                <tr>
                    <th>Evento</th>
                    <th>Fecha</th>
                    <th class="d-none d-md-table-cell">Modalidad</th>
                    <th class="d-none d-lg-table-cell text-end">DPC</th>
                    <th class="text-end d-none d-md-table-cell">Precios</th>
                    <th class="text-end">Reg.</th>
                    <th>Estatus</th>
                    <th class="text-end">Acciones</th>
                </tr>
            </thead>
            <tbody>
            <?php if (!$eventos): ?>
                <tr><td colspan="8" class="text-center text-muted py-4">Sin resultados.</td></tr>
            <?php endif; ?>
            <?php foreach ($eventos as $ev): ?>
                <tr>
                    <td>
                        <div class="d-flex align-items-center gap-2">
                            <?php if ($ev['imagen_id']): ?>
                                <img class="diana-evento-mini" src="<?= e(url('eventos/imagen/' . (int) $ev['id'] . '/' . (int) $ev['imagen_id'])) ?>" alt="">
                            <?php else: ?>
                                <span class="diana-evento-mini diana-avatar-ini"><i class="bi bi-calendar-event"></i></span>
                            <?php endif; ?>
                            <div>
                                <a class="fw-semibold text-decoration-none" href="<?= e(url('eventos/editar/' . (int) $ev['id'])) ?>">
                                    <?= e($ev['nombre']) ?>
                                </a>
                                <div class="small text-muted">
                                    <?php if ($ev['expositores']): ?><?= e($ev['expositores']) ?><?php endif; ?>
                                    <?php if ((int) $ev['modulos'] > 0): ?>
                                        <span class="badge text-bg-light border ms-1"><?= (int) $ev['modulos'] ?> módulos</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </td>
                    <td class="text-nowrap"><?= e(fecha_corta($ev['fecha_inicio'])) ?></td>
                    <td class="d-none d-md-table-cell small">
                        <?php $iconos = ['presencial' => 'bi-geo-alt', 'linea' => 'bi-camera-video', 'hibrido' => 'bi-broadcast']; ?>
                        <i class="bi <?= $iconos[$ev['modalidad']] ?? 'bi-geo-alt' ?> me-1 text-muted"></i>
                        <?= e(Catalogos::MODALIDADES[$ev['modalidad']] ?? $ev['modalidad']) ?>
                        <?php if ($ev['enlace_sesion']): ?>
                            <a class="ms-1" href="<?= e($ev['enlace_sesion']) ?>" target="_blank" rel="noopener noreferrer" title="Abrir sesión en línea"><i class="bi bi-box-arrow-up-right"></i></a>
                        <?php endif; ?>
                    </td>
                    <td class="d-none d-lg-table-cell text-end"><?= e(number_format((float) $ev['puntos_dpc'], 2)) ?></td>
                    <td class="text-end d-none d-md-table-cell small">
                        <?php if ($ev['precio_min'] === null): ?>
                            <span class="text-muted">—</span>
                        <?php elseif ((float) $ev['precio_min'] === (float) $ev['precio_max']): ?>
                            <?= e(dinero($ev['precio_min'])) ?>
                        <?php else: ?>
                            <?= e(dinero($ev['precio_min'])) ?> – <?= e(dinero($ev['precio_max'])) ?>
                        <?php endif; ?>
                    </td>
                    <td class="text-end"><?= (int) $ev['registrados'] ?></td>
                    <td>
                        <?php $color = ['publicado' => 'success', 'borrador' => 'secondary', 'cerrado' => 'dark', 'cancelado' => 'danger'][$ev['estatus']] ?? 'secondary'; ?>
                        <span class="badge text-bg-<?= $color ?>"><?= e($ev['estatus']) ?></span>
                    </td>
                    <td class="text-end text-nowrap">
                        <a class="btn btn-sm btn-outline-secondary" title="Registro de asistentes"
                           href="<?= e(url('registro/evento/' . (int) $ev['id'])) ?>"><i class="bi bi-clipboard2-check"></i></a>
                        <a class="btn btn-sm btn-outline-primary" title="Editar"
                           href="<?= e(url('eventos/editar/' . (int) $ev['id'])) ?>"><i class="bi bi-pencil"></i></a>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

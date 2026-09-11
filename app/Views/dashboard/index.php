<?php
use Diana\Core\Catalogos;

$iconosModalidad = ['presencial' => 'bi-geo-alt', 'linea' => 'bi-camera-video', 'hibrido' => 'bi-broadcast'];
$destacado = $proximosEventos[0] ?? null;
$resto = array_slice($proximosEventos, 1);
?>
<h1 class="h4 mb-4">Inicio</h1>

<div class="row g-3 mb-4">
    <div class="col-6 col-xl-3">
        <div class="card diana-kpi diana-kpi-primario"><div class="card-body">
            <div class="d-flex align-items-center gap-2 text-muted small mb-1">
                <span class="diana-kpi-icono"><i class="bi bi-hourglass-split"></i></span>Próximo evento
            </div>
            <?php if ($destacado): ?>
                <div class="valor"><?= e(dias_relativo($destacado['fecha_inicio'])) ?></div>
                <div class="small text-truncate" title="<?= e($destacado['nombre']) ?>"><?= e($destacado['nombre']) ?></div>
            <?php else: ?>
                <div class="valor text-muted">—</div>
                <div class="small text-muted">Sin eventos próximos</div>
            <?php endif; ?>
        </div></div>
    </div>
    <div class="col-6 col-xl-3">
        <div class="card diana-kpi diana-kpi-info"><div class="card-body">
            <div class="d-flex align-items-center gap-2 text-muted small mb-1">
                <span class="diana-kpi-icono"><i class="bi bi-calendar3"></i></span>Eventos próximos
            </div>
            <div class="valor"><?= (int) $kpis['eventos_proximos'] ?></div>
        </div></div>
    </div>
    <div class="col-6 col-xl-3">
        <div class="card diana-kpi diana-kpi-exito"><div class="card-body">
            <div class="d-flex align-items-center gap-2 text-muted small mb-1">
                <span class="diana-kpi-icono"><i class="bi bi-clipboard2-check"></i></span>Confirmados
            </div>
            <div class="valor"><?= (int) $kpis['confirmados_proximos'] ?></div>
        </div></div>
    </div>
    <div class="col-6 col-xl-3">
        <div class="card diana-kpi diana-kpi-neutro"><div class="card-body">
            <div class="d-flex align-items-center gap-2 text-muted small mb-1">
                <span class="diana-kpi-icono"><i class="bi bi-people"></i></span>Socios activos
            </div>
            <div class="valor"><?= (int) $kpis['socios_activos'] ?></div>
        </div></div>
    </div>
</div>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h2 class="h6 fw-semibold mb-0">Próximos eventos</h2>
    <a class="small" href="<?= e(url('eventos')) ?>">Ver todos <i class="bi bi-arrow-right"></i></a>
</div>

<?php if (!$proximosEventos): ?>
    <div class="card"><div class="card-body text-center text-muted py-5">
        <i class="bi bi-calendar-x display-6 d-block mb-2"></i>
        Sin eventos próximos. <a href="<?= e(url('eventos/crear')) ?>">Cree uno nuevo</a>.
    </div></div>
<?php else: ?>

<?php
/** Pinta una tarjeta de evento; $destacada agranda imagen y tipografía. */
$tarjeta = function (array $ev, bool $destacada) use ($iconosModalidad): void {
    $cupo = $ev['cupo'] !== null ? (int) $ev['cupo'] : null;
    $ocupacion = $cupo && $cupo > 0 ? min(100, (int) round($ev['confirmados'] / $cupo * 100)) : null;
    ?>
    <div class="card h-100 diana-evento-card <?= $destacada ? 'diana-evento-destacado' : '' ?>">
        <div class="position-relative">
            <?php if ($ev['imagen_id']): ?>
                <img class="diana-evento-card-img <?= $destacada ? 'diana-evento-card-img-grande' : '' ?>"
                     src="<?= e(url('eventos/imagen/' . (int) $ev['id'] . '/' . (int) $ev['imagen_id'])) ?>" alt="">
            <?php else: ?>
                <div class="diana-evento-card-img diana-evento-card-img-vacia <?= $destacada ? 'diana-evento-card-img-grande' : '' ?>">
                    <i class="bi bi-calendar-event"></i>
                </div>
            <?php endif; ?>
            <span class="badge diana-badge-cuando"><?= e(dias_relativo($ev['fecha_inicio'])) ?></span>
        </div>
        <div class="card-body d-flex flex-column">
            <div class="small text-muted text-uppercase mb-1"><?= e(fecha_evento($ev['fecha_inicio'])) ?><?= $ev['hora_inicio'] ? ' · ' . e(substr((string) $ev['hora_inicio'], 0, 5)) : '' ?></div>
            <a class="fw-semibold text-decoration-none d-block mb-1 <?= $destacada ? 'fs-5' : '' ?>"
               href="<?= e(url('registro/evento/' . (int) $ev['id'])) ?>"><?= e($ev['nombre']) ?></a>
            <div class="small text-muted mb-2">
                <i class="bi <?= e($iconosModalidad[$ev['modalidad']] ?? 'bi-geo-alt') ?> me-1"></i><?= e(Catalogos::MODALIDADES[$ev['modalidad']] ?? $ev['modalidad']) ?>
                <?php if ($ev['sede']): ?> · <?= e($ev['sede']) ?><?php endif; ?>
                <?php if ((float) $ev['puntos_dpc'] > 0): ?>
                    <span class="badge text-bg-light border ms-1"><?= e(number_format((float) $ev['puntos_dpc'], 2)) ?> pts DPC</span>
                <?php endif; ?>
            </div>

            <div class="mt-auto">
                <div class="d-flex justify-content-between small mb-1">
                    <span><i class="bi bi-clipboard2-check text-muted me-1"></i>Confirmados <strong><?= (int) $ev['confirmados'] ?></strong><?= $cupo ? ' / ' . $cupo : '' ?></span>
                    <span><i class="bi bi-person-check text-muted me-1"></i>Asistieron <strong><?= (int) $ev['asistieron'] ?></strong></span>
                </div>
                <?php if ($ocupacion !== null): ?>
                <div class="progress diana-progreso-cupo mb-2" role="progressbar" aria-valuenow="<?= $ocupacion ?>" aria-valuemin="0" aria-valuemax="100">
                    <div class="progress-bar" style="width: <?= $ocupacion ?>%"></div>
                </div>
                <?php endif; ?>
                <div class="d-flex gap-2">
                    <a class="btn btn-sm btn-outline-primary flex-fill" href="<?= e(url('registro/evento/' . (int) $ev['id'])) ?>">
                        <i class="bi bi-clipboard2-check me-1"></i>Confirmaciones
                    </a>
                    <a class="btn btn-sm btn-outline-secondary flex-fill" href="<?= e(url('registro/asistencia/' . (int) $ev['id'])) ?>">
                        <i class="bi bi-person-check me-1"></i>Asistencia
                    </a>
                </div>
            </div>
        </div>
    </div>
    <?php
};
?>

<div class="row g-3 mb-4">
    <div class="col-12">
        <?php $tarjeta($destacado, true); ?>
    </div>
    <?php foreach ($resto as $ev): ?>
    <div class="col-12 col-md-6 col-xl-4">
        <?php $tarjeta($ev, false); ?>
    </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<div class="row g-3">
    <div class="col-12 col-lg-6">
        <div class="card">
            <div class="card-header bg-white fw-semibold">Últimos pagos</div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead><tr><th>Fecha</th><th>Socio</th><th class="text-end">Importe</th></tr></thead>
                    <tbody>
                    <?php if (!$ultimosPagos): ?>
                        <tr><td colspan="3" class="text-muted text-center py-4">Sin pagos registrados.</td></tr>
                    <?php endif; ?>
                    <?php foreach ($ultimosPagos as $p): ?>
                        <tr>
                            <td><?= e(fecha_corta($p['fecha'])) ?></td>
                            <td><?= e($p['socio']) ?><div class="small text-muted"><?= e($p['concepto']) ?></div></td>
                            <td class="text-end"><?= e(dinero($p['importe'])) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php
use Diana\Core\Catalogos;

$iconosModalidad = ['presencial' => 'bi-geo-alt', 'linea' => 'bi-camera-video', 'hibrido' => 'bi-broadcast'];
?>
<h1 class="h4 mb-4">Inicio</h1>

<div class="row g-3 mb-4">
    <div class="col-6 col-xl-3">
        <div class="card diana-kpi diana-kpi-primario"><div class="card-body">
            <div class="d-flex align-items-center gap-2 text-muted small mb-1">
                <span class="diana-kpi-icono"><i class="bi bi-hourglass-split"></i></span>Próximo evento
            </div>
            <?php if ($proximosEventos): ?>
                <div class="valor"><?= e(dias_relativo($proximosEventos[0]['fecha_inicio'])) ?></div>
                <div class="small text-truncate" title="<?= e($proximosEventos[0]['nombre']) ?>"><?= e($proximosEventos[0]['nombre']) ?></div>
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

<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <h2 class="h6 fw-semibold mb-0">Próximos eventos</h2>
    <a class="small" href="<?= e(url('eventos')) ?>">Ver todos <i class="bi bi-arrow-right"></i></a>
</div>

<?php if (!$proximosEventos): ?>
    <div class="card"><div class="card-body text-center text-muted py-5">
        <i class="bi bi-calendar-x display-6 d-block mb-2"></i>
        Sin eventos próximos. <a href="<?= e(url('eventos/crear')) ?>">Cree uno nuevo</a>.
    </div></div>
<?php else: ?>

<div class="diana-filtro-eventos d-flex flex-wrap align-items-center gap-2 mb-3">
    <div class="diana-buscador-eventos">
        <i class="bi bi-search"></i>
        <input type="search" id="diFiltroTexto" class="form-control form-control-sm" placeholder="Buscar por nombre…" aria-label="Buscar evento">
    </div>
    <div class="btn-group btn-group-sm" role="group" aria-label="Filtrar por modalidad">
        <button type="button" class="btn btn-outline-secondary active" data-di-modalidad="">Todos</button>
        <button type="button" class="btn btn-outline-secondary" data-di-modalidad="presencial"><i class="bi bi-geo-alt me-1"></i>Presencial</button>
        <button type="button" class="btn btn-outline-secondary" data-di-modalidad="linea"><i class="bi bi-camera-video me-1"></i>En línea</button>
        <button type="button" class="btn btn-outline-secondary" data-di-modalidad="hibrido"><i class="bi bi-broadcast me-1"></i>Híbrido</button>
    </div>
</div>

<div class="card mb-4">
    <div class="list-group list-group-flush" id="diListaEventos">
        <?php foreach ($proximosEventos as $ev): ?>
            <?php
            $cupo = $ev['cupo'] !== null ? (int) $ev['cupo'] : null;
            $ocupacion = $cupo && $cupo > 0 ? min(100, (int) round($ev['confirmados'] / $cupo * 100)) : null;
            ?>
            <div class="list-group-item diana-evento-fila" data-di-nombre="<?= e(mb_strtolower($ev['nombre'])) ?>" data-di-modalidad="<?= e($ev['modalidad']) ?>">
                <div class="diana-evento-fila-info">
                    <div class="d-flex flex-wrap align-items-center gap-2 mb-1">
                        <span class="badge diana-badge-cuando"><?= e(dias_relativo($ev['fecha_inicio'])) ?></span>
                        <span class="small text-muted"><?= e(fecha_evento($ev['fecha_inicio'])) ?><?= $ev['hora_inicio'] ? ' · ' . e(substr((string) $ev['hora_inicio'], 0, 5)) : '' ?></span>
                    </div>
                    <a class="fw-semibold text-decoration-none d-block mb-1" href="<?= e(url('registro/evento/' . (int) $ev['id'])) ?>"><?= e($ev['nombre']) ?></a>
                    <div class="small text-muted mb-2">
                        <i class="bi <?= e($iconosModalidad[$ev['modalidad']] ?? 'bi-geo-alt') ?> me-1"></i><?= e(Catalogos::MODALIDADES[$ev['modalidad']] ?? $ev['modalidad']) ?>
                        <?php if ($ev['sede']): ?> · <?= e($ev['sede']) ?><?php endif; ?>
                        <?php if ((float) $ev['puntos_dpc'] > 0): ?>
                            <span class="badge text-bg-light border ms-1"><?= e(number_format((float) $ev['puntos_dpc'], 2)) ?> pts DPC</span>
                        <?php endif; ?>
                        <?php if ($ev['expositores']): ?>
                            <div><i class="bi bi-person-badge me-1"></i><?= e($ev['expositores']) ?></div>
                        <?php endif; ?>
                    </div>
                    <div class="d-flex flex-wrap justify-content-between small gap-2 mb-1" style="max-width: 420px;">
                        <span><i class="bi bi-clipboard2-check text-muted me-1"></i>Confirmados <strong><?= (int) $ev['confirmados'] ?></strong><?= $cupo ? ' / ' . $cupo : '' ?></span>
                        <span><i class="bi bi-person-check text-muted me-1"></i>Asistieron <strong><?= (int) $ev['asistieron'] ?></strong></span>
                    </div>
                    <?php if ($ocupacion !== null): ?>
                    <div class="progress diana-progreso-cupo mb-2" style="max-width: 420px;" role="progressbar" aria-valuenow="<?= $ocupacion ?>" aria-valuemin="0" aria-valuemax="100">
                        <div class="progress-bar" style="width: <?= $ocupacion ?>%"></div>
                    </div>
                    <?php endif; ?>
                    <div class="d-flex gap-2">
                        <a class="btn btn-sm btn-outline-primary" href="<?= e(url('registro/evento/' . (int) $ev['id'])) ?>">
                            <i class="bi bi-clipboard2-check me-1"></i>Confirmaciones
                        </a>
                        <a class="btn btn-sm btn-outline-secondary" href="<?= e(url('registro/asistencia/' . (int) $ev['id'])) ?>">
                            <i class="bi bi-person-check me-1"></i>Asistencia
                        </a>
                    </div>
                </div>
                <?php if ($ev['imagen_id']): ?>
                    <img class="diana-evento-fila-img" src="<?= e(url('eventos/imagen/' . (int) $ev['id'] . '/' . (int) $ev['imagen_id'])) ?>" alt="">
                <?php else: ?>
                    <div class="diana-evento-fila-img diana-evento-fila-img-vacia"><i class="bi bi-calendar-event"></i></div>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
        <div class="list-group-item text-center text-muted py-4" id="diSinResultados" hidden>
            <i class="bi bi-search me-1"></i>Ningún evento coincide con el filtro.
        </div>
    </div>
</div>

<script>
(function () {
    var texto = document.getElementById('diFiltroTexto');
    var chips = document.querySelectorAll('[data-di-modalidad]');
    var filas = document.querySelectorAll('#diListaEventos .diana-evento-fila');
    var sinResultados = document.getElementById('diSinResultados');
    var modalidadActiva = '';

    function aplicar() {
        var q = (texto.value || '').toLowerCase().trim();
        var visibles = 0;
        filas.forEach(function (fila) {
            var coincideTexto = q === '' || (fila.dataset.diNombre || '').indexOf(q) !== -1;
            var coincideModalidad = modalidadActiva === '' || fila.dataset.diModalidad === modalidadActiva;
            var visible = coincideTexto && coincideModalidad;
            fila.hidden = !visible;
            if (visible) visibles++;
        });
        if (sinResultados) sinResultados.hidden = visibles !== 0;
    }

    if (texto) texto.addEventListener('input', aplicar);
    chips.forEach(function (chip) {
        chip.addEventListener('click', function () {
            chips.forEach(function (c) { c.classList.remove('active'); });
            chip.classList.add('active');
            modalidadActiva = chip.getAttribute('data-di-modalidad');
            aplicar();
        });
    });
})();
</script>
<?php endif; ?>

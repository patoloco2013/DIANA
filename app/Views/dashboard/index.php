<h1 class="h4 mb-4">Inicio</h1>

<div class="row g-3 mb-4">
    <div class="col-6 col-xl-3">
        <div class="card diana-kpi"><div class="card-body">
            <div class="text-muted small"><i class="bi bi-people me-1"></i>Socios activos</div>
            <div class="valor"><?= (int) $kpis['socios_activos'] ?></div>
        </div></div>
    </div>
    <div class="col-6 col-xl-3">
        <div class="card diana-kpi"><div class="card-body">
            <div class="text-muted small"><i class="bi bi-calendar3 me-1"></i>Eventos próximos</div>
            <div class="valor"><?= (int) $kpis['eventos_proximos'] ?></div>
        </div></div>
    </div>
    <div class="col-6 col-xl-3">
        <div class="card diana-kpi"><div class="card-body">
            <div class="text-muted small"><i class="bi bi-wallet2 me-1"></i>Cartera por cobrar</div>
            <div class="valor"><?= e(dinero($kpis['cartera'])) ?></div>
        </div></div>
    </div>
    <div class="col-6 col-xl-3">
        <div class="card diana-kpi"><div class="card-body">
            <div class="text-muted small"><i class="bi bi-cash-coin me-1"></i>Cobrado este mes</div>
            <div class="valor"><?= e(dinero($kpis['cobrado_mes'])) ?></div>
        </div></div>
    </div>
</div>

<div class="row g-3">
    <div class="col-12 col-lg-7">
        <div class="card">
            <div class="card-header bg-white fw-semibold">Próximos eventos</div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead><tr><th>Evento</th><th>Fecha</th><th>Sede</th><th class="text-end">Registrados</th></tr></thead>
                    <tbody>
                    <?php if (!$proximosEventos): ?>
                        <tr><td colspan="4" class="text-muted text-center py-4">Sin eventos próximos.</td></tr>
                    <?php endif; ?>
                    <?php foreach ($proximosEventos as $ev): ?>
                        <tr>
                            <td><a href="<?= e(url('eventos/editar/' . $ev['id'])) ?>"><?= e($ev['nombre']) ?></a></td>
                            <td><?= e(fecha_corta($ev['fecha_inicio'])) ?></td>
                            <td><?= e($ev['sede']) ?></td>
                            <td class="text-end"><?= (int) $ev['registrados'] ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="col-12 col-lg-5">
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

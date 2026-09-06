<h1 class="h4 mb-4">Reportes</h1>

<div class="row g-3">
    <?php
    $reportes = [
        ['reportes/saldos',    'bi-wallet2',      'Saldos de socios', 'Cargos, pagos y saldo de cada socio.'],
        ['reportes/morosidad', 'bi-exclamation-triangle', 'Morosidad', 'Cargos vencidos pendientes de pago.'],
        ['reportes/cobranza',  'bi-cash-stack',   'Cobranza',         'Pagos recibidos por periodo.'],
        ['reportes/eventos',   'bi-calendar3',    'Eventos',          'Asistencia e ingresos por evento.'],
    ];
    ?>
    <?php foreach ($reportes as [$ruta, $icono, $nombre, $desc]): ?>
    <div class="col-12 col-sm-6 col-xl-3">
        <a class="card text-decoration-none h-100" href="<?= e(url($ruta)) ?>">
            <div class="card-body">
                <i class="bi <?= e($icono) ?> fs-3 d-block mb-2" style="color: var(--diana-primario);"></i>
                <div class="fw-semibold"><?= e($nombre) ?></div>
                <div class="small text-muted"><?= e($desc) ?></div>
            </div>
        </a>
    </div>
    <?php endforeach; ?>
</div>

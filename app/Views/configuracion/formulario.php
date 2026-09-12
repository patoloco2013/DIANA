<?php
/** Variables: $pestana, $fiscal, $correo, $pac, $constancia, $mascara */
use Diana\Core\Auth;

$pestanas = [
    'fiscal'     => ['Fiscal',    'bi-bank'],
    'correo'     => ['Correo',    'bi-envelope-at'],
    'pac'        => ['SAT PAC',   'bi-stamp'],
    'constancia' => ['Constancia', 'bi-award'],
];
?>
<div class="d-flex flex-wrap align-items-center gap-3 mb-4">
    <div>
        <h1 class="h4 mb-0">Configuración</h1>
        <div class="small text-muted"><?= e(Auth::colegio()['nombre_corto'] ?? '') ?></div>
    </div>
</div>

<ul class="nav nav-tabs diana-tabs" role="tablist">
    <?php foreach ($pestanas as $clave => [$etiqueta, $icono]): ?>
    <li class="nav-item" role="presentation">
        <button class="nav-link <?= $pestana === $clave ? 'active' : '' ?>"
                data-bs-toggle="tab" data-bs-target="#tab-<?= $clave ?>" type="button" role="tab">
            <i class="bi <?= $icono ?> me-1"></i><?= $etiqueta ?>
        </button>
    </li>
    <?php endforeach; ?>
</ul>

<div class="card diana-card-tabs">
    <div class="card-body">
        <div class="tab-content">
            <div class="tab-pane fade <?= $pestana === 'fiscal' ? 'show active' : '' ?>" id="tab-fiscal" role="tabpanel">
                <?php require __DIR__ . '/_fiscal.php'; ?>
            </div>
            <div class="tab-pane fade <?= $pestana === 'correo' ? 'show active' : '' ?>" id="tab-correo" role="tabpanel">
                <?php require __DIR__ . '/_correo.php'; ?>
            </div>
            <div class="tab-pane fade <?= $pestana === 'pac' ? 'show active' : '' ?>" id="tab-pac" role="tabpanel">
                <?php require __DIR__ . '/_pac.php'; ?>
            </div>
            <div class="tab-pane fade <?= $pestana === 'constancia' ? 'show active' : '' ?>" id="tab-constancia" role="tabpanel">
                <?php require __DIR__ . '/_constancia.php'; ?>
            </div>
        </div>
    </div>
</div>

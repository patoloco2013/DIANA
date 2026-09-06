<?php
use Diana\Core\Auth;
use Diana\Core\Csrf;

$usuario = Auth::usuario();
$colegio = Auth::colegio();
$colorPrimario = $colegio['color_primario'] ?? '#1f0512';
$seccionActiva = strtolower(explode('/', trim($_GET['r'] ?? '', '/'))[0] ?? 'dashboard');

/** Módulos del menú: ruta => [etiqueta, icono, módulo RBAC] */
$menu = [
    'dashboard' => ['Inicio',        'bi-house',        'dashboard'],
    'socios'    => ['Socios',        'bi-people',       'socios'],
    'eventos'   => ['Eventos',       'bi-calendar3',    'eventos'],
    'registro'  => ['Registro',      'bi-clipboard-check', 'registro'],
    'cuentas'   => ['Cuentas',       'bi-cash-coin',    'cuentas'],
    'reportes'  => ['Reportes',      'bi-graph-up',     'reportes'],
    'usuarios'  => ['Usuarios',      'bi-person-gear',  'usuarios'],
    'colegios'  => ['Colegios',      'bi-building-gear','colegios'],
];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($titulo) ?> · <?= e(cfg('app.nombre')) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="<?= e(asset('assets/css/app.css')) ?>" rel="stylesheet">
    <style>:root { --diana-primario: <?= e($colorPrimario) ?>; }</style>
</head>
<body>
<nav class="navbar navbar-dark diana-navbar sticky-top">
    <div class="container-fluid">
        <button class="btn btn-outline-light d-lg-none" type="button" data-bs-toggle="offcanvas" data-bs-target="#menuLateral">
            <i class="bi bi-list"></i>
        </button>
        <span class="navbar-brand fw-bold">
            <?= e(cfg('app.nombre')) ?>
            <small class="fw-normal opacity-75 ms-2"><?= e($colegio['nombre_corto'] ?? '') ?></small>
        </span>
        <div class="d-flex align-items-center gap-2">
            <?php if (Auth::esGlobal()): ?>
            <form method="post" action="<?= e(url('auth/colegio')) ?>" class="d-none d-sm-block">
                <?= Csrf::campo() ?>
                <select name="colegio_id" class="form-select form-select-sm" onchange="this.form.submit()" aria-label="Cambiar colegio">
                    <?php foreach (($colegios_activos ?? \Diana\Core\Database::todas('SELECT id, nombre_corto FROM colegios WHERE activo = 1 ORDER BY nombre_corto')) as $c): ?>
                    <option value="<?= (int) $c['id'] ?>" <?= (int) $c['id'] === (int) ($colegio['id'] ?? 0) ? 'selected' : '' ?>><?= e($c['nombre_corto']) ?></option>
                    <?php endforeach; ?>
                </select>
            </form>
            <?php endif; ?>
            <div class="dropdown">
                <button class="btn btn-outline-light btn-sm dropdown-toggle" data-bs-toggle="dropdown">
                    <i class="bi bi-person-circle me-1"></i><span class="d-none d-sm-inline"><?= e($usuario['nombre'] ?? '') ?></span>
                </button>
                <ul class="dropdown-menu dropdown-menu-end">
                    <li><span class="dropdown-item-text small text-muted"><?= e($usuario['rol'] ?? '') ?></span></li>
                    <li><hr class="dropdown-divider"></li>
                    <li>
                        <form method="post" action="<?= e(url('auth/salir')) ?>">
                            <?= Csrf::campo() ?>
                            <button class="dropdown-item" type="submit"><i class="bi bi-box-arrow-right me-1"></i>Cerrar sesión</button>
                        </form>
                    </li>
                </ul>
            </div>
        </div>
    </div>
</nav>

<div class="d-flex">
    <aside class="offcanvas-lg offcanvas-start diana-sidebar" id="menuLateral" tabindex="-1">
        <div class="offcanvas-header d-lg-none">
            <span class="offcanvas-title fw-bold text-white"><?= e(cfg('app.nombre')) ?></span>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas" data-bs-target="#menuLateral"></button>
        </div>
        <nav class="nav flex-column py-2">
            <?php foreach ($menu as $ruta => [$etiqueta, $icono, $modulo]): ?>
                <?php if (!Auth::puede($modulo)) continue; ?>
                <a class="nav-link diana-link <?= $seccionActiva === $ruta ? 'active' : '' ?>" href="<?= e(url($ruta)) ?>">
                    <i class="bi <?= e($icono) ?> me-2"></i><?= e($etiqueta) ?>
                </a>
            <?php endforeach; ?>
        </nav>
    </aside>

    <main class="flex-grow-1 p-3 p-md-4 diana-main">
        <?php foreach (flashes() as $f): ?>
            <div class="alert alert-<?= e($f['tipo']) ?> alert-dismissible fade show">
                <?= e($f['mensaje']) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endforeach; ?>
        <?= $contenido ?>
    </main>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

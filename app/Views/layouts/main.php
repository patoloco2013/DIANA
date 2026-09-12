<?php
use Diana\Core\Auth;
use Diana\Core\Csrf;
use Diana\Core\Database;

$usuario = Auth::usuario();
$colegio = Auth::colegio();
$primario = $colegio['color_primario'] ?? '#1f0512';
$seccionActiva = strtolower(explode('/', trim($_GET['r'] ?? '', '/'))[0] ?? 'dashboard');

/** Secciones del menú: título => [ruta => [etiqueta, icono, módulo RBAC]] */
$menu = [
    'Operación' => [
        'dashboard' => ['Inicio',   'bi-grid-1x2',         'dashboard'],
        'socios'    => ['Socios',   'bi-people',           'socios'],
        'eventos'   => ['Eventos',  'bi-calendar-event',   'eventos'],
        'registro'  => ['Registro', 'bi-clipboard2-check', 'registro'],
        'cuentas'   => ['Cuentas',  'bi-wallet2',          'cuentas'],
    ],
    'Análisis' => [
        'reportes'  => ['Reportes', 'bi-bar-chart-line',   'reportes'],
    ],
    'Administración' => [
        'usuarios'      => ['Usuarios',      'bi-person-gear', 'usuarios'],
        'colegios'      => ['Colegios',      'bi-buildings',   'colegios'],
        'configuracion' => ['Configuración', 'bi-gear',        'configuracion'],
    ],
];

$colegiosActivos = Auth::esGlobal()
    ? Database::todas('SELECT id, nombre_corto FROM colegios WHERE activo = 1 ORDER BY nombre_corto')
    : [];
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
    <style>
        :root {
            --diana-primario: <?= e($primario) ?>;
            --diana-primario-oscuro: <?= e(color_sombra($primario, -0.35)) ?>;
            --diana-primario-suave: <?= e(color_sombra($primario, 0.90)) ?>;
            --diana-primario-texto: <?= e(color_contraste($primario)) ?>;
        }
    </style>
</head>
<body>
<div class="diana-app">

    <aside class="diana-sidebar offcanvas-lg offcanvas-start" id="menuLateral" tabindex="-1" aria-label="Menú principal">
        <div class="diana-brand">
            <span class="diana-brand-logo"><?= e(mb_substr($colegio['clave'] ?? 'D', 0, 3)) ?></span>
            <span class="diana-brand-text">
                <strong><?= e(cfg('app.nombre')) ?></strong>
                <small><?= e($colegio['nombre_corto'] ?? '') ?></small>
            </span>
            <button type="button" class="btn-close btn-close-white d-lg-none ms-auto" data-bs-dismiss="offcanvas" data-bs-target="#menuLateral" aria-label="Cerrar"></button>
        </div>

        <?php if ($colegiosActivos): ?>
        <form method="post" action="<?= e(url('auth/colegio')) ?>" class="diana-colegio-form">
            <?= Csrf::campo() ?>
            <label class="diana-section-label" for="selColegio">Colegio activo</label>
            <select id="selColegio" name="colegio_id" class="form-select form-select-sm diana-select" onchange="this.form.submit()">
                <?php foreach ($colegiosActivos as $c): ?>
                <option value="<?= (int) $c['id'] ?>" <?= (int) $c['id'] === (int) ($colegio['id'] ?? 0) ? 'selected' : '' ?>><?= e($c['nombre_corto']) ?></option>
                <?php endforeach; ?>
            </select>
        </form>
        <?php endif; ?>

        <nav class="diana-nav">
            <?php foreach ($menu as $seccion => $items): ?>
                <?php
                $visibles = array_filter($items, fn($it) => Auth::puede($it[2]));
                if (!$visibles) continue;
                ?>
                <div class="diana-section-label"><?= e($seccion) ?></div>
                <?php foreach ($visibles as $ruta => [$etiqueta, $icono]): ?>
                <a class="diana-link <?= $seccionActiva === $ruta ? 'active' : '' ?>" href="<?= e(url($ruta)) ?>"
                   <?= $seccionActiva === $ruta ? 'aria-current="page"' : '' ?>>
                    <i class="bi <?= e($icono) ?>"></i><span><?= e($etiqueta) ?></span>
                </a>
                <?php endforeach; ?>
            <?php endforeach; ?>
        </nav>

        <div class="diana-user">
            <span class="diana-avatar"><?= e(iniciales($usuario['nombre'] ?? '')) ?></span>
            <span class="diana-user-text">
                <strong><?= e($usuario['nombre'] ?? '') ?></strong>
                <small><?= e($usuario['rol'] ?? '') ?></small>
            </span>
            <form method="post" action="<?= e(url('auth/salir')) ?>" class="ms-auto">
                <?= Csrf::campo() ?>
                <button class="diana-icon-btn" type="submit" title="Cerrar sesión" aria-label="Cerrar sesión">
                    <i class="bi bi-box-arrow-right"></i>
                </button>
            </form>
        </div>
    </aside>

    <div class="diana-body">
        <header class="diana-topbar">
            <button class="diana-icon-btn diana-icon-btn-dark d-lg-none" type="button"
                    data-bs-toggle="offcanvas" data-bs-target="#menuLateral" aria-label="Abrir menú">
                <i class="bi bi-list"></i>
            </button>
            <?php
            // Ruta de navegación (grupo › sección); el título de la página lo
            // pone cada vista en su propio encabezado.
            $grupoActivo = $seccionNombre = '';
            foreach ($menu as $grupo => $items) {
                if (isset($items[$seccionActiva])) {
                    $grupoActivo = $grupo;
                    $seccionNombre = $items[$seccionActiva][0];
                }
            }
            ?>
            <nav class="diana-breadcrumb" aria-label="Ubicación">
                <?php if ($grupoActivo !== ''): ?>
                    <span><?= e($grupoActivo) ?></span>
                    <i class="bi bi-chevron-right"></i>
                    <strong><?= e($seccionNombre) ?></strong>
                <?php else: ?>
                    <strong><?= e($titulo) ?></strong>
                <?php endif; ?>
            </nav>
            <span class="diana-chip ms-auto d-none d-sm-inline-flex">
                <i class="bi bi-building"></i><?= e($colegio['nombre_corto'] ?? '') ?>
            </span>
        </header>

        <main class="diana-main">
            <?php foreach (flashes() as $f): ?>
                <div class="alert alert-<?= e($f['tipo']) ?> alert-dismissible fade show">
                    <?= e($f['mensaje']) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button>
                </div>
            <?php endforeach; ?>
            <?= $contenido ?>
        </main>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

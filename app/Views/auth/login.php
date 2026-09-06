<?php use Diana\Core\Csrf; ?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Acceso · <?= e(cfg('app.nombre')) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="<?= e(url('assets/css/app.css')) ?>" rel="stylesheet">
</head>
<body class="diana-login">
    <main class="card shadow-lg" style="width: min(92vw, 380px);">
        <div class="card-body p-4">
            <h1 class="h4 text-center mb-1"><?= e(cfg('app.nombre')) ?></h1>
            <p class="text-center text-muted small mb-4">Sistema Integral para Colegios</p>

            <?php if (!empty($error)): ?>
                <div class="alert alert-danger py-2 small"><?= e($error) ?></div>
            <?php endif; ?>

            <form method="post" action="<?= e(url('auth/login')) ?>" autocomplete="off">
                <?= Csrf::campo() ?>
                <div class="mb-3">
                    <label class="form-label" for="usuario">Usuario</label>
                    <input class="form-control" type="text" id="usuario" name="usuario"
                           maxlength="50" required autofocus>
                </div>
                <div class="mb-4">
                    <label class="form-label" for="password">Contraseña</label>
                    <input class="form-control" type="password" id="password" name="password"
                           maxlength="100" required>
                </div>
                <button class="btn btn-primary w-100" type="submit">
                    <i class="bi bi-box-arrow-in-right me-1"></i>Entrar
                </button>
            </form>
        </div>
    </main>
</body>
</html>

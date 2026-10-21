<?php
/** Certificados SAT. Variables: $certificados, $historial, $cifradoListo */
use Diana\Controllers\SatController;
use Diana\Core\Csrf;

$ahora = time();
$vigencia = function (array $c) use ($ahora): array {
    $dias = (int) floor((strtotime($c['valido_hasta']) - $ahora) / 86400);
    return match (true) {
        $dias < 0                            => ['danger',  'Vencido'],
        $dias <= SatController::DIAS_AVISO   => ['warning', "Vence en {$dias} días"],
        default                              => ['success', 'Vigente'],
    };
};
$hayActivo = [];
foreach ($certificados as $c) {
    if ((int) $c['activo'] === 1) { $hayActivo[$c['tipo']] = true; }
}
?>
<h1 class="h4 mb-1">Certificados SAT</h1>
<p class="text-muted mb-4">
    Certificados del colegio para facturación electrónica (CSD) y e.firma.
    Al subirlos se comprueba que la llave privada abra con su contraseña y
    corresponda al certificado. La llave y la contraseña quedan protegidas en
    el servidor y nunca se pueden descargar.
</p>

<?php if (!$cifradoListo): ?>
<div class="alert alert-warning">
    <i class="bi bi-exclamation-triangle me-1"></i>
    Falta configurar la clave de cifrado (<code>seguridad.clave_cifrado</code> en <code>config/config.php</code>).
    Hasta entonces no se pueden subir certificados. Pida al administrador del servidor que la configure.
</div>
<?php endif; ?>

<?php if (!isset($hayActivo['csd'])): ?>
<div class="alert alert-info">
    <i class="bi bi-info-circle me-1"></i>No hay un CSD activo: sin él no se podrán emitir facturas.
</div>
<?php endif; ?>

<div class="row g-4">
    <div class="col-12 col-lg-4">
        <h2 class="h6 fw-semibold mb-3">Subir certificado</h2>
        <form method="post" action="<?= e(url('sat/subir')) ?>" enctype="multipart/form-data" autocomplete="off">
            <?= Csrf::campo() ?>
            <fieldset <?= $cifradoListo ? '' : 'disabled' ?>>
            <div class="mb-3">
                <label class="form-label" for="sat_cer">Certificado (.cer) *</label>
                <input class="form-control" id="sat_cer" name="cer" type="file" accept=".cer" required>
            </div>
            <div class="mb-3">
                <label class="form-label" for="sat_key">Llave privada (.key) *</label>
                <input class="form-control" id="sat_key" name="key" type="file" accept=".key" required>
            </div>
            <div class="mb-3">
                <label class="form-label" for="sat_password">Contraseña de la llave privada *</label>
                <input class="form-control" id="sat_password" name="password" type="password" autocomplete="new-password" required>
            </div>
            <div class="form-check mb-3">
                <input class="form-check-input" type="checkbox" id="sat_activar" name="activar" value="1" checked>
                <label class="form-check-label" for="sat_activar">Usar como certificado activo</label>
                <div class="form-text">Reemplaza al activo del mismo tipo (el anterior se conserva inactivo).</div>
            </div>
            <button class="btn btn-primary w-100" type="submit"><i class="bi bi-upload me-1"></i>Subir y validar</button>
            </fieldset>
        </form>
    </div>

    <div class="col-12 col-lg-8">
        <h2 class="h6 fw-semibold mb-3">Certificados <span class="badge text-bg-secondary"><?= count($certificados) ?></span></h2>
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead><tr><th>Tipo</th><th>RFC / titular</th><th class="d-none d-md-table-cell">No. certificado</th><th>Vigencia</th><th class="text-end">Acciones</th></tr></thead>
                <tbody>
                <?php if (!$certificados): ?>
                    <tr><td colspan="5" class="text-center text-muted py-4">Aún no hay certificados.</td></tr>
                <?php endif; ?>
                <?php foreach ($certificados as $c): [$color, $leyenda] = $vigencia($c); ?>
                    <tr>
                        <td>
                            <?= e(SatController::TIPOS[$c['tipo']] ?? $c['tipo']) ?>
                            <?php if ((int) $c['activo'] === 1): ?><div><span class="badge text-bg-primary">Activo</span></div><?php endif; ?>
                        </td>
                        <td>
                            <div class="fw-semibold"><?= e($c['rfc']) ?></div>
                            <div class="small text-muted"><?= e($c['titular']) ?></div>
                        </td>
                        <td class="d-none d-md-table-cell"><code><?= e($c['numero_certificado']) ?></code></td>
                        <td class="small">
                            <?= e(fecha_corta($c['valido_desde'])) ?> al <?= e(fecha_corta($c['valido_hasta'])) ?>
                            <div><span class="badge text-bg-<?= $color ?>"><?= e($leyenda) ?></span></div>
                        </td>
                        <td class="text-end text-nowrap">
                            <a class="btn btn-sm btn-outline-secondary" title="Descargar .cer"
                               href="<?= e(url('sat/descargar/' . (int) $c['id'])) ?>"><i class="bi bi-download"></i></a>
                            <?php if ((int) $c['activo'] === 0 && $color !== 'danger'): ?>
                            <form class="d-inline" method="post" action="<?= e(url('sat/activar/' . (int) $c['id'])) ?>">
                                <?= Csrf::campo() ?>
                                <button class="btn btn-sm btn-outline-primary" title="Usar como activo"><i class="bi bi-check2-circle"></i></button>
                            </form>
                            <?php endif; ?>
                            <form class="d-inline" method="post" action="<?= e(url('sat/quitar/' . (int) $c['id'])) ?>"
                                  onsubmit="return confirm('¿Quitar este certificado? Se borrarán del servidor el .cer, el .key y la contraseña. No se puede deshacer.');">
                                <?= Csrf::campo() ?>
                                <button class="btn btn-sm btn-outline-danger" title="Quitar"><i class="bi bi-trash"></i></button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <?php if ($historial): ?>
        <details class="mt-3">
            <summary class="small text-muted">Historial de certificados quitados (<?= count($historial) ?>)</summary>
            <div class="table-responsive mt-2">
                <table class="table table-sm small text-muted">
                    <thead><tr><th>Tipo</th><th>RFC</th><th>No. certificado</th><th>Vencía</th><th>Quitado</th></tr></thead>
                    <tbody>
                    <?php foreach ($historial as $h): ?>
                        <tr>
                            <td><?= e(SatController::TIPOS[$h['tipo']] ?? $h['tipo']) ?></td>
                            <td><?= e($h['rfc']) ?></td>
                            <td><code><?= e($h['numero_certificado']) ?></code></td>
                            <td><?= e(fecha_corta($h['valido_hasta'])) ?></td>
                            <td><?= e(fecha_corta($h['eliminado_en'])) ?> · <?= e($h['eliminado_por_nombre'] ?? '') ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </details>
        <?php endif; ?>
    </div>
</div>

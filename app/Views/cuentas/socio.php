<?php use Diana\Core\Csrf; ?>
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-1">
    <h1 class="h4 mb-0"><?= e(trim(($socio['titulo'] ?? '') . ' ' . $socio['nombre'])) ?></h1>
    <a class="btn btn-outline-secondary btn-sm" href="<?= e(url('cuentas')) ?>">
        <i class="bi bi-arrow-left me-1"></i>Cuentas
    </a>
</div>
<p class="text-muted mb-4">
    Socio No. <?= e($socio['numero']) ?> ·
    Saldo actual:
    <strong class="<?= $saldo > 0 ? 'text-danger' : 'text-success' ?>"><?= e(dinero($saldo)) ?></strong>
</p>

<div class="row g-3">
    <div class="col-12 col-lg-4">
        <div class="card">
            <div class="card-header bg-white fw-semibold">Nuevo movimiento</div>
            <div class="card-body">
                <form method="post" action="<?= e(url('cuentas/movimiento/' . $socio['id'])) ?>">
                    <?= Csrf::campo() ?>
                    <div class="mb-3">
                        <label class="form-label" for="tipo">Tipo</label>
                        <select class="form-select" id="tipo" name="tipo"
                                onchange="document.getElementById('grupo-pago').hidden = this.value !== 'pago';
                                          document.getElementById('grupo-venc').hidden = this.value !== 'cargo';">
                            <option value="cargo">Cargo</option>
                            <option value="pago">Pago (abono)</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="concepto">Concepto *</label>
                        <input class="form-control" id="concepto" name="concepto" maxlength="200" required
                               placeholder="Cuota anual, inscripción...">
                    </div>
                    <div class="row g-2">
                        <div class="col-6 mb-3">
                            <label class="form-label" for="importe">Importe *</label>
                            <input class="form-control" id="importe" name="importe" type="number" step="0.01" min="0.01" required>
                        </div>
                        <div class="col-6 mb-3">
                            <label class="form-label" for="fecha">Fecha</label>
                            <input class="form-control" id="fecha" name="fecha" type="date" value="<?= e(date('Y-m-d')) ?>">
                        </div>
                    </div>
                    <div class="mb-3" id="grupo-venc">
                        <label class="form-label" for="fecha_vencimiento">Vencimiento (cargos)</label>
                        <input class="form-control" id="fecha_vencimiento" name="fecha_vencimiento" type="date">
                    </div>
                    <div class="mb-3" id="grupo-pago" hidden>
                        <label class="form-label" for="forma_pago">Forma de pago</label>
                        <select class="form-select" id="forma_pago" name="forma_pago">
                            <?php foreach ($formasPago as $fp): ?>
                            <option value="<?= $fp ?>"><?= ucfirst($fp) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="referencia">Referencia / folio</label>
                        <input class="form-control" id="referencia" name="referencia" maxlength="60">
                    </div>
                    <button class="btn btn-primary w-100" type="submit"><i class="bi bi-plus-lg me-1"></i>Registrar</button>
                </form>
            </div>
        </div>
    </div>

    <div class="col-12 col-lg-8">
        <div class="card">
            <div class="card-header bg-white fw-semibold">Movimientos</div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Fecha</th><th>Concepto</th>
                            <th class="text-end">Cargo</th><th class="text-end">Pago</th><th></th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if (!$movimientos): ?>
                        <tr><td colspan="5" class="text-center text-muted py-4">Sin movimientos.</td></tr>
                    <?php endif; ?>
                    <?php foreach ($movimientos as $m): ?>
                        <tr class="<?= $m['estatus'] === 'cancelado' ? 'text-decoration-line-through text-muted' : '' ?>">
                            <td class="text-nowrap"><?= e(fecha_corta($m['fecha'])) ?></td>
                            <td>
                                <?= e($m['concepto']) ?>
                                <?php if ($m['evento_nombre']): ?><div class="small text-muted"><?= e($m['evento_nombre']) ?></div><?php endif; ?>
                                <?php if ($m['referencia']): ?><div class="small text-muted">Ref: <?= e($m['referencia']) ?></div><?php endif; ?>
                            </td>
                            <td class="text-end"><?= $m['tipo'] === 'cargo' ? e(dinero($m['importe'])) : '' ?></td>
                            <td class="text-end text-success"><?= $m['tipo'] === 'pago' ? e(dinero($m['importe'])) : '' ?></td>
                            <td class="text-end">
                                <?php if ($m['estatus'] === 'vigente'): ?>
                                <form method="post" action="<?= e(url('cuentas/cancelar/' . $socio['id'] . '/' . $m['id'])) ?>"
                                      onsubmit="return confirm('¿Cancelar este movimiento?');">
                                    <?= Csrf::campo() ?>
                                    <button class="btn btn-sm btn-outline-danger" title="Cancelar"><i class="bi bi-x-lg"></i></button>
                                </form>
                                <?php else: ?>
                                <span class="badge text-bg-secondary">cancelado</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

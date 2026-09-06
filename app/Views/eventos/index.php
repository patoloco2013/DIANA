<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
    <h1 class="h4 mb-0">Eventos</h1>
    <div class="d-flex gap-2">
        <form method="get" action="<?= e(url('eventos')) ?>" class="d-flex gap-2">
            <input type="hidden" name="r" value="eventos">
            <input class="form-control" type="search" name="q" value="<?= e($q) ?>"
                   placeholder="Nombre, expositor o sede" style="min-width: 220px;">
            <button class="btn btn-outline-secondary" type="submit"><i class="bi bi-search"></i></button>
        </form>
        <a class="btn btn-primary text-nowrap" href="<?= e(url('eventos/crear')) ?>">
            <i class="bi bi-calendar-plus me-1"></i>Nuevo
        </a>
    </div>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead>
                <tr>
                    <th>Evento</th><th>Fecha</th><th class="d-none d-md-table-cell">Sede</th>
                    <th class="d-none d-lg-table-cell text-end">EPC</th>
                    <th class="text-end d-none d-md-table-cell">Precio socio</th>
                    <th class="text-end">Reg.</th><th>Estatus</th><th class="text-end">Acciones</th>
                </tr>
            </thead>
            <tbody>
            <?php if (!$eventos): ?>
                <tr><td colspan="8" class="text-center text-muted py-4">Sin resultados.</td></tr>
            <?php endif; ?>
            <?php foreach ($eventos as $ev): ?>
                <tr>
                    <td>
                        <?= e($ev['nombre']) ?>
                        <?php if ($ev['expositores']): ?><div class="small text-muted"><?= e($ev['expositores']) ?></div><?php endif; ?>
                    </td>
                    <td class="text-nowrap"><?= e(fecha_corta($ev['fecha_inicio'])) ?></td>
                    <td class="d-none d-md-table-cell"><?= e($ev['sede']) ?></td>
                    <td class="d-none d-lg-table-cell text-end"><?= e($ev['puntos_epc']) ?></td>
                    <td class="text-end d-none d-md-table-cell"><?= e(dinero($ev['precio_socio'])) ?></td>
                    <td class="text-end"><?= (int) $ev['registrados'] ?></td>
                    <td>
                        <?php $color = ['publicado' => 'success', 'borrador' => 'secondary', 'cerrado' => 'dark', 'cancelado' => 'danger'][$ev['estatus']] ?? 'secondary'; ?>
                        <span class="badge text-bg-<?= $color ?>"><?= e($ev['estatus']) ?></span>
                    </td>
                    <td class="text-end text-nowrap">
                        <a class="btn btn-sm btn-outline-secondary" title="Registro de asistentes"
                           href="<?= e(url('registro/evento/' . $ev['id'])) ?>"><i class="bi bi-clipboard-check"></i></a>
                        <a class="btn btn-sm btn-outline-primary" title="Editar"
                           href="<?= e(url('eventos/editar/' . $ev['id'])) ?>"><i class="bi bi-pencil"></i></a>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

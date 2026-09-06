<?php
/** Pestaña Precios del evento. Variables: $evento, $precios */
use Diana\Core\Catalogos;
use Diana\Core\Csrf;
use Diana\Controllers\EventosController;

$modalidades = EventosController::modalidadesDe($evento['modalidad']);
?>
<h2 class="h6 fw-semibold mb-1">Precios por categoría de asistente</h2>
<p class="small text-muted">
    Deje vacío el precio de una categoría que no aplique a este evento; en cero significa sin costo.
    <?php if (count($modalidades) > 1): ?>
        Al ser híbrido se captura un precio para presencial y otro para en línea.
    <?php endif; ?>
</p>

<form method="post" action="<?= e(url('eventos/guardarPrecios/' . (int) $evento['id'])) ?>">
    <?= Csrf::campo() ?>
    <div class="table-responsive" style="max-width: 720px;">
        <table class="table align-middle">
            <thead>
                <tr>
                    <th>Categoría</th>
                    <?php foreach ($modalidades as $m): ?>
                    <th class="text-end"><?= e(Catalogos::MODALIDADES_ASISTENCIA[$m]) ?></th>
                    <?php endforeach; ?>
                </tr>
            </thead>
            <tbody>
            <?php foreach (Catalogos::CATEGORIAS_ASISTENTE as $clave => $etiqueta): ?>
                <tr>
                    <td><?= e($etiqueta) ?></td>
                    <?php foreach ($modalidades as $m): ?>
                        <?php $actual = $precios[$m][$clave] ?? null; ?>
                    <td class="text-end" style="max-width: 180px;">
                        <div class="input-group input-group-sm">
                            <span class="input-group-text">$</span>
                            <input class="form-control text-end" type="number" step="0.01" min="0" placeholder="—"
                                   name="precio[<?= $m ?>][<?= $clave ?>]"
                                   aria-label="<?= e($etiqueta . ' ' . Catalogos::MODALIDADES_ASISTENCIA[$m]) ?>"
                                   value="<?= $actual === null ? '' : e((string) (float) $actual) ?>">
                        </div>
                    </td>
                    <?php endforeach; ?>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <button class="btn btn-primary" type="submit"><i class="bi bi-check-lg me-1"></i>Guardar precios</button>
</form>

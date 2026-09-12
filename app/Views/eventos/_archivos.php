<?php
/** Pestaña Archivos del evento. Variables: $evento, $documentos, $maxMb */
use Diana\Core\Catalogos;
use Diana\Core\Csrf;

$idEvento = (int) $evento['id'];
$tamano = function (int $bytes): string {
    return $bytes >= 1048576 ? number_format($bytes / 1048576, 1) . ' MB' : number_format($bytes / 1024) . ' KB';
};
?>
<div class="row g-4">
    <div class="col-12 col-lg-4">
        <h2 class="h6 fw-semibold mb-3">Agregar archivo</h2>
        <form method="post" action="<?= e(url('eventos/subirArchivo/' . $idEvento)) ?>" enctype="multipart/form-data">
            <?= Csrf::campo() ?>
            <div class="mb-3">
                <label class="form-label" for="ea_tipo">Tipo *</label>
                <select class="form-select" id="ea_tipo" name="tipo" required>
                    <option value="">— Seleccionar —</option>
                    <?php foreach (Catalogos::TIPOS_DOCUMENTO_EVENTO as $clave => $etiqueta): ?>
                    <option value="<?= $clave ?>"><?= e($etiqueta) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="mb-3">
                <label class="form-label" for="ea_descripcion">Descripción</label>
                <input class="form-control" id="ea_descripcion" name="descripcion" maxlength="150" placeholder="Opcional">
            </div>
            <div class="mb-3">
                <label class="form-label" for="ea_archivo">Archivo *</label>
                <input class="form-control" id="ea_archivo" name="archivo" type="file" required
                       accept=".pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.jpg,.jpeg,.png,.webp">
                <div class="form-text">PDF, Word, Excel, PowerPoint, JPG, PNG o WEBP · máximo <?= (int) $maxMb ?> MB.</div>
            </div>
            <button class="btn btn-primary w-100" type="submit"><i class="bi bi-cloud-arrow-up me-1"></i>Subir archivo</button>
        </form>
    </div>

    <div class="col-12 col-lg-8">
        <h2 class="h6 fw-semibold mb-3">Archivos del evento <span class="badge text-bg-secondary"><?= count($documentos) ?></span></h2>
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead><tr><th>Tipo</th><th>Archivo</th><th class="d-none d-md-table-cell">Fecha</th><th class="text-end">Acciones</th></tr></thead>
                <tbody>
                <?php if (!$documentos): ?>
                    <tr><td colspan="4" class="text-center text-muted py-4">Aún no hay archivos adjuntos.</td></tr>
                <?php endif; ?>
                <?php foreach ($documentos as $d): ?>
                    <?php $ext = strtolower(pathinfo((string) $d['nombre_original'], PATHINFO_EXTENSION)); ?>
                    <tr>
                        <td>
                            <i class="bi <?= e(icono_archivo($ext)) ?> me-1"></i>
                            <?= e(Catalogos::TIPOS_DOCUMENTO_EVENTO[$d['tipo']] ?? $d['tipo']) ?>
                            <?php if ($d['descripcion']): ?><div class="small text-muted"><?= e($d['descripcion']) ?></div><?php endif; ?>
                        </td>
                        <td>
                            <?= e($d['nombre_original']) ?>
                            <div class="small text-muted"><?= e($tamano((int) $d['tamano'])) ?></div>
                        </td>
                        <td class="d-none d-md-table-cell small text-muted">
                            <?= e(fecha_corta(substr((string) $d['creado_en'], 0, 10))) ?>
                            <?php if ($d['subido_por_nombre']): ?><div><?= e($d['subido_por_nombre']) ?></div><?php endif; ?>
                        </td>
                        <td class="text-end text-nowrap">
                            <a class="btn btn-sm btn-outline-secondary" title="Ver" target="_blank" rel="noopener"
                               href="<?= e(url('eventos/archivo/' . $idEvento . '/' . (int) $d['id'])) ?>"><i class="bi bi-eye"></i></a>
                            <a class="btn btn-sm btn-outline-secondary" title="Descargar"
                               href="<?= e(url('eventos/archivo/' . $idEvento . '/' . (int) $d['id'], ['descargar' => 1])) ?>"><i class="bi bi-download"></i></a>
                            <form class="d-inline" method="post" action="<?= e(url('eventos/eliminarArchivo/' . $idEvento . '/' . (int) $d['id'])) ?>"
                                  onsubmit="return confirm('¿Eliminar este archivo? No se puede deshacer.');">
                                <?= Csrf::campo() ?>
                                <button class="btn btn-sm btn-outline-danger" title="Eliminar"><i class="bi bi-trash"></i></button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

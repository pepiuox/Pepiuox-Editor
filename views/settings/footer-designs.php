<?php
/**
 * Catálogo de diseños del pie de página.
 *
 * Cada fila es un diseño compuesto con el constructor (GrapesJS). Uno solo puede
 * estar activo: es el que pinta el sitio. Sin ninguno activo, el sitio muestra el
 * pie por campos, que no se toca nunca desde aquí.
 *
 * Acciones: componer (abre el constructor), activar, duplicar y borrar. Borrar el
 * activo está prohibido: primero se activa otro o se desactiva.
 *
 * @package PePiuoX\Editor\Views
 * @since   2026-11-01
 */

$activo = null;
foreach ($designs as $d) {
    if ($d['is_active']) {
        $activo = $d;
        break;
    }
}
?>

<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <div>
        <h1 class="h3 mb-1"><i class="bi bi-collection me-2"></i>Diseños del pie</h1>
        <p class="text-secondary mb-0">
            Cada diseño se compone con el constructor y se guarda aquí.
            <?= $activo
                ? 'Ahora mismo el sitio muestra <strong>' . e($activo['name']) . '</strong>.'
                : 'Ahora mismo el sitio muestra el <strong>pie por campos</strong>.' ?>
        </p>
    </div>
    <div class="d-flex gap-2">
        <a class="btn btn-outline-secondary btn-sm" href="<?= url('admin/settings/footer') ?>">
            <i class="bi bi-list-ul"></i> Pie por campos
        </a>
        <a class="btn btn-outline-secondary btn-sm" href="<?= url('admin/settings') ?>">
            <i class="bi bi-arrow-left"></i> Configuración
        </a>
    </div>
</div>

<?php if ($activo !== null): ?>
<div class="alert alert-success d-flex flex-wrap justify-content-between align-items-center gap-3">
    <div>
        <i class="bi bi-check-circle me-1"></i>
        El sitio está usando <strong><?= e($activo['name']) ?></strong>.
    </div>
    <form method="post" action="<?= url('admin/settings/footer/designs/deactivate') ?>" class="mb-0">
        <?= csrf_field() ?>
        <button type="submit" class="btn btn-sm btn-outline-secondary">
            Volver al pie por campos
        </button>
    </form>
</div>
<?php endif; ?>

<div class="card mb-4">
    <div class="card-header">
        <strong>Diseños guardados</strong>
        <span class="text-secondary small ms-2"><?= count($designs) ?> de <?= (int) $max ?></span>
    </div>
    <div class="table-responsive">
        <table class="table table-sm align-middle mb-0">
            <thead>
                <tr>
                    <th scope="col">Nombre</th>
                    <th scope="col">Estado</th>
                    <th scope="col">Contenido</th>
                    <th scope="col">Modificado</th>
                    <th scope="col" class="text-end">Acciones</th>
                </tr>
            </thead>
            <tbody>
            <?php if ($designs === []): ?>
                <tr>
                    <td colspan="5" class="text-center text-secondary py-4">
                        Todavía no hay diseños. Crea el primero abajo y compónlo con el constructor.
                    </td>
                </tr>
            <?php endif; ?>

            <?php foreach ($designs as $d): ?>
                <tr>
                    <td>
                        <strong><?= e($d['name']) ?></strong>
                        <div class="text-secondary small">#<?= (int) $d['id'] ?></div>
                    </td>
                    <td>
                        <?php if ($d['is_active']): ?>
                            <span class="badge text-bg-success">En el sitio</span>
                        <?php else: ?>
                            <span class="badge text-bg-secondary">Guardado</span>
                        <?php endif; ?>
                    </td>
                    <td class="small text-secondary">
                        <?= $d['html_bytes'] > 0
                            ? number_format($d['html_bytes'] / 1024, 1) . ' KB de HTML'
                            : 'Vacío' ?>
                    </td>
                    <td class="small text-secondary"><?= e($d['updated_at']) ?></td>
                    <td class="text-end">
                        <div class="d-inline-flex gap-1 flex-wrap justify-content-end">
                            <a class="btn btn-sm btn-primary"
                               href="<?= url('admin/settings/footer/designs/' . $d['id'] . '/builder') ?>">
                                <i class="bi bi-magic"></i> Componer
                            </a>

                            <?php if (!$d['is_active']): ?>
                                <form method="post" class="d-inline"
                                      action="<?= url('admin/settings/footer/designs/' . $d['id'] . '/activate') ?>">
                                    <?= csrf_field() ?>
                                    <button type="submit" class="btn btn-sm btn-outline-success"
                                            title="Usar este diseño en el sitio">
                                        <i class="bi bi-eye"></i> Usar
                                    </button>
                                </form>
                            <?php endif; ?>

                            <form method="post" class="d-inline"
                                  action="<?= url('admin/settings/footer/designs/' . $d['id'] . '/duplicate') ?>">
                                <?= csrf_field() ?>
                                <button type="submit" class="btn btn-sm btn-outline-secondary"
                                        title="Crear una copia con otro nombre">
                                    <i class="bi bi-files"></i> Duplicar
                                </button>
                            </form>

                            <?php if (!$d['is_active']): ?>
                                <form method="post" class="d-inline"
                                      action="<?= url('admin/settings/footer/designs/' . $d['id'] . '/delete') ?>"
                                      onsubmit="return confirm('¿Borrar el diseño «<?= e($d['name']) ?>»? No se puede deshacer.');">
                                    <?= csrf_field() ?>
                                    <button type="submit" class="btn btn-sm btn-outline-danger"
                                            title="Borrar este diseño">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </form>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="card">
    <div class="card-header"><strong>Crear un diseño</strong></div>
    <div class="card-body">
        <form method="post" action="<?= url('admin/settings/footer/designs') ?>" class="row g-2 align-items-end">
            <?= csrf_field() ?>
            <div class="col-md-8">
                <label class="form-label" for="name">Nombre del diseño</label>
                <input class="form-control" type="text" id="name" name="name" maxlength="120" required
                       placeholder="Pie oscuro con columnas, Pie mínimo de una línea…">
                <div class="form-text">Sirve para reconocerlo en la lista. Debe ser único.</div>
            </div>
            <div class="col-md-4">
                <button type="submit" class="btn btn-primary w-100"
                        <?= count($designs) >= (int) $max ? 'disabled' : '' ?>>
                    <i class="bi bi-plus-lg"></i> Crear y componer
                </button>
            </div>
        </form>
        <?php if (count($designs) >= (int) $max): ?>
            <p class="text-secondary small mb-0 mt-2">
                Has llegado al máximo de <?= (int) $max ?> diseños. Borra alguno para crear otro.
            </p>
        <?php endif; ?>
    </div>
</div>

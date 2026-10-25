<?php
/**
 * Formulario de menú y gestión de sus elementos.
 *
 * Sirve para crear (sin menú) y para editar (con menú y elementos).
 * Rutas: GET /admin/menus/create · GET /admin/menus/{id}/edit
 *
 * @package Views\Menus
 */

$isNew = empty($menu['id']);
$menuId = $isNew ? 0 : (int) $menu['id'];
$isDefault = !$isNew && (int) ($menu['is_default'] ?? 0) === 1;
$pageTitle = $isNew ? 'Nuevo menú' : 'Menú: ' . ($menu['name'] ?? '');

/** Aplana el árbol de ítems conservando el nivel para los desplegables. */
$flatItems = [];
$flatten = static function (array $items, int $depth) use (&$flatten, &$flatItems): void {
    foreach ($items as $item) {
        $item['depth'] = $depth;
        $flatItems[] = $item;
        if (!empty($item['children'])) {
            $flatten($item['children'], $depth + 1);
        }
    }
};
$flatten($items ?? [], 0);
?>
<div class="card mb-3">
    <div class="card-header">
        <h3>
            <i class="bi bi-<?= $isNew ? 'plus-circle' : 'list-nested' ?>"></i>
            <?= $isNew ? 'Nuevo menú' : e((string) $menu['name']) ?>
            <?php if ($isDefault): ?><span class="badge badge-published">automático</span><?php endif; ?>
        </h3>
        <a class="btn btn-ghost btn-sm" href="<?= url('admin/menus') ?>">
            <i class="bi bi-arrow-left"></i> Volver
        </a>
    </div>

    <div class="card-body">
        <form method="post"
              action="<?= $isNew ? url('admin/menus') : url('admin/menus/' . $menuId) ?>"
              class="form-grid">
            <?= \App\Core\CSRF::field() ?>

            <div>
                <label class="form-label" for="menuName">Nombre <span class="text-danger">*</span></label>
                <input type="text" class="form-control" id="menuName" name="name" required maxlength="100"
                       value="<?= e((string) ($menu['name'] ?? '')) ?>" placeholder="Ej: Menú principal">
            </div>

            <div>
                <label class="form-label" for="menuSlug">Identificador (slug)</label>
                <input type="text" class="form-control" id="menuSlug" name="slug" maxlength="100"
                       value="<?= e((string) ($menu['slug'] ?? '')) ?>" placeholder="se genera del nombre">
                <small class="text-muted">Se usa en la API pública: <code>/api/v1/menus/{slug}</code></small>
            </div>

            <div>
                <label class="form-label" for="menuLocation">Ubicación</label>
                <select class="form-select" id="menuLocation" name="location">
                    <?php foreach (($locations ?? ['header']) as $location): ?>
                        <option value="<?= e((string) $location) ?>"
                            <?= (string) ($menu['location'] ?? 'header') === (string) $location ? 'selected' : '' ?>>
                            <?= e(ucfirst((string) $location)) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div>
                <label class="form-label">Opciones</label>
                <label class="form-check">
                    <input type="checkbox" class="form-check-input" name="is_active" value="1"
                        <?= $isNew || (int) ($menu['is_active'] ?? 1) === 1 ? 'checked' : '' ?>
                        <?= $isDefault ? 'disabled' : '' ?>>
                    <span>Activo</span>
                </label>
                <label class="form-check">
                    <input type="checkbox" class="form-check-input" name="is_primary" value="1"
                        <?= (int) ($menu['is_primary'] ?? 0) === 1 ? 'checked' : '' ?>
                        <?= $isDefault ? 'disabled' : '' ?>>
                    <span>Sustituye al menú automático en su ubicación</span>
                </label>
                <?php if ($isDefault): ?>
                    <input type="hidden" name="is_active" value="1">
                    <small class="text-muted">El menú automático siempre está activo y no puede sustituirse a sí mismo.</small>
                <?php endif; ?>
            </div>

            <div class="d-flex gap-2">
                <button class="btn btn-primary" type="submit">
                    <i class="bi bi-check-lg"></i> <?= $isNew ? 'Crear menú' : 'Guardar menú' ?>
                </button>
            </div>
        </form>
    </div>
</div>

<?php if (!$isNew): ?>
    <div class="card mb-3">
        <div class="card-header">
            <h3><i class="bi bi-list-ol"></i> Elementos del menú</h3>
            <?php if ($isDefault): ?>
                <form method="post" action="<?= url('admin/menus/sync') ?>" style="display:inline">
                    <?= \App\Core\CSRF::field() ?>
                    <button class="btn btn-outline btn-sm">
                        <i class="bi bi-arrow-repeat"></i> Sincronizar desde páginas publicadas
                    </button>
                </form>
            <?php endif; ?>
        </div>

        <div class="card-body p-0">
            <table class="table">
                <thead>
                    <tr>
                        <th>Elemento</th>
                        <th>Página / URL</th>
                        <th>Estado</th>
                        <th>Nivel</th>
                        <th class="text-end">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                <?php if ($flatItems === []): ?>
                    <tr>
                        <td colspan="5" class="text-muted" style="padding:1rem">
                            <?= $isDefault
                                ? 'El menú automático está vacío: pulsa «Sincronizar» o publica páginas.'
                                : 'Sin elementos. Añade páginas o enlaces con el formulario de abajo.' ?>
                        </td>
                    </tr>
                <?php endif; ?>

                <?php foreach ($flatItems as $item): ?>
                    <?php
                        $itemId = (int) $item['id'];
                        $depth = (int) ($item['depth'] ?? 0);
                        $pagePublished = $item['page_status'] ?? null;
                        $visible = (int) ($item['is_published'] ?? 0) === 1
                            && ($item['status'] ?? '') === 'published'
                            && ($pagePublished === null || $pagePublished === 'published');
                    ?>
                    <tr>
                        <td style="padding-left:<?= 12 + $depth * 22 ?>px">
                            <?= $depth > 0 ? '<i class="bi bi-arrow-return-right"></i> ' : '' ?><?= e((string) $item['title']) ?>
                        </td>
                        <td>
                            <?php if (!empty($item['page_id'])): ?>
                                <span class="text-muted">página #<?= (int) $item['page_id'] ?></span>
                                <code>/<?= e((string) $item['slug']) ?></code>
                            <?php else: ?>
                                <code><?= e((string) ($item['url'] ?? '—')) ?></code>
                            <?php endif; ?>
                        </td>
                        <td>
                            <span class="badge badge-<?= $visible ? 'published' : 'archived' ?>">
                                <?= $visible ? 'visible' : 'oculto' ?>
                            </span>
                        </td>
                        <td><?= (int) ($item['level'] ?? $depth) ?></td>
                        <td class="text-end">
                            <?php if (!$isDefault): ?>
                                <form method="post" action="<?= url("admin/menus/{$menuId}/items/{$itemId}/move") ?>" style="display:inline">
                                    <?= \App\Core\CSRF::field() ?>
                                    <input type="hidden" name="direction" value="up">
                                    <button class="btn btn-ghost btn-sm" title="Subir"><i class="bi bi-arrow-up"></i></button>
                                </form>
                                <form method="post" action="<?= url("admin/menus/{$menuId}/items/{$itemId}/move") ?>" style="display:inline">
                                    <?= \App\Core\CSRF::field() ?>
                                    <input type="hidden" name="direction" value="down">
                                    <button class="btn btn-ghost btn-sm" title="Bajar"><i class="bi bi-arrow-down"></i></button>
                                </form>
                                <form method="post" action="<?= url("admin/menus/{$menuId}/items/{$itemId}/delete") ?>" style="display:inline"
                                      onsubmit="return confirm('¿Quitar «<?= e((string) $item['title']) ?>» del menú? La página no se borra.')">
                                    <?= \App\Core\CSRF::field() ?>
                                    <button class="btn btn-ghost btn-sm" title="Quitar del menú"><i class="bi bi-x-lg"></i></button>
                                </form>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <?php if (!$isDefault): ?>
        <div class="card">
            <div class="card-header">
                <h3><i class="bi bi-plus-square"></i> Añadir elemento</h3>
            </div>
            <div class="card-body">
                <form method="post" action="<?= url("admin/menus/{$menuId}/items") ?>" class="form-grid">
                    <?= \App\Core\CSRF::field() ?>

                    <div>
                        <label class="form-label" for="itemPage">Página</label>
                        <select class="form-select" id="itemPage" name="page_id">
                            <option value="">— Enlace externo o manual —</option>
                            <?php foreach (($pages ?? []) as $page): ?>
                                <option value="<?= (int) $page['id'] ?>">
                                    <?= e((string) $page['title']) ?> (<?= e((string) $page['slug']) ?>)
                                    <?= ($page['status'] ?? '') === 'published' ? '' : ' · sin publicar' ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <small class="text-muted">Si la página no está publicada, el elemento no se mostrará pero no se borra.</small>
                    </div>

                    <div>
                        <label class="form-label" for="itemTitle">Título en el menú</label>
                        <input type="text" class="form-control" id="itemTitle" name="title" maxlength="255"
                               placeholder="Si lo dejas vacío se usa el de la página">
                    </div>

                    <div>
                        <label class="form-label" for="itemUrl">URL manual</label>
                        <input type="text" class="form-control" id="itemUrl" name="url" maxlength="500"
                               placeholder="/contacto · https://… · #ancla">
                    </div>

                    <div>
                        <label class="form-label" for="itemParent">Depende de</label>
                        <select class="form-select" id="itemParent" name="parent_id">
                            <option value="">— Primer nivel —</option>
                            <?php foreach ($flatItems as $item): ?>
                                <option value="<?= (int) $item['id'] ?>">
                                    <?= str_repeat('— ', (int) ($item['depth'] ?? 0)) ?><?= e((string) $item['title']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <small class="text-muted">La jerarquía del menú es independiente de la de las páginas.</small>
                    </div>

                    <div>
                        <label class="form-label" for="itemOrder">Orden</label>
                        <input type="number" class="form-control" id="itemOrder" name="order" min="0"
                               placeholder="Al final">
                    </div>

                    <div>
                        <label class="form-label" for="itemTarget">Abrir en</label>
                        <select class="form-select" id="itemTarget" name="target">
                            <option value="_self">Misma ventana</option>
                            <option value="_blank">Ventana nueva</option>
                            <option value="_parent">Marco padre</option>
                        </select>
                    </div>

                    <div>
                        <label class="form-label" for="itemIcon">Icono (opcional)</label>
                        <input type="text" class="form-control" id="itemIcon" name="icon" maxlength="100"
                               placeholder="bi bi-house">
                    </div>

                    <div class="d-flex gap-2 align-items-end">
                        <button class="btn btn-primary" type="submit">
                            <i class="bi bi-plus-lg"></i> Añadir al menú
                        </button>
                    </div>
                </form>
            </div>
        </div>
    <?php endif; ?>
<?php endif; ?>

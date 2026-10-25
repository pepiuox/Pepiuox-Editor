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
            <?php else: ?>
                <small class="text-muted d-none d-md-inline">
                    <i class="bi bi-grip-vertical"></i> Arrastra un elemento para reordenarlo;
                    suéltalo en el <strong>centro</strong> de otro para anidarlo, o en el
                    <strong>borde</strong> para colocarlo antes o después.
                </small>
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
                        $parentId = isset($item['parent_id']) && $item['parent_id'] !== null ? (int) $item['parent_id'] : 0;
                        $pagePublished = $item['page_status'] ?? null;
                        $visible = (int) ($item['is_published'] ?? 0) === 1
                            && ($item['status'] ?? '') === 'published'
                            && ($pagePublished === null || $pagePublished === 'published');
                    ?>
                    <tr class="menu-item-row<?= $isDefault ? '' : ' menu-item-draggable' ?>"
                        data-item-id="<?= $itemId ?>"
                        data-parent-id="<?= $parentId ?>"
                        data-depth="<?= $depth ?>"
                        <?= $isDefault ? '' : 'draggable="true"' ?>>
                        <td class="menu-item-label" style="padding-left:<?= 12 + $depth * 22 ?>px">
                            <?php if (!$isDefault): ?>
                                <i class="bi bi-grip-vertical menu-item-grip" title="Arrastra para reordenar o anidar"></i>
                            <?php endif; ?>
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
                        <td class="menu-item-level"><?= (int) ($item['level'] ?? $depth) ?></td>
                        <td class="text-end">
                            <?php if (!$isDefault): ?>
                                <form method="post" action="<?= url("admin/menus/{$menuId}/items/{$itemId}/move") ?>" style="display:inline">
                                    <?= \App\Core\CSRF::field() ?>
                                    <input type="hidden" name="direction" value="up">
                                    <button class="btn btn-ghost btn-sm" title="Subir (alternativa al arrastre)">
                                        <i class="bi bi-arrow-up"></i>
                                    </button>
                                </form>
                                <form method="post" action="<?= url("admin/menus/{$menuId}/items/{$itemId}/move") ?>" style="display:inline">
                                    <?= \App\Core\CSRF::field() ?>
                                    <input type="hidden" name="direction" value="down">
                                    <button class="btn btn-ghost btn-sm" title="Bajar (alternativa al arrastre)">
                                        <i class="bi bi-arrow-down"></i>
                                    </button>
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

<?php if (!$isNew && !$isDefault): ?>
<style>
    /* Arrastrar y soltar en la tabla de elementos */
    .menu-item-draggable { cursor: grab; }
    .menu-item-draggable:active { cursor: grabbing; }
    .menu-item-grip { color: var(--text-muted, #8b949e); margin-right: .35rem; opacity: .7; }
    .menu-item-row.is-dragging { opacity: .45; }
    .menu-item-row.drop-before > td { box-shadow: inset 0 3px 0 var(--accent, #00d4aa); }
    .menu-item-row.drop-after > td { box-shadow: inset 0 -3px 0 var(--accent, #00d4aa); }
    .menu-item-row.drop-inside > td { background: rgba(0, 212, 170, .12); }
    .menu-reorder-status { font-size: .8rem; color: var(--text-muted, #8b949e); margin-left: .5rem; }
</style>

<script>
(function () {
    'use strict';

    const tbody = document.querySelector('.menu-item-row')?.closest('tbody');
    if (!tbody) return;

    const reorderUrl = <?= json_encode(url("admin/menus/{$menuId}/items/reorder"), JSON_UNESCAPED_SLASHES) ?>;
    const csrfToken = <?= json_encode(\App\Core\CSRF::token(), JSON_UNESCAPED_SLASHES) ?>;
    const status = document.createElement('span');
    status.className = 'menu-reorder-status';

    let dragRow = null;

    const rows = () => Array.from(tbody.querySelectorAll('tr.menu-item-row'));
    const depthOf = (row) => parseInt(row.dataset.depth || '0', 10);
    const idOf = (row) => row.dataset.itemId;

    /** Filas del elemento arrastrado más sus descendientes (bloque contiguo). */
    function subtree(row) {
        const all = rows();
        const start = all.indexOf(row);
        if (start === -1) return [row];
        const block = [row];
        for (let i = start + 1; i < all.length; i++) {
            if (depthOf(all[i]) > depthOf(row)) block.push(all[i]);
            else break;
        }
        return block;
    }

    /** Ajusta la profundidad visual de un bloque movido y la guarda en data-depth. */
    function applyDepth(block, baseDepth) {
        const offset = baseDepth - depthOf(block[0]);
        block.forEach((row) => {
            const depth = Math.max(0, depthOf(row) + offset);
            row.dataset.depth = String(depth);
            const label = row.querySelector('.menu-item-label');
            if (label) {
                label.style.paddingLeft = (12 + depth * 22) + 'px';
            }
            const level = row.querySelector('.menu-item-level');
            if (level) {
                level.textContent = String(depth);
            }
            const arrow = label ? label.querySelector('.bi-arrow-return-right') : null;
            if (depth > 0 && !arrow && label) {
                label.insertAdjacentHTML('afterbegin', '<i class="bi bi-arrow-return-right"></i> ');
            }
        });
    }

    /** Zona del destino según la posición vertical del puntero. */
    function dropZone(row, event) {
        const rect = row.getBoundingClientRect();
        const offset = event.clientY - rect.top;
        if (offset < rect.height * 0.3) return 'before';
        if (offset > rect.height * 0.7) return 'after';
        return 'inside';
    }

    function clearMarks() {
        rows().forEach((row) => row.classList.remove('drop-before', 'drop-after', 'drop-inside'));
    }

    function serialize() {
        return rows().map((row, index) => ({
            id: parseInt(idOf(row), 10),
            order: index,
            parent_id: parseInt(row.dataset.parentId || '0', 10) || null
        }));
    }

    function persist() {
        const body = new FormData();
        body.set('_csrf_token', csrfToken);
        body.set('items', JSON.stringify(serialize()));
        status.textContent = 'Guardando…';
        status.style.color = '';

        fetch(reorderUrl, {
            method: 'POST',
            body: body,
            credentials: 'same-origin',
            headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
        })
        .then((response) => {
            if (!response.ok) throw new Error('El servidor respondió ' + response.status);
            return response.json();
        })
        .then((result) => {
            if (!result.success) throw new Error(result.error || 'No se pudo guardar el orden');
            status.textContent = '✓ Orden guardado';
            // Recarga para ver el estado persistido (padres y niveles reales).
            window.location.reload();
        })
        .catch((error) => {
            status.textContent = '✗ ' + error.message;
            status.style.color = '#ef4444';
        });
    }

    tbody.addEventListener('dragstart', (event) => {
        const row = event.target.closest('tr.menu-item-row');
        if (!row || !row.draggable) return;
        dragRow = row;
        row.classList.add('is-dragging');
        event.dataTransfer.effectAllowed = 'move';
        event.dataTransfer.setData('text/plain', idOf(row));
    });

    tbody.addEventListener('dragend', () => {
        if (dragRow) dragRow.classList.remove('is-dragging');
        dragRow = null;
        clearMarks();
    });

    tbody.addEventListener('dragover', (event) => {
        if (!dragRow) return;
        const row = event.target.closest('tr.menu-item-row');
        if (!row || row === dragRow) return;

        // No se puede soltar dentro de un descendiente del propio elemento.
        if (subtree(dragRow).includes(row)) return;

        event.preventDefault();
        event.dataTransfer.dropEffect = 'move';
        clearMarks();
        row.classList.add('drop-' + dropZone(row, event));
    });

    tbody.addEventListener('dragleave', (event) => {
        const row = event.target.closest('tr.menu-item-row');
        if (row) row.classList.remove('drop-before', 'drop-after', 'drop-inside');
    });

    tbody.addEventListener('drop', (event) => {
        if (!dragRow) return;
        const target = event.target.closest('tr.menu-item-row');
        if (!target || target === dragRow) return;

        const block = subtree(dragRow);
        if (block.includes(target)) return;

        event.preventDefault();
        const zone = dropZone(target, event);

        // Se recoloca el bloque completo (el elemento y sus hijos) en el DOM.
        const parentIdOfTarget = parseInt(target.dataset.parentId || '0', 10);
        const targetBlock = subtree(target);
        let anchor;
        let newParentId;
        let baseDepth;

        if (zone === 'inside') {
            anchor = targetBlock[targetBlock.length - 1].nextElementSibling;
            newParentId = parseInt(idOf(target), 10);
            baseDepth = depthOf(target) + 1;
        } else if (zone === 'before') {
            anchor = target;
            newParentId = parentIdOfTarget;
            baseDepth = depthOf(target);
        } else {
            anchor = targetBlock[targetBlock.length - 1].nextElementSibling;
            newParentId = parentIdOfTarget;
            baseDepth = depthOf(target);
        }

        block.forEach((row) => tbody.insertBefore(row, anchor));
        // Solo el elemento arrastrado cambia de padre; sus hijos lo mantienen.
        block[0].dataset.parentId = String(newParentId);
        applyDepth(block, baseDepth);

        clearMarks();
        dragRow.classList.remove('is-dragging');
        dragRow = null;

        persist();
    });

    // Indicador de estado junto al título de la tarjeta.
    const header = tbody.closest('.card')?.querySelector('.card-header h3');
    if (header) header.appendChild(status);
})();
</script>
<?php endif; ?>

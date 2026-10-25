<?php
/**
 * Listado de menús.
 *
 * Ruta: GET /admin/menus
 * Controlador: MenuController::index()
 *
 * @package Views\Menus
 */

$pageTitle = 'Menús';
$totalItems = (int) ($stats['items'] ?? 0);
?>
<div class="card mb-3">
    <div class="card-header">
        <h3><i class="bi bi-menu-button-wide"></i> Menús del sitio</h3>
        <div class="d-flex gap-2 flex-wrap">
            <form method="post" action="<?= url('admin/menus/sync') ?>" style="display:inline">
                <?= \App\Core\CSRF::field() ?>
                <button class="btn btn-outline btn-sm" title="Regenerar el menú automático desde las páginas publicadas">
                    <i class="bi bi-arrow-repeat"></i> Sincronizar automático
                </button>
            </form>
            <form method="post" action="<?= url('admin/menus/repair') ?>" style="display:inline">
                <?= \App\Core\CSRF::field() ?>
                <button class="btn btn-ghost btn-sm" title="Deja un solo predeterminado y un solo principal por ubicación">
                    <i class="bi bi-tools"></i> Reparar
                </button>
            </form>
            <?php if (\App\Core\Auth::can('menus.create')): ?>
                <a class="btn btn-primary btn-sm" href="<?= url('admin/menus/create') ?>">
                    <i class="bi bi-plus"></i> Nuevo menú
                </a>
            <?php endif; ?>
        </div>
    </div>

    <div class="card-body">
        <p class="text-muted" style="margin:0 0 1rem">
            <strong>Automático</strong>: se genera solo con las páginas publicadas (no se edita a mano).
            <strong>Principal</strong>: menú personalizado que sustituye al automático en su ubicación.
            Si una página tiene un menú asignado, ese tiene prioridad sobre los dos anteriores.
        </p>

        <div class="d-flex gap-3 flex-wrap" style="font-size:.9rem">
            <span><i class="bi bi-collection"></i> <?= (int) ($stats['menus'] ?? 0) ?> menú(s)</span>
            <span><i class="bi bi-list-ul"></i> <?= $totalItems ?> elemento(s)</span>
            <span><i class="bi bi-eye"></i> <?= (int) ($stats['published'] ?? 0) ?> visible(s)</span>
            <span><i class="bi bi-eye-slash"></i> <?= (int) ($stats['hidden'] ?? 0) ?> oculto(s)</span>
        </div>
    </div>
</div>

<?php if (empty($menus)): ?>
    <div class="card">
        <div class="card-body">
            <p style="margin:0">Todavía no hay menús. Crea uno o pulsa «Sincronizar automático» para generar el menú del sitio.</p>
        </div>
    </div>
<?php endif; ?>

<?php foreach ($menus as $menu): ?>
    <?php
        $menuId = (int) $menu['id'];
        $isDefault = (int) $menu['is_default'] === 1;
        $isPrimary = (int) $menu['is_primary'] === 1;
        $isActive = (int) $menu['is_active'] === 1;
        $tree = $menusTree[$menuId] ?? [];
    ?>
    <div class="card mb-3">
        <div class="card-header">
            <h3>
                <i class="bi bi-<?= $isDefault ? 'magic' : 'list-nested' ?>"></i>
                <?= e($menu['name']) ?>
                <?php if ($isDefault): ?><span class="badge badge-published">automático</span><?php endif; ?>
                <?php if ($isPrimary): ?><span class="badge badge-draft">principal</span><?php endif; ?>
                <?php if (!$isActive): ?><span class="badge badge-archived">inactivo</span><?php endif; ?>
            </h3>
            <div class="d-flex gap-2 flex-wrap">
                <a class="btn btn-ghost btn-sm" href="<?= url("admin/menus/{$menuId}/edit") ?>">
                    <i class="bi bi-pencil"></i> Editar
                </a>
                <?php if (\App\Core\Auth::can('menus.create')): ?>
                    <form method="post" action="<?= url("admin/menus/{$menuId}/duplicate") ?>" style="display:inline">
                        <?= \App\Core\CSRF::field() ?>
                        <button class="btn btn-ghost btn-sm" title="Duplicar este menú con sus elementos">
                            <i class="bi bi-copy"></i>
                        </button>
                    </form>
                <?php endif; ?>
                <?php if (!$isDefault): ?>
                    <form method="post" action="<?= url("admin/menus/{$menuId}/toggle") ?>" style="display:inline">
                        <?= \App\Core\CSRF::field() ?>
                        <button class="btn btn-ghost btn-sm" title="<?= $isActive ? 'Desactivar' : 'Activar' ?>">
                            <i class="bi bi-<?= $isActive ? 'pause-circle' : 'play-circle' ?>"></i>
                        </button>
                    </form>
                    <form method="post" action="<?= url("admin/menus/{$menuId}/default") ?>" style="display:inline"
                          onsubmit="return confirm('¿Convertir este menú en el automático de su ubicación? El actual dejará de serlo.')">
                        <?= \App\Core\CSRF::field() ?>
                        <button class="btn btn-ghost btn-sm" title="Marcar como menú automático">
                            <i class="bi bi-magic"></i>
                        </button>
                    </form>
                <?php endif; ?>
                <?php if (\App\Core\Auth::can('menus.delete') && !$isDefault): ?>
                    <form method="post" action="<?= url("admin/menus/{$menuId}/delete") ?>" style="display:inline"
                          onsubmit="return confirm('¿Eliminar este menú? Sus páginas no se borran.')">
                        <?= \App\Core\CSRF::field() ?>
                        <button class="btn btn-ghost btn-sm" title="Eliminar menú">
                            <i class="bi bi-trash"></i>
                        </button>
                    </form>
                <?php endif; ?>
            </div>
        </div>

        <div class="card-body p-0">
            <table class="table">
                <thead>
                    <tr>
                        <th>Elemento</th>
                        <th>Destino</th>
                        <th>Estado</th>
                        <th class="text-end">Hijos</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($tree)): ?>
                        <tr>
                            <td colspan="4" class="text-muted" style="padding:1rem">
                                <?= $isDefault
                                    ? 'Sin elementos todavía. Pulsa «Sincronizar automático» para generarlo desde las páginas publicadas.'
                                    : 'Este menú no tiene elementos. Añádelos desde «Editar».' ?>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php
                        $renderItems = static function (array $items, int $depth) use (&$renderItems): void {
                            foreach ($items as $item) {
                                $title = (string) ($item['title'] ?? '');
                                $slug = (string) ($item['slug'] ?? '');
                                $url = (string) ($item['url'] ?? '');
                                $pageId = $item['page_id'] !== null ? (int) $item['page_id'] : null;
                                $pagePublished = $item['page_status'] ?? null;
                                $visible = (int) ($item['is_published'] ?? 0) === 1
                                    && ($item['status'] ?? '') === 'published'
                                    && ($pagePublished === null || $pagePublished === 'published');

                                echo '<tr>';
                                echo '<td style="padding-left:' . (12 + $depth * 22) . 'px">'
                                    . ($depth > 0 ? '<i class="bi bi-arrow-return-right"></i> ' : '')
                                    . e($title) . '</td>';
                                echo '<td>' . ($pageId !== null
                                    ? '<code>/' . e($slug) . '</code> <span class="text-muted">(página #' . $pageId . ')</span>'
                                    : ($url !== '' ? e($url) : '<span class="text-muted">—</span>')) . '</td>';
                                echo '<td>' . ($visible
                                    ? '<span class="badge badge-published">visible</span>'
                                    : '<span class="badge badge-archived">oculto</span>') . '</td>';
                                echo '<td class="text-end">' . (isset($item['children']) ? count($item['children']) : 0) . '</td>';
                                echo '</tr>';

                                if (!empty($item['children'])) {
                                    $renderItems($item['children'], $depth + 1);
                                }
                            }
                        };
                        $renderItems($tree, 0);
                        ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
<?php endforeach; ?>

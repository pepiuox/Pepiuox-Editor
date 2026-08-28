<?php
/**
 * Vista: Gestión de Menús
 * Versión coherente con el sistema unificado
 */
$pageTitle = 'Menús';

// ═══════════════════════════════════════════════════════════════════
// HELPERS CSRF (protegidos contra redeclaración)
// ═══════════════════════════════════════════════════════════════════
if (!function_exists('csrf_field_html')) {
    function csrf_field_html(): string {
        if (class_exists('\App\Core\CSRF')) {
            return \App\Core\CSRF::field();
        }
        $token = $_SESSION['_csrf_token'] ?? ($_SESSION['csrf_token'] ?? '');
        return '<input type="hidden" name="_csrf_token" value="' . htmlspecialchars((string)$token) . '">';
    }
}

if (!function_exists('get_csrf_token_js')) {
    function get_csrf_token_js(): string {
        if (class_exists('\App\Core\CSRF') && method_exists('\App\Core\CSRF', 'token')) {
            return (string)\App\Core\CSRF::token();
        }
        return htmlspecialchars((string)($_SESSION['_csrf_token'] ?? ($_SESSION['csrf_token'] ?? '')));
    }
}

// ═══════════════════════════════════════════════════════════════════
// VARIABLES CON FALLBACK SEGURO
// ═══════════════════════════════════════════════════════════════════
$items    = $items ?? [];
$menu     = $menu ?? [];
$groups   = $groups ?? [];
$pages    = $pages ?? [];
$group    = $group ?? 'main';
$stats    = $stats ?? ['total' => 0, 'published' => 0, 'draft' => 0, 'groups' => 0];

// ✅ CORRECCIÓN CRÍTICA: Priorizar el árbol jerárquico ($menu) sobre el plano ($items)
// $menu viene de getAdminMenu() → estructura con 'children' para recursión
// $items viene de getFlatMenu() → array plano sin jerarquía
$menuData = !empty($menu) ? $menu : $items;
?>

<div class="container-fluid py-3">
    <div class="row">
        <div class="col-12">

            <!-- HEADER -->
            <div class="card shadow-sm mb-3">
                <div class="card-header bg-white d-flex flex-wrap justify-content-between align-items-center gap-2">
                    <div class="d-flex align-items-center gap-2">
                        <i class="bi bi-list-nested fs-4 text-primary"></i>
                        <h4 class="mb-0 fw-bold">Gestión de Menús</h4>
                        <?php if (!empty($stats['total'])): ?>
                            <span class="badge bg-primary-subtle text-primary">
                                <?= (int)$stats['total'] ?> elementos
                            </span>
                        <?php endif; ?>
                    </div>
                    <div class="d-flex gap-2 flex-wrap">
                        <?php if (!empty($groups)): ?>
                            <div class="btn-group btn-group-sm" role="group">
                                <?php foreach ($groups as $g): ?>
                                    <?php
                                    // ✅ Soporta tanto array ['slug'=>'x','name'=>'y'] como string
                                    $slug = is_array($g) ? ($g['slug'] ?? '') : (string)$g;
                                    $name = is_array($g) ? ($g['name'] ?? $slug) : (string)$g;
                                    ?>
                                    <a class="btn <?= ($group === $slug) ? 'btn-primary' : 'btn-outline-primary' ?>"
                                       href="<?= url('admin/menus?group=' . urlencode($slug)) ?>">
                                        <?= e($name) ?>
                                    </a>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                        <button class="btn btn-warning btn-sm" onclick="regenerateMenu()" title="Regenerar caché">
                            <i class="bi bi-arrow-repeat"></i> Regenerar
                        </button>
                        <a href="<?= url('admin/menus/create') ?>" class="btn btn-success btn-sm">
                            <i class="bi bi-plus-circle"></i> Nueva Página
                        </a>
                    </div>
                </div>
            </div>

            <!-- FORMULARIO AÑADIR ITEM -->
            <div class="card shadow-sm mb-3">
                <div class="card-header bg-light">
                    <h6 class="mb-0"><i class="bi bi-plus-square text-primary"></i> Añadir nuevo elemento</h6>
                </div>
                <div class="card-body">
                    <form method="post" action="<?= url('admin/menus') ?>">
                        <?= csrf_field_html() ?>
                        <input type="hidden" name="menu_group" value="<?= e((string)$group) ?>">
                        <div class="row g-3">
                            <div class="col-md-3">
                                <label class="form-label fw-semibold">Título <span class="text-danger">*</span></label>
                                <input type="text" name="title" class="form-control" required placeholder="Ej: Inicio">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label fw-semibold">URL</label>
                                <input type="text" name="url" class="form-control" placeholder="/pagina o https://...">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label fw-semibold">Página enlazada</label>
                                <select name="page_id" class="form-select">
                                    <option value="">— Ninguna —</option>
                                    <?php foreach ((array)$pages as $p): ?>
                                        <?php
                                        // ✅ Verificar que $p sea un array con keys esperadas
                                        if (!is_array($p)) continue;
                                        $pageId    = (int)($p['id'] ?? 0);
                                        $pageTitle = (string)($p['title'] ?? 'Sin título');
                                        if ($pageId <= 0) continue;
                                        ?>
                                        <option value="<?= $pageId ?>">
                                            <?= e($pageTitle) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label fw-semibold">Target</label>
                                <select name="target" class="form-select">
                                    <option value="_self">Misma ventana</option>
                                    <option value="_blank">Nueva ventana</option>
                                </select>
                            </div>
                            <div class="col-md-1 d-flex align-items-end">
                                <button type="submit" class="btn btn-primary w-100">
                                    <i class="bi bi-plus-lg"></i>
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <!-- ÁRBOL DE MENÚ -->
            <div class="card shadow-sm">
                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                    <h6 class="mb-0"><i class="bi bi-diagram-3 text-primary"></i> Estructura del Menú</h6>
                    <small class="text-muted"><i class="bi bi-info-circle"></i> Arrastra para reordenar</small>
                </div>
                <div class="card-body">
                    <?php if (!empty($menuData)): ?>
                        <div class="alert alert-info py-2 mb-3">
                            <i class="bi bi-lightbulb"></i>
                            <small>Usa el <i class="bi bi-grip-vertical"></i> para arrastrar y reorganizar.</small>
                        </div>
                        <div id="menu-tree">
                            <ul class="list-group sortable-menu" data-level="0">
                                <?= _renderMenuTree($menuData) ?>
                            </ul>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-5">
                            <i class="bi bi-menu-button-wide display-1 text-muted"></i>
                            <p class="text-muted mt-3 mb-4">No hay elementos en el menú</p>
                            <a href="<?= url('admin/menus/create') ?>" class="btn btn-primary">
                                <i class="bi bi-plus-circle"></i> Crear primer elemento
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

        </div>
    </div>
</div>

<?php
/**
 * ✅ Renderiza recursivamente los elementos del menú
 * Protegida contra redeclaración con prefijo _
 */
if (!function_exists('_renderMenuTree')) {
    function _renderMenuTree(array $items, int $level = 0): string
    {
        if (empty($items)) return '';
        $html = '';
        foreach ($items as $it) {
            // ✅ Validar que sea un array
            if (!is_array($it)) continue;

            $id          = (int)($it['id'] ?? 0);
            $parentId    = $it['parent_id'] ?? null;
            $title       = htmlspecialchars((string)($it['title'] ?? 'Sin título'), ENT_QUOTES, 'UTF-8');
            $url         = (string)($it['url'] ?? ('/' . ($it['slug'] ?? '')));
            $icon        = htmlspecialchars((string)($it['icon'] ?? 'bi bi-link-45deg'), ENT_QUOTES, 'UTF-8');
            $published   = !empty($it['is_published']);
            $children    = (array)($it['children'] ?? []);
            $hasChildren = !empty($children);

            // ✅ Validar ID antes de renderizar
            if ($id <= 0) continue;

            $html .= '<li class="list-group-item menu-item" data-id="' . $id . '" data-parent="' . ($parentId ?? '') . '" data-level="' . $level . '">';
            $html .= '<div class="d-flex justify-content-between align-items-center flex-wrap gap-2">';
            $html .= '<div class="d-flex align-items-center gap-2 flex-grow-1">';
            $html .= '<i class="bi bi-grip-vertical drag-handle text-muted" style="cursor:grab"></i>';
            $html .= '<i class="' . $icon . ' text-primary fs-5"></i>';
            $html .= '<strong class="text-dark">' . $title . '</strong>';
            $html .= '<small class="text-muted text-truncate" style="max-width:250px">' . htmlspecialchars($url, ENT_QUOTES, 'UTF-8') . '</small>';
            $html .= '<span class="badge ' . ($published ? 'bg-success' : 'bg-secondary') . '">';
            $html .= $published ? 'Publicado' : 'Borrador';
            $html .= '</span>';
            if ($level > 0) {
                $html .= '<span class="badge bg-info">Nivel ' . $level . '</span>';
            }
            if ($hasChildren) {
                $html .= '<span class="badge bg-warning text-dark">' . count($children) . ' submenús</span>';
            }
            $html .= '</div>';
            $html .= '<div class="btn-group btn-group-sm">';
            $html .= '<a href="' . url('admin/menus/' . $id . '/edit') . '" class="btn btn-outline-primary" title="Editar"><i class="bi bi-pencil"></i></a>';
            $html .= '<button type="button" onclick="toggleStatus(' . $id . ')" class="btn btn-outline-' . ($published ? 'secondary' : 'success') . '" title="Cambiar estado"><i class="bi bi-' . ($published ? 'eye-slash' : 'eye') . '"></i></button>';
            $html .= '<button type="button" onclick="deleteItem(' . $id . ')" class="btn btn-outline-danger" title="Eliminar"><i class="bi bi-trash"></i></button>';
            $html .= '</div>';
            $html .= '</div>';
            if ($hasChildren) {
                $html .= '<ul class="list-group sortable-menu mt-2 ms-4 border-start border-2 border-primary-subtle">';
                $html .= _renderMenuTree($children, $level + 1);
                $html .= '</ul>';
            }
            $html .= '</li>';
        }
        return $html;
    }
}
?>

<style>
.menu-item { transition: all 0.2s ease; border-left: 3px solid transparent !important; }
.menu-item:hover { border-left-color: var(--bs-primary, #0d6efd) !important; background-color: rgba(13, 110, 253, 0.03); }
.drag-handle { cursor: grab; font-size: 1.1rem; }
.drag-handle:active { cursor: grabbing; }
.sortable-ghost { opacity: 0.4; background: #e7f1ff !important; }
.sortable-chosen { box-shadow: 0 4px 12px rgba(0,0,0,0.15); }
</style>

<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
<script>
const CSRF_TOKEN = '<?= get_csrf_token_js() ?>';
const URL_BASE   = '<?= url("admin/menus") ?>';

document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('.sortable-menu').forEach(container => {
        new Sortable(container, {
            group: 'menu-tree',
            handle: '.drag-handle',
            animation: 150,
            fallbackOnBody: true,
            swapThreshold: 0.65,
            onEnd: saveOrder
        });
    });
});

function saveOrder() {
    const orderData = [];
    let order = 0;
    document.querySelectorAll('.menu-item').forEach(item => {
        orderData.push({
            id: item.dataset.id,
            parent_id: item.dataset.parent || null,
            level: item.dataset.level || 0,
            order: order++
        });
    });

    fetch(URL_BASE + '/reorder', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': CSRF_TOKEN,
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: JSON.stringify({ items: orderData })
    })
    .then(r => r.json())
    .then(data => {
        showNotification(
            data.success ? 'Orden actualizado' : ('Error: ' + (data.error || 'desconocido')),
            data.success ? 'success' : 'danger'
        );
    })
    .catch(() => showNotification('Error de red al guardar', 'danger'));
}

function toggleStatus(id) {
    if (!confirm('¿Cambiar el estado de publicación?')) return;
    fetch(URL_BASE + '/' + id + '/toggle', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': CSRF_TOKEN,
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) location.reload();
        else showNotification('Error: ' + (data.error || ''), 'danger');
    });
}

function deleteItem(id) {
    if (!confirm('¿Eliminar este elemento? Esta acción no se puede deshacer.')) return;
    fetch(URL_BASE + '/' + id + '/delete', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': CSRF_TOKEN,
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) location.reload();
        else showNotification('Error: ' + (data.error || ''), 'danger');
    });
}

function regenerateMenu() {
    if (!confirm('¿Regenerar la caché del menú?')) return;
    fetch(URL_BASE + '/regenerate', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': CSRF_TOKEN,
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(r => r.json())
    .then(data => {
        showNotification(
            data.success ? 'Caché regenerada' : 'Error al regenerar',
            data.success ? 'success' : 'danger'
        );
    });
}

function showNotification(message, type = 'info') {
    const icons = {
        success: 'check-circle-fill',
        danger: 'exclamation-triangle-fill',
        info: 'info-circle-fill',
        warning: 'exclamation-circle-fill'
    };
    const alert = document.createElement('div');
    alert.className = `alert alert-${type} alert-dismissible fade show position-fixed shadow`;
    alert.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 300px;';
    alert.innerHTML = `
        <i class="bi bi-${icons[type] || 'info-circle-fill'} me-2"></i>
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    document.body.appendChild(alert);
    setTimeout(() => alert.remove(), 4000);
}
</script>
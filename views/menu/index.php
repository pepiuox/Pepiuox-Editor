<?php $pageTitle = 'Menús'; ?>
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4 class="mb-0">Gestión de Menú</h4>
                    <div>
                        <button class="btn btn-success btn-sm" onclick="regenerateMenu()">
                            <i class="bi bi-arrow-repeat"></i> Regenerar
                        </button>
                        <a href="<?= url('admin/menus/create') ?>" class="btn btn-primary btn-sm">
                            <i class="bi bi-plus-circle"></i> Nueva Página
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <div class="alert alert-info">
                        <i class="bi bi-info-circle"></i>
                        Arrastra y suelta los elementos para reordenar el menú.
                    </div>

                    <div id="menu-tree">
                        <?php if (!empty($menu)): ?>
                            <ul class="list-group sortable-menu">
                                <?= renderMenuItems($menu) ?>
                            </ul>
                        <?php else: ?>
                            <div class="text-center py-5">
                                <i class="bi bi-menu-up display-1 text-muted"></i>
                                <p class="text-muted mt-3">No hay elementos en el menú</p>
                                <a href="<?= url('admin/menus/create') ?>" class="btn btn-primary">
                                    <i class="bi bi-plus-circle"></i> Crear primera página
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
function renderMenuItems($items, $level = 0)
{
    if (empty($items)) {
        return '';
    }

    $html = '';
    foreach ($items as $item) {
        $hasChildren = !empty($item['children']);
        $indent = str_repeat('&nbsp;&nbsp;&nbsp;&nbsp;', $level);
        $icon = $item['icon'] ?? 'bi bi-file';
        
        $html .= '<li class="list-group-item" data-id="' . $item['id'] . '" data-parent="' . ($item['parent_id'] ?? '') . '">';
        $html .= '<div class="d-flex justify-content-between align-items-center">';
        $html .= '<div class="d-flex align-items-center">';
        $html .= '<i class="menu-handle bi bi-grip-vertical me-2 text-muted" style="cursor: grab;"></i>';
        $html .= '<i class="' . $icon . ' me-2"></i>';
        $html .= '<span>' . $indent . htmlspecialchars($item['title']) . '</span>';
        $html .= '<span class="badge bg-' . ($item['is_published'] ? 'success' : 'secondary') . ' ms-2">';
        $html .= $item['is_published'] ? 'Publicado' : 'Borrador';
        $html .= '</span>';
        $html .= '<span class="badge bg-info ms-2">Nivel ' . $item['level'] . '</span>';
        if ($hasChildren) {
            $html .= '<span class="badge bg-warning ms-2">' . count($item['children']) . ' submenús</span>';
        }
        $html .= '</div>';
        $html .= '<div class="btn-group">';
        $html .= '<a href="' . url('admin/menus/' . $item['id'] . '/edit') . '" class="btn btn-sm btn-outline-primary">';
        $html .= '<i class="bi bi-pencil"></i>';
        $html .= '</a>';
        $html .= '<button onclick="toggleStatus(' . $item['id'] . ')" class="btn btn-sm btn-outline-' . ($item['is_published'] ? 'secondary' : 'success') . '">';
        $html .= '<i class="bi bi-' . ($item['is_published'] ? 'eye-slash' : 'eye') . '"></i>';
        $html .= '</button>';
        $html .= '<button onclick="deletePage(' . $item['id'] . ')" class="btn btn-sm btn-outline-danger">';
        $html .= '<i class="bi bi-trash"></i>';
        $html .= '</button>';
        $html .= '</div>';
        $html .= '</div>';

        if ($hasChildren) {
            $html .= '<ul class="list-group sortable-menu mt-2">';
            $html .= renderMenuItems($item['children'], $level + 1);
            $html .= '</ul>';
        }

        $html .= '</li>';
    }

    return $html;
}
?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Inicializar Sortable.js
    const containers = document.querySelectorAll('.sortable-menu');
    containers.forEach(container => {
        new Sortable(container, {
            group: 'menu',
            handle: '.menu-handle',
            animation: 150,
            onEnd: function() {
                saveOrder();
            }
        });
    });
});

function saveOrder() {
    const items = [];
    const lists = document.querySelectorAll('.sortable-menu');
    
    lists.forEach(list => {
        const listItems = list.querySelectorAll('li.list-group-item');
        listItems.forEach(item => {
            items.push({
                id: item.dataset.id,
                parent_id: item.dataset.parent || null
            });
        });
    });

    // Actualizar órdenes
    const orderData = [];
    let order = 0;
    
    items.forEach(item => {
        orderData.push({
            id: item.id,
            order: order++,
            parent_id: item.parent_id
        });
    });

    fetch('<?= url('admin/menus/reorder') ?>', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '<?= csrf_token() ?>'
        },
        body: JSON.stringify({ items: orderData })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showNotification('Orden actualizado correctamente', 'success');
        } else {
            showNotification('Error al actualizar el orden: ' + data.error, 'danger');
        }
    })
    .catch(error => {
        showNotification('Error al guardar el orden', 'danger');
    });
}

function toggleStatus(id) {
    if (!confirm('¿Cambiar el estado de esta página?')) return;

    fetch('<?= url('admin/menus') ?>/' + id + '/toggle', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '<?= csrf_token() ?>'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            showNotification('Error: ' + data.error, 'danger');
        }
    });
}

function deletePage(id) {
    if (!confirm('¿Eliminar esta página del menú?')) return;

    fetch('<?= url('admin/menus') ?>/' + id + '/delete', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '<?= csrf_token() ?>'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            showNotification('Error: ' + data.error, 'danger');
        }
    });
}

function regenerateMenu() {
    if (!confirm('¿Regenerar el menú?')) return;

    fetch('<?= url('admin/menus/regenerate') ?>', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '<?= csrf_token() ?>'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showNotification('Menú regenerado correctamente', 'success');
        } else {
            showNotification('Error al regenerar el menú', 'danger');
        }
    });
}

function showNotification(message, type = 'info') {
    const alert = document.createElement('div');
    alert.className = 'alert alert-' + type + ' alert-dismissible fade show position-fixed top-0 end-0 m-3';
    alert.style.zIndex = '9999';
    alert.innerHTML = `
        <i class="bi bi-${type === 'success' ? 'check-circle' : type === 'danger' ? 'exclamation-circle' : 'info-circle'}"></i>
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    document.body.appendChild(alert);
    
    setTimeout(() => {
        alert.remove();
    }, 5000);
}
</script>

<!-- Incluir Sortable.js -->
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
<?php
$pageTitle = 'Archivos';

// ✅ Funciones auxiliares
if (!function_exists('_fileIcon')) {
    function _fileIcon(string $category): string {
        return match($category) {
            'image'    => 'image',
            'document' => 'file-earmark-text',
            'audio'    => 'music-note',
            'video'    => 'camera-video',
            'archive'  => 'file-zip',
            default    => 'file-earmark',
        };
    }
}

if (!function_exists('_fileIconColor')) {
    function _fileIconColor(string $category): string {
        return match($category) {
            'image'    => '#22c55e',
            'document' => '#3b82f6',
            'audio'    => '#f59e0b',
            'video'    => '#ef4444',
            'archive'  => '#8b5cf6',
            default    => '#8b949e',
        };
    }
}

if (!function_exists('_formatFileSize')) {
    function _formatFileSize($bytes): string {
        $bytes = (int)($bytes ?? 0);
        if ($bytes < 1024) return $bytes . ' B';
        if ($bytes < 1048576) return round($bytes / 1024, 1) . ' KB';
        if ($bytes < 1073741824) return round($bytes / 1048576, 2) . ' MB';
        return round($bytes / 1073741824, 2) . ' GB';
    }
}

if (!function_exists('_fileExtension')) {
    function _fileExtension(string $filename): string {
        return strtolower(pathinfo($filename, PATHINFO_EXTENSION));
    }
}

// ✅ Variables con fallback
$files = $files ?? [];
$cat = $cat ?? null;
?>

<div class="card mb-3">
    <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
        <h3 class="mb-0"><i class="bi bi-folder2-open"></i> Gestor de archivos</h3>
        <div class="d-flex gap-2 align-items-center flex-wrap">
            <div class="btn-group me-2">
                <button class="btn btn-sm btn-ghost" id="viewGrid" title="Vista en cuadrícula">
                    <i class="bi bi-grid-3x3-gap"></i>
                </button>
                <button class="btn btn-sm btn-ghost" id="viewTable" title="Vista en tabla">
                    <i class="bi bi-list-ul"></i>
                </button>
            </div>
            <form method="post" action="<?= url('admin/files/sync') ?>" style="display:inline">
                <?= \App\Core\CSRF::field() ?>
                <button class="btn btn-outline btn-sm" title="Registra en el gestor los archivos que ya están en uploads/ (por ejemplo, los subidos desde el editor visual)">
                    <i class="bi bi-arrow-repeat"></i> Sincronizar con disco
                </button>
            </form>
            <form method="post" action="<?= url('admin/files/upload') ?>" enctype="multipart/form-data" class="d-flex gap-2 flex-wrap">
                <?= \App\Core\CSRF::field() ?>
                <input type="file" name="files[]" class="form-control form-control-sm" multiple required style="max-width:250px">
                <button class="btn btn-primary btn-sm"><i class="bi bi-cloud-upload"></i> Subir</button>
            </form>
        </div>
    </div>
    <div class="card-body">
        <?php if (isset($stats)): ?>
            <div class="d-flex gap-3 flex-wrap mb-3" style="font-size:.85rem;color:#666">
                <span><i class="bi bi-database"></i> <?= (int) $stats['in_db'] ?> en el gestor</span>
                <span><i class="bi bi-hdd"></i> <?= (int) $stats['on_disk'] ?> en uploads/</span>
                <?php if ((int) $stats['missing'] > 0): ?>
                    <span style="color:#b45309">
                        <i class="bi bi-exclamation-triangle"></i>
                        <?= (int) $stats['missing'] ?> sin archivo en disco
                    </span>
                <?php endif; ?>
                <?php if ((int) $stats['on_disk'] > (int) $stats['in_db']): ?>
                    <span style="color:#0f766e">
                        <i class="bi bi-info-circle"></i> hay archivos en disco fuera del gestor: pulsa «Sincronizar con disco»
                    </span>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <!-- ✅ Filtros por categoría -->
        <div class="d-flex gap-2 mb-3 flex-wrap align-items-center">
            <a class="btn btn-sm <?= !$cat ? 'btn-primary' : 'btn-ghost' ?>" href="<?= url('admin/files') ?>">
                <i class="bi bi-folder"></i> Todos
            </a>
            <?php 
            $categories = [
                'image' => 'Imágenes',
                'document' => 'Documentos',
                'audio' => 'Audio',
                'video' => 'Video',
                'archive' => 'Archivos',
                'other' => 'Otros'
            ];
            foreach ($categories as $c => $label): 
            ?>
                <a class="btn btn-sm <?= $cat === $c ? 'btn-primary' : 'btn-ghost' ?>" 
                   href="<?= url("admin/files?category=$c") ?>">
                    <i class="bi bi-<?= _fileIcon($c) ?>"></i> <?= e($label) ?>
                </a>
            <?php endforeach; ?>

            <form method="get" action="<?= url('admin/files') ?>" class="d-flex gap-2 ms-auto">
                <?php if ($cat): ?>
                    <input type="hidden" name="category" value="<?= e($cat) ?>">
                <?php endif; ?>
                <input type="search" name="q" class="form-control form-control-sm"
                       value="<?= e($q ?? '') ?>" placeholder="Buscar por nombre…" style="max-width:220px">
                <button class="btn btn-sm btn-ghost" title="Buscar"><i class="bi bi-search"></i></button>
                <?php if (!empty($q)): ?>
                    <a class="btn btn-sm btn-ghost" href="<?= url('admin/files' . ($cat ? '?category=' . urlencode($cat) : '')) ?>"
                       title="Quitar búsqueda"><i class="bi bi-x-lg"></i></a>
                <?php endif; ?>
            </form>
            <a class="btn btn-sm btn-ghost" href="<?= url('admin/files/search/advanced') ?>" title="Búsqueda avanzada">
                <i class="bi bi-funnel"></i> Avanzada
            </a>
        </div>

        <!-- ✅ Vista en Cuadrícula -->
        <div id="filesGrid" class="files-grid" style="display:grid;grid-template-columns:repeat(auto-fill,minmax(200px,1fr));gap:15px;">
            <?php if (empty($files)): ?>
                <div class="text-center text-muted p-5" style="grid-column:1/-1;">
                    <i class="bi bi-folder2-open" style="font-size:4rem"></i>
                    <p class="mt-3">No hay archivos<?= $cat ? " en esta categoría" : '' ?></p>
                </div>
            <?php else: ?>
                <?php foreach ($files as $f): ?>
                    <?php if (!is_array($f)) continue; ?>
                    <div class="file-card border rounded p-2" style="background:#fff;transition:all 0.2s;">
                        <?php if (($f['category'] ?? '') === 'image' && !empty($f['preview_url'])): ?>
                            <div class="file-preview" style="height:150px;overflow:hidden;border-radius:4px;background:#f8f9fa;">
                                <img src="<?= e($f['preview_url'] ?? '') ?>" 
                                     alt="<?= e($f['original_name'] ?? '') ?>" 
                                     style="width:100%;height:100%;object-fit:cover;">
                            </div>
                        <?php else: ?>
                            <div class="file-icon" style="height:150px;display:flex;align-items:center;justify-content:center;background:<?= _fileIconColor($f['category'] ?? 'other') ?>20;border-radius:4px;">
                                <i class="bi bi-<?= _fileIcon($f['category'] ?? 'other') ?>" 
                                   style="font-size:4rem;color:<?= _fileIconColor($f['category'] ?? 'other') ?>"></i>
                            </div>
                        <?php endif; ?>
                        <div class="file-info p-2">
                            <div class="file-name" style="font-weight:500;font-size:0.9rem;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;"
                                 title="<?= e($f['relative_path'] ?? '') ?>">
                                <?= e($f['original_name'] ?? '') ?>
                            </div>
                            <div class="file-meta d-flex justify-content-between text-muted" style="font-size:0.8rem;">
                                <span class="file-ext badge bg-secondary"><?= strtoupper(_fileExtension($f['original_name'] ?? '')) ?></span>
                                <span class="file-size"><?= e($f['size_human'] ?? _formatFileSize($f['file_size'] ?? 0)) ?></span>
                            </div>
                            <?php if (isset($f['exists_on_disk']) && !$f['exists_on_disk']): ?>
                                <div class="badge bg-warning text-dark" style="font-size:.7rem"
                                     title="El registro existe pero el archivo no está en uploads/">
                                    <i class="bi bi-exclamation-triangle"></i> sin archivo
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="file-actions d-flex justify-content-end gap-1 p-1 border-top">
                            <a href="<?= url("admin/files/{$f['id']}/download") ?>" class="btn btn-ghost btn-sm" title="Descargar">
                                <i class="bi bi-download"></i>
                            </a>
                            <a href="<?= url("admin/files/{$f['id']}/share") ?>" class="btn btn-ghost btn-sm" title="Compartir">
                                <i class="bi bi-share"></i>
                            </a>
                            <form method="post" action="<?= url("admin/files/{$f['id']}/delete") ?>" style="display:inline" 
                                  onsubmit="return confirm('¿Eliminar este archivo?')">
                                <?= \App\Core\CSRF::field() ?>
                                <button class="btn btn-ghost btn-sm text-danger" title="Eliminar">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </form>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <!-- ✅ Vista en Tabla -->
        <div id="filesTable" style="display:none;">
            <?php if (empty($files)): ?>
                <div class="text-center text-muted p-5">
                    <i class="bi bi-folder2-open" style="font-size:4rem"></i>
                    <p class="mt-3">No hay archivos</p>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th></th>
                                <th>Nombre</th>
                                <th>Tipo</th>
                                <th>Tamaño</th>
                                <th>Descargas</th>
                                <th>Subido</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($files as $f): ?>
                                <?php if (!is_array($f)) continue; ?>
                                <tr>
                                    <td>
                                        <?php if (($f['category'] ?? '') === 'image' && !empty($f['preview_url'])): ?>
                                            <div class="file-thumb" style="width:40px;height:40px;overflow:hidden;border-radius:4px;">
                                                <img src="<?= e($f['preview_url'] ?? '') ?>" 
                                                     alt="" style="width:100%;height:100%;object-fit:cover;">
                                            </div>
                                        <?php else: ?>
                                            <div class="file-icon-sm" style="width:40px;height:40px;display:flex;align-items:center;justify-content:center;background:<?= _fileIconColor($f['category'] ?? 'other') ?>20;border-radius:4px;">
                                                <i class="bi bi-<?= _fileIcon($f['category'] ?? 'other') ?>" 
                                                   style="color:<?= _fileIconColor($f['category'] ?? 'other') ?>"></i>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <strong><?= e($f['original_name'] ?? '') ?></strong>
                                        <?php if (!empty($f['description'])): ?>
                                            <br><small class="text-muted"><?= e($f['description']) ?></small>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="badge bg-secondary"><?= strtoupper(_fileExtension($f['original_name'] ?? '')) ?></span>
                                    </td>
                                    <td><?= _formatFileSize($f['file_size'] ?? 0) ?></td>
                                    <td>
                                        <span class="badge bg-info"><?= (int)($f['download_count'] ?? 0) ?></span>
                                    </td>
                                    <td>
                                        <small><?= e(!empty($f['created_at']) ? date('d/m/Y H:i', strtotime($f['created_at'])) : '-') ?></small>
                                    </td>
                                    <td>
                                        <div class="d-flex gap-1">
                                            <a class="btn btn-ghost btn-sm" href="<?= url("admin/files/{$f['id']}/download") ?>" title="Descargar">
                                                <i class="bi bi-download"></i>
                                            </a>
                                            <a class="btn btn-ghost btn-sm" href="<?= url("admin/files/{$f['id']}/share") ?>" title="Compartir">
                                                <i class="bi bi-share"></i>
                                            </a>
                                            <form method="post" action="<?= url("admin/files/{$f['id']}/delete") ?>" style="display:inline" 
                                                  onsubmit="return confirm('¿Eliminar este archivo?')">
                                                <?= \App\Core\CSRF::field() ?>
                                                <button class="btn btn-ghost btn-sm text-danger" title="Eliminar">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const viewGrid = document.getElementById('viewGrid');
    const viewTable = document.getElementById('viewTable');
    const filesGrid = document.getElementById('filesGrid');
    const filesTable = document.getElementById('filesTable');

    // ✅ Cargar vista guardada
    const savedView = localStorage.getItem('filesView') || 'grid';
    setView(savedView);

    // ✅ Eventos
    viewGrid.addEventListener('click', function() {
        setView('grid');
    });

    viewTable.addEventListener('click', function() {
        setView('table');
    });

    function setView(view) {
        if (view === 'grid') {
            filesGrid.style.display = 'grid';
            filesTable.style.display = 'none';
            viewGrid.classList.add('active', 'btn-primary');
            viewGrid.classList.remove('btn-ghost');
            viewTable.classList.remove('active', 'btn-primary');
            viewTable.classList.add('btn-ghost');
        } else {
            filesGrid.style.display = 'none';
            filesTable.style.display = 'block';
            viewGrid.classList.remove('active', 'btn-primary');
            viewGrid.classList.add('btn-ghost');
            viewTable.classList.add('active', 'btn-primary');
            viewTable.classList.remove('btn-ghost');
        }
        localStorage.setItem('filesView', view);
    }
});
</script>

<style>
/* ✅ Estilos para la vista de archivos */
.file-card:hover {
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
    transform: translateY(-2px);
}

.btn-ghost {
    background: transparent;
    border: 1px solid #dee2e6;
    color: #6c757d;
}

.btn-ghost:hover {
    background: #f8f9fa;
    color: #0d6efd;
}

.btn-ghost.active {
    background: #0d6efd;
    color: white;
    border-color: #0d6efd;
}

.files-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
    gap: 15px;
}

@media (max-width: 768px) {
    .files-grid {
        grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
    }
}
</style>
<?php
$pageTitle = 'Archivos';

// Funciones auxiliares (fuera de la clase)
function getFileIcon(string $category): string {
    return match($category) {
        'image'    => 'image',
        'document' => 'file-earmark-text',
        'audio'    => 'music-note',
        'video'    => 'camera-video',
        'archive'  => 'file-zip',
        default    => 'file-earmark',
    };
}

function getFileIconColor(string $category): string {
    return match($category) {
        'image'    => '#22c55e',
        'document' => '#3b82f6',
        'audio'    => '#f59e0b',
        'video'    => '#ef4444',
        'archive'  => '#8b5cf6',
        default    => '#8b949e',
    };
}

function formatFileSize(int $bytes): string {
    if ($bytes < 1024) return $bytes . ' B';
    if ($bytes < 1048576) return round($bytes / 1024, 1) . ' KB';
    if ($bytes < 1073741824) return round($bytes / 1048576, 2) . ' MB';
    return round($bytes / 1073741824, 2) . ' GB';
}

function getFileExtension(string $filename): string {
    return strtolower(pathinfo($filename, PATHINFO_EXTENSION));
}
?>

<div class="card mb-3">
    <div class="card-header">
        <h3><i class="bi bi-folder2-open"></i> Gestor de archivos</h3>
        <div class="d-flex gap-2 align-center">
            <!-- Toggle vista -->
            <div class="btn-group">
                <button class="btn btn-sm btn-ghost" id="viewGrid" title="Vista de iconos">
                    <i class="bi bi-grid-3x3-gap"></i>
                </button>
                <button class="btn btn-sm btn-ghost" id="viewTable" title="Vista de tabla">
                    <i class="bi bi-list-ul"></i>
                </button>
            </div>
            
            <!-- Upload -->
            <form method="post" action="<?= url('admin/files/upload') ?>" enctype="multipart/form-data" class="d-flex gap-2">
                <?= \App\Core\CSRF::field() ?>
                <input type="file" name="files[]" class="form-control" multiple required id="fileInput" style="max-width:300px">
                <button class="btn btn-primary btn-sm"><i class="bi bi-cloud-upload"></i> Subir</button>
            </form>
        </div>
    </div>
    
    <div class="card-body">
        <!-- Filtros por categoría -->
        <div class="d-flex gap-2 mb-3 flex-wrap">
            <a class="btn btn-sm <?= !$cat ? 'btn-primary' : 'btn-ghost' ?>" href="<?= url('admin/files') ?>">
                <i class="bi bi-folder"></i> Todos
            </a>
            <?php foreach (['image' => 'Imágenes', 'document' => 'Documentos', 'audio' => 'Audio', 'video' => 'Video', 'archive' => 'Archivos', 'other' => 'Otros'] as $c => $label): ?>
                <a class="btn btn-sm <?= $cat === $c ? 'btn-primary' : 'btn-ghost' ?>" href="<?= url("admin/files?category=$c") ?>">
                    <i class="bi bi-<?= getFileIcon($c) ?>"></i> <?= e($label) ?>
                </a>
            <?php endforeach; ?>
        </div>

        <!-- ═══════════════════════════════════════════════════════════════
        VISTA DE ICONOS (GRID)
        ═══════════════════════════════════════════════════════════════════ -->
        <div id="filesGrid" class="files-grid" style="display:none">
            <?php if (empty($files)): ?>
                <div class="text-center text-muted p-5">
                    <i class="bi bi-folder2-open" style="font-size:4rem"></i>
                    <p class="mt-3">No hay archivos<?= $cat ? " en esta categoría" : '' ?></p>
                </div>
            <?php else: ?>
                <?php foreach ($files as $f): ?>
                    <div class="file-card" data-category="<?= e($f['category']) ?>">
                        <!-- Preview para imágenes -->
                        <?php if ($f['category'] === 'image'): ?>
                            <div class="file-preview">
                                <img src="<?= url('uploads/' . $f['file_path']) ?>" alt="<?= e($f['original_name']) ?>" loading="lazy">
                            </div>
                        <?php else: ?>
                            <div class="file-icon" style="background: <?= getFileIconColor($f['category']) ?>20">
                                <i class="bi bi-<?= getFileIcon($f['category']) ?>" style="color: <?= getFileIconColor($f['category']) ?>"></i>
                            </div>
                        <?php endif; ?>
                        
                        <div class="file-info">
                            <div class="file-name" title="<?= e($f['original_name']) ?>">
                                <?= e($f['original_name']) ?>
                            </div>
                            <div class="file-meta">
                                <span class="file-ext"><?= strtoupper(getFileExtension($f['original_name'])) ?></span>
                                <span class="file-size"><?= formatFileSize((int)$f['file_size']) ?></span>
                            </div>
                        </div>
                        
                        <div class="file-actions">
                            <a href="<?= url("admin/files/{$f['id']}/download") ?>" class="btn btn-ghost btn-sm" title="Descargar">
                                <i class="bi bi-download"></i>
                            </a>
                            <a href="<?= url("admin/files/{$f['id']}/share") ?>" class="btn btn-ghost btn-sm" title="Compartir">
                                <i class="bi bi-share"></i>
                            </a>
                            <form method="post" action="<?= url("admin/files/{$f['id']}/delete") ?>" style="display:inline" onsubmit="return confirm('¿Eliminar archivo?')">
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

        <!-- ═══════════════════════════════════════════════════════════════
        VISTA DE TABLA
        ═══════════════════════════════════════════════════════════════════ -->
        <div id="filesTable">
            <?php if (empty($files)): ?>
                <div class="text-center text-muted p-5">
                    <i class="bi bi-folder2-open" style="font-size:4rem"></i>
                    <p class="mt-3">No hay archivos<?= $cat ? " en esta categoría" : '' ?></p>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th style="width:60px"></th>
                                <th>Nombre</th>
                                <th>Tipo</th>
                                <th>Tamaño</th>
                                <th>Descargas</th>
                                <th>Subido</th>
                                <th style="width:150px">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($files as $f): ?>
                                <tr>
                                    <td>
                                        <?php if ($f['category'] === 'image'): ?>
                                            <div class="file-thumb">
                                                <img src="<?= url('uploads/' . $f['file_path']) ?>" alt="" loading="lazy">
                                            </div>
                                        <?php else: ?>
                                            <div class="file-icon-sm" style="background: <?= getFileIconColor($f['category']) ?>20">
                                                <i class="bi bi-<?= getFileIcon($f['category']) ?>" style="color: <?= getFileIconColor($f['category']) ?>"></i>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <strong><?= e($f['original_name']) ?></strong>
                                        <br>
                                        <small class="text-muted"><?= e($f['file_path']) ?></small>
                                    </td>
                                    <td>
                                        <span class="badge badge-<?= $f['category'] ?>">
                                            <?= strtoupper(getFileExtension($f['original_name'])) ?>
                                        </span>
                                    </td>
                                    <td><?= formatFileSize((int)$f['file_size']) ?></td>
                                    <td>
                                        <span class="badge badge-info"><?= (int)$f['download_count'] ?></span>
                                    </td>
                                    <td>
                                        <small><?= e(date('d/m/Y H:i', strtotime($f['created_at']))) ?></small>
                                    </td>
                                    <td>
                                        <div class="d-flex gap-1">
                                            <a class="btn btn-ghost btn-sm" href="<?= url("admin/files/{$f['id']}/download") ?>" title="Descargar">
                                                <i class="bi bi-download"></i>
                                            </a>
                                            <a class="btn btn-ghost btn-sm" href="<?= url("admin/files/{$f['id']}/share") ?>" title="Compartir">
                                                <i class="bi bi-share"></i>
                                            </a>
                                            <form method="post" action="<?= url("admin/files/{$f['id']}/delete") ?>" style="display:inline" onsubmit="return confirm('¿Eliminar?')">
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

<!-- ═══════════════════════════════════════════════════════════════
ESTILOS PARA VISTA DE ICONOS
═══════════════════════════════════════════════════════════════ -->
<style>
.files-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
    gap: 1.5rem;
    padding: 1rem 0;
}

.file-card {
    background: var(--bg-card);
    border: 1px solid var(--border);
    border-radius: var(--radius);
    overflow: hidden;
    transition: all var(--transition);
    cursor: pointer;
    position: relative;
}

.file-card:hover {
    transform: translateY(-4px);
    box-shadow: var(--shadow-lg);
    border-color: var(--accent);
}

.file-preview {
    width: 100%;
    height: 160px;
    background: var(--bg-principal);
    display: grid;
    place-items: center;
    overflow: hidden;
}

.file-preview img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform var(--transition);
}

.file-card:hover .file-preview img {
    transform: scale(1.05);
}

.file-icon {
    width: 100%;
    height: 160px;
    display: grid;
    place-items: center;
    background: var(--bg-principal);
}

.file-icon i {
    font-size: 4rem;
}

.file-info {
    padding: 1rem;
    border-top: 1px solid var(--border);
}

.file-name {
    font-weight: 600;
    font-size: 0.9rem;
    color: var(--text-primary);
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    margin-bottom: 0.5rem;
}

.file-meta {
    display: flex;
    justify-content: space-between;
    align-items: center;
    font-size: 0.75rem;
    color: var(--text-muted);
}

.file-ext {
    background: var(--bg-card-2);
    padding: 2px 8px;
    border-radius: 4px;
    font-weight: 600;
    font-family: var(--font-mono);
}

.file-actions {
    position: absolute;
    top: 0.5rem;
    right: 0.5rem;
    display: flex;
    gap: 0.25rem;
    opacity: 0;
    transition: opacity var(--transition);
    background: rgba(13, 17, 23, 0.9);
    padding: 0.25rem;
    border-radius: var(--radius-sm);
    backdrop-filter: blur(8px);
}

.file-card:hover .file-actions {
    opacity: 1;
}

.file-actions .btn {
    padding: 0.25rem 0.5rem;
    font-size: 0.8rem;
}

/* Vista de tabla - thumbnails */
.file-thumb {
    width: 48px;
    height: 48px;
    border-radius: var(--radius-sm);
    overflow: hidden;
    background: var(--bg-principal);
    display: grid;
    place-items: center;
}

.file-thumb img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.file-icon-sm {
    width: 48px;
    height: 48px;
    border-radius: var(--radius-sm);
    display: grid;
    place-items: center;
}

.file-icon-sm i {
    font-size: 1.5rem;
}

/* Badges por categoría */
.badge-image { background: rgba(34, 197, 94, 0.15); color: var(--success); }
.badge-document { background: rgba(59, 130, 246, 0.15); color: var(--info); }
.badge-audio { background: rgba(245, 158, 11, 0.15); color: var(--warning); }
.badge-video { background: rgba(239, 68, 68, 0.15); color: var(--danger); }
.badge-archive { background: rgba(139, 92, 246, 0.15); color: #8b5cf6; }
.badge-other { background: rgba(139, 148, 158, 0.15); color: var(--text-muted); }

/* Toggle buttons */
.btn-group .btn.active {
    background: var(--accent);
    color: var(--bg-principal);
    border-color: var(--accent);
}
</style>

<!-- ═══════════════════════════════════════════════════════════════
JAVASCRIPT PARA TOGGLE DE VISTA
═══════════════════════════════════════════════════════════════ -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    const viewGrid = document.getElementById('viewGrid');
    const viewTable = document.getElementById('viewTable');
    const filesGrid = document.getElementById('filesGrid');
    const filesTable = document.getElementById('filesTable');
    
    // Cargar preferencia guardada
    const savedView = localStorage.getItem('filesView') || 'grid';
    setView(savedView);
    
    viewGrid.addEventListener('click', () => setView('grid'));
    viewTable.addEventListener('click', () => setView('table'));
    
    function setView(view) {
        if (view === 'grid') {
            filesGrid.style.display = 'grid';
            filesTable.style.display = 'none';
            viewGrid.classList.add('active');
            viewTable.classList.remove('active');
        } else {
            filesGrid.style.display = 'none';
            filesTable.style.display = 'block';
            viewGrid.classList.remove('active');
            viewTable.classList.add('active');
        }
        localStorage.setItem('filesView', view);
    }
});
</script>
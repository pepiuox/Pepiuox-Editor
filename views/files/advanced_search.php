<?php
$pageTitle = 'Búsqueda Avanzada de Archivos';
?>

<div class="card">
    <div class="card-header">
        <h4><i class="bi bi-search"></i> Búsqueda Avanzada</h4>
    </div>
    <div class="card-body">
        <form method="get" action="<?= url('admin/files/search/advanced') ?>" class="mb-4">
            <div class="row">
                <div class="col-md-4">
                    <div class="mb-3">
                        <label class="form-label">Nombre o descripción</label>
                        <input type="text" name="search" class="form-control" value="<?= e($filters['search'] ?? '') ?>" placeholder="Buscar...">
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="mb-3">
                        <label class="form-label">Categoría</label>
                        <select name="category" class="form-select">
                            <option value="">Todas</option>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?= $cat ?>" <?= ($filters['category'] ?? '') === $cat ? 'selected' : '' ?>>
                                    <?= ucfirst($cat) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="mb-3">
                        <label class="form-label">Metadata</label>
                        <input type="text" name="metadata_search" class="form-control" value="<?= e($filters['metadata_search'] ?? '') ?>" placeholder="Buscar en metadata...">
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-md-3">
                    <div class="mb-3">
                        <label class="form-label">Ancho mínimo (px)</label>
                        <input type="number" name="min_width" class="form-control" value="<?= e($filters['min_width'] ?? '') ?>">
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="mb-3">
                        <label class="form-label">Ancho máximo (px)</label>
                        <input type="number" name="max_width" class="form-control" value="<?= e($filters['max_width'] ?? '') ?>">
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="mb-3">
                        <label class="form-label">Fecha EXIF desde</label>
                        <input type="date" name="exif_date_from" class="form-control" value="<?= e($filters['exif_date_from'] ?? '') ?>">
                    </div>
                </div>
                <div class="col-md-3 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="bi bi-search"></i> Buscar
                    </button>
                </div>
            </div>
        </form>

        <?php if (!empty($files)): ?>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Nombre</th>
                            <th>Categoría</th>
                            <th>Tamaño</th>
                            <th>Descargas</th>
                            <th>Metadata</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($files as $file): ?>
                            <tr>
                                <td><?= $file['id'] ?></td>
                                <td><?= e($file['original_name']) ?></td>
                                <td><span class="badge bg-<?= _fileIconColor($file['category'] ?? 'other') ?>"><?= $file['category'] ?? 'other' ?></span></td>
                                <td><?= _formatFileSize($file['file_size'] ?? 0) ?></td>
                                <td><?= $file['download_count'] ?? 0 ?></td>
                                <td>
                                    <?php if (!empty($file['metadata'])): ?>
                                        <button class="btn btn-sm btn-info" data-bs-toggle="collapse" data-bs-target="#metadata-<?= $file['id'] ?>">
                                            <i class="bi bi-info-circle"></i>
                                        </button>
                                        <div class="collapse mt-2" id="metadata-<?= $file['id'] ?>">
                                            <pre class="small bg-light p-2 rounded"><?= json_encode(json_decode($file['metadata'], true), JSON_PRETTY_PRINT) ?></pre>
                                        </div>
                                    <?php else: ?>
                                        <span class="text-muted">Sin metadata</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="btn-group">
                                        <a href="<?= url("admin/files/{$file['id']}/download") ?>" class="btn btn-sm btn-primary">
                                            <i class="bi bi-download"></i>
                                        </a>
                                        <a href="<?= url("admin/files/{$file['id']}/edit") ?>" class="btn btn-sm btn-warning">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="text-center text-muted p-5">
                <i class="bi bi-search" style="font-size: 3rem;"></i>
                <p class="mt-3">No se encontraron archivos con los filtros especificados</p>
            </div>
        <?php endif; ?>
    </div>
</div>
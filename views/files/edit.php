<?php
// views/files/edit.php

$pageTitle = 'Editar Archivo';
?>

<div class="card">
    <div class="card-header">
        <h4><i class="bi bi-pencil"></i> Editar Archivo: <?= e($file['original_name'] ?? '') ?></h4>
    </div>
    <div class="card-body">
        <form method="post" action="<?= url("admin/files/{$file['id']}/metadata") ?>">
            <?= \App\Core\CSRF::field() ?>
            
            <div class="row">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label class="form-label">Descripción</label>
                        <textarea name="description" class="form-control" rows="3"><?= e($file['description'] ?? '') ?></textarea>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="mb-3">
                        <label class="form-label">Etiquetas (separadas por coma)</label>
                        <input type="text" name="tags" class="form-control" value="<?= e($tags ?? '') ?>" placeholder="tag1, tag2, tag3">
                    </div>
                </div>
            </div>

            <hr>
            <h5>Información EXIF</h5>
            
            <div class="row">
                <div class="col-md-4">
                    <div class="mb-3">
                        <label class="form-label">Fabricante</label>
                        <input type="text" name="exif_make" class="form-control" value="<?= e($exif['make'] ?? '') ?>">
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="mb-3">
                        <label class="form-label">Modelo</label>
                        <input type="text" name="exif_model" class="form-control" value="<?= e($exif['model'] ?? '') ?>">
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="mb-3">
                        <label class="form-label">Fecha de captura</label>
                        <input type="datetime-local" name="exif_datetime" class="form-control" value="<?= e($exif['datetime'] ?? '') ?>">
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label class="form-label">Categoría</label>
                        <select name="category" class="form-select">
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?= $cat ?>" <?= ($file['category'] ?? '') === $cat ? 'selected' : '' ?>>
                                    <?= ucfirst($cat) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="mb-3">
                        <label class="form-label">Público</label>
                        <select name="is_public" class="form-select">
                            <option value="0" <?= ($file['is_public'] ?? 0) == 0 ? 'selected' : '' ?>>Privado</option>
                            <option value="1" <?= ($file['is_public'] ?? 0) == 1 ? 'selected' : '' ?>>Público</option>
                        </select>
                    </div>
                </div>
            </div>

            <div class="mt-3">
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-save"></i> Guardar Cambios
                </button>
                <a href="<?= url('admin/files') ?>" class="btn btn-secondary">
                    <i class="bi bi-x"></i> Cancelar
                </a>
            </div>
        </form>
    </div>
</div>
<?php
/**
 * Editor de Metadatos y SEO
 * 
 * Este archivo maneja solo los metadatos de la página.
 * Para editar el contenido visual/código, usar builder.php
 */

$pageTitle = 'Editar: ' . ($page['title'] ?? 'Página');
$seo = $seo ?? ['basic' => [], 'og' => [], 'twitter' => []];
$templates = $templates ?? [];
$pages = $pages ?? [];
?>

<div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
    <h2 class="mb-0">
        <i class="bi bi-file-earmark-text text-primary"></i> 
        <?= e($page['title'] ?? 'Editar página') ?>
    </h2>
    <div class="d-flex gap-2">
        <a class="btn btn-ghost" href="<?= url('admin/pages') ?>">
            <i class="bi bi-arrow-left"></i> Volver
        </a>
        <a class="btn btn-primary" href="<?= url("admin/pages/{$page['id']}/builder") ?>">
            <i class="bi bi-palette"></i> Editar contenido
        </a>
    </div>
</div>

<div class="row g-4">
    <!-- ═══════════════════════════════════════════════════════════════
    COLUMNA IZQUIERDA: METADATOS DE LA PÁGINA
    ═══════════════════════════════════════════════════════════════ -->
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header">
                <h3><i class="bi bi-info-circle"></i> Información básica</h3>
            </div>
            <div class="card-body">
                <form method="post" action="<?= url("admin/pages/{$page['id']}") ?>" id="pageForm">
                    <?= \App\Core\CSRF::field() ?>
                    <input type="hidden" name="mode" value="metadata">
                    
                    <div class="form-grid">
                        <div class="form-group">
                            <label class="form-label">
                                <i class="bi bi-card-heading"></i> Título
                            </label>
                            <input type="text" name="title" class="form-control" 
                                   value="<?= e($page['title']) ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">
                                <i class="bi bi-link-45deg"></i> Slug URL
                            </label>
                            <input type="text" name="slug" class="form-control" 
                                   value="<?= e($page['slug']) ?>">
                            <small class="form-text">URL amigable (ej: mi-pagina)</small>
                        </div>
                        
                        <div class="form-group full-width">
                            <label class="form-label">
                                <i class="bi bi-text-paragraph"></i> Descripción
                            </label>
                            <textarea name="description" class="form-control" rows="3"><?= e($page['description'] ?? '') ?></textarea>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">
                                <i class="bi bi-type-h1"></i> H1
                            </label>
                            <input type="text" name="h1" class="form-control" 
                                   value="<?= e($page['h1'] ?? '') ?>">
                            <small class="form-text">Encabezado principal (SEO)</small>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">
                                <i class="bi bi-layout-text-window"></i> Plantilla
                            </label>
                            <select name="template_id" class="form-select">
                                <option value="">— Sin plantilla —</option>
                                <?php foreach ($templates as $t): ?>
                                    <option value="<?= (int)$t['id'] ?>" 
                                        <?= ($page['template_id'] ?? null) == $t['id'] ? 'selected' : '' ?>>
                                        <?= e($t['name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">
                                <i class="bi bi-diagram-3"></i> Página padre
                            </label>
                            <select name="parent_id" class="form-select">
                                <option value="">— Ninguna —</option>
                                <?php foreach ($pages as $p): ?>
                                    <?php if ($p['id'] == $page['id']) continue; ?>
                                    <option value="<?= (int)$p['id'] ?>" 
                                        <?= ($page['parent_id'] ?? null) == $p['id'] ? 'selected' : '' ?>>
                                        <?= e($p['title']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">
                                <i class="bi bi-flag"></i> Estado
                            </label>
                            <select name="status" class="form-select">
                                <option value="draft" <?= ($page['status'] ?? 'draft') === 'draft' ? 'selected' : '' ?>>
                                    📝 Borrador
                                </option>
                                <option value="published" <?= ($page['status'] ?? '') === 'published' ? 'selected' : '' ?>>
                                    ✅ Publicada
                                </option>
                                <option value="archived" <?= ($page['status'] ?? '') === 'archived' ? 'selected' : '' ?>>
                                    📦 Archivada
                                </option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">
                                <i class="bi bi-calendar-event"></i> Publicar el
                            </label>
                            <input type="datetime-local" name="publish_at" class="form-control" 
                                   value="<?= e($page['publish_at'] ? date('Y-m-d\TH:i', strtotime($page['publish_at'])) : '') ?>">
                            <small class="form-text">Dejar vacío para publicar inmediatamente</small>
                        </div>
                    </div>
                    
                    <div class="d-flex gap-3 mt-3">
                        <label class="form-check">
                            <input type="checkbox" name="is_published" value="1" class="form-check-input" 
                                   <?= ($page['is_published'] ?? 0) ? 'checked' : '' ?>>
                            <span class="form-check-label">Publicada</span>
                        </label>
                        
                        <label class="form-check">
                            <input type="checkbox" name="startpage" value="1" class="form-check-input" 
                                   <?= ($page['startpage'] ?? 0) ? 'checked' : '' ?>>
                            <span class="form-check-label">Página de inicio</span>
                        </label>
                    </div>
                    
                    <div class="form-group mt-3">
                        <label class="form-label">
                            <i class="bi bi-chat-left-text"></i> Nota de versión
                        </label>
                        <input type="text" name="version_note" class="form-control" 
                               placeholder="Describe los cambios realizados (opcional)">
                    </div>
                    
                    <hr class="my-4">
                    
                    <div class="d-flex justify-content-end gap-2">
                        <a class="btn btn-ghost" href="<?= url('admin/pages') ?>">Cancelar</a>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check-circle"></i> Guardar cambios
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- ═══════════════════════════════════════════════════════════════
    COLUMNA DERECHA: SEO
    ═══════════════════════════════════════════════════════════════ -->
    <div class="col-lg-4">
        <div class="card">
            <div class="card-header">
                <h3><i class="bi bi-search"></i> SEO</h3>
            </div>
            <div class="card-body">
                <form method="post" action="<?= url("admin/pages/{$page['id']}") ?>" id="seoForm">
                    <?= \App\Core\CSRF::field() ?>
                    <input type="hidden" name="mode" value="metadata">
                    
                    <h5 class="text-small text-muted mb-2">Básico</h5>
                    
                    <div class="form-group">
                        <label class="form-label">
                            Meta title 
                            <span class="char-counter" id="cc-title">0/60</span>
                        </label>
                        <input type="text" name="seo_title" class="form-control" 
                               maxlength="70" value="<?= e($seo['basic']['title'] ?? '') ?>">
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">
                            Meta description 
                            <span class="char-counter" id="cc-desc">0/160</span>
                        </label>
                        <textarea name="seo_description" class="form-control" rows="3" 
                                  maxlength="160"><?= e($seo['basic']['description'] ?? '') ?></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Keywords</label>
                        <input type="text" name="seo_keywords" class="form-control" 
                               value="<?= e($seo['basic']['keywords'] ?? '') ?>">
                        <small class="form-text">Separadas por comas</small>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Canonical URL</label>
                        <input type="url" name="seo_canonical" class="form-control" 
                               value="<?= e($seo['basic']['canonical'] ?? '') ?>">
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Robots</label>
                        <input type="text" name="seo_robots" class="form-control" 
                               value="<?= e($seo['basic']['robots'] ?? 'index,follow') ?>">
                    </div>
                    
                    <hr>
                    
                    <h5 class="text-small text-muted mb-2">Open Graph</h5>
                    
                    <div class="form-group">
                        <label class="form-label">OG Title</label>
                        <input type="text" name="og_title" class="form-control" 
                               value="<?= e($seo['og']['title'] ?? '') ?>">
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">OG Description</label>
                        <textarea name="og_description" class="form-control" rows="2"><?= e($seo['og']['description'] ?? '') ?></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">OG Image URL</label>
                        <input type="url" name="og_image" class="form-control" 
                               value="<?= e($seo['og']['image'] ?? '') ?>">
                    </div>
                    
                    <hr>
                    
                    <h5 class="text-small text-muted mb-2">Twitter Card</h5>
                    
                    <div class="form-group">
                        <label class="form-label">Card Type</label>
                        <select name="twitter_card" class="form-select">
                            <option value="summary" <?= ($seo['twitter']['card'] ?? '') === 'summary' ? 'selected' : '' ?>>
                                Summary
                            </option>
                            <option value="summary_large_image" <?= ($seo['twitter']['card'] ?? 'summary_large_image') === 'summary_large_image' ? 'selected' : '' ?>>
                                Summary large image
                            </option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Twitter Title</label>
                        <input type="text" name="twitter_title" class="form-control" 
                               value="<?= e($seo['twitter']['title'] ?? '') ?>">
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Twitter Image</label>
                        <input type="url" name="twitter_image" class="form-control" 
                               value="<?= e($seo['twitter']['image'] ?? '') ?>">
                    </div>
                    
                    <hr>
                    
                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check-circle"></i> Guardar SEO
                        </button>
                    </div>
                </form>
            </div>
        </div>
        
        <!-- Información de la página -->
        <div class="card">
            <div class="card-header">
                <h3><i class="bi bi-info-circle"></i> Información</h3>
            </div>
            <div class="card-body">
                <div class="d-flex justify-content-between mb-2">
                    <span class="text-muted text-small">ID</span>
                    <strong>#<?= (int)$page['id'] ?></strong>
                </div>
                <div class="d-flex justify-content-between mb-2">
                    <span class="text-muted text-small">Creada</span>
                    <strong class="text-small"><?= e($page['created_at'] ?? '—') ?></strong>
                </div>
                <div class="d-flex justify-content-between mb-2">
                    <span class="text-muted text-small">Actualizada</span>
                    <strong class="text-small"><?= e($page['updated_at'] ?? '—') ?></strong>
                </div>
                <div class="d-flex justify-content-between mb-2">
                    <span class="text-muted text-small">Estado</span>
                    <span class="badge badge-<?= $page['status'] ?? 'draft' ?>">
                        <?= e(ucfirst($page['status'] ?? 'draft')) ?>
                    </span>
                </div>
                <div class="d-flex justify-content-between">
                    <span class="text-muted text-small">Slug</span>
                    <code class="text-small">/<?= e($page['slug'] ?? '') ?></code>
                </div>
                
                <?php if (!empty($versions)): ?>
                <hr>
                <div class="mt-2">
                    <span class="text-muted text-small">Últimas versiones</span>
                    <?php foreach (array_slice($versions, 0, 3) as $v): ?>
                        <div class="d-flex justify-content-between text-small mt-1">
                            <span>v<?= (int)$v['version_number'] ?></span>
                            <span class="text-muted"><?= e($v['note'] ?? 'Sin nota') ?></span>
                            <span class="text-muted"><?= e(date('d/m/Y H:i', strtotime($v['created_at']))) ?></span>
                        </div>
                    <?php endforeach; ?>
                    <a href="<?= url("admin/pages/{$page['id']}/versions") ?>" class="btn btn-ghost btn-sm mt-2">
                        Ver todas las versiones
                    </a>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
// Contadores de caracteres SEO
document.addEventListener('DOMContentLoaded', function() {
    const seoTitle = document.querySelector('[name=seo_title]');
    const seoDesc = document.querySelector('[name=seo_description]');
    const ccTitle = document.getElementById('cc-title');
    const ccDesc = document.getElementById('cc-desc');
    
    function updateCounters() {
        if (seoTitle && ccTitle) {
            const len = seoTitle.value.length;
            ccTitle.textContent = len + '/60';
            ccTitle.className = 'char-counter' + (len > 60 ? ' warning' : '') + (len > 70 ? ' danger' : '');
        }
        
        if (seoDesc && ccDesc) {
            const len = seoDesc.value.length;
            ccDesc.textContent = len + '/160';
            ccDesc.className = 'char-counter' + (len > 150 ? ' warning' : '') + (len > 160 ? ' danger' : '');
        }
    }
    
    if (seoTitle) seoTitle.addEventListener('input', updateCounters);
    if (seoDesc) seoDesc.addEventListener('input', updateCounters);
    
    updateCounters();
});
</script>

<style>
.char-counter.warning { color: #f59e0b; }
.char-counter.danger { color: #ef4444; }
</style>
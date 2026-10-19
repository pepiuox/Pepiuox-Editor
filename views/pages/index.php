<?php $pageTitle = 'Páginas'; ?>
<div class="card">
<div class="card-header">
<h3><i class="bi bi-file-earmark-richtext"></i> Páginas</h3>
<div class="d-flex gap-2">
<a class="btn btn-ghost btn-sm <?= !$status?'active':'' ?>" href="<?= url('admin/pages') ?>">Todas</a>
<a class="btn btn-ghost btn-sm <?= $status==='published'?'active':'' ?>" href="<?= url('admin/pages?status=published') ?>">Publicadas</a>
<a class="btn btn-ghost btn-sm <?= $status==='draft'?'active':'' ?>" href="<?= url('admin/pages?status=draft') ?>">Borradores</a>
<?php if (\App\Core\Auth::can('pages.create')): ?>
<a class="btn btn-primary btn-sm" href="<?= url('admin/pages/create') ?>"><i class="bi bi-plus"></i> Nueva</a>
<?php endif; ?>
</div>
</div>
<div class="card-body p-0">
<table class="table">
<thead><tr><th>ID</th><th>Título</th><th>Slug</th><th>Plantilla</th><th>Estado</th><th>Autor</th><th>Actualizada</th><th></th></tr></thead>
<tbody>
<?php foreach ($pages as $p): ?>
<tr>
<td><?= (int)$p['id'] ?></td>
<td>
<a href="<?= url("admin/pages/{$p['id']}/edit") ?>"><?= e($p['title']) ?></a>
<?php if (!empty($p['startpage'])): ?><span class="badge badge-published">HOME</span><?php endif; ?>
</td>
<td><code>/<?= e($p['slug']) ?></code></td>
<td><?= e($p['template_name'] ?? '—') ?></td>
<td><span class="badge badge-<?= $p['status'] ?>"><?= e($p['status']) ?></span></td>
<td><?= e($p['author_name'] ?? '—') ?></td>
<td><?= e($p['updated_at']) ?></td>
<td class="text-end">
<!-- Builder visual -->
<a class="btn btn-primary btn-sm" href="<?= url("admin/pages/{$p['id']}/builder") ?>" title="Builder visual">
<i class="bi bi-palette"></i> Builder
</a>
<!-- Editor de metadatos -->
<a class="btn btn-ghost btn-sm" href="<?= url("admin/pages/{$p['id']}/edit") ?>" title="Editar metadatos">
<i class="bi bi-code-slash"></i>
</a>
<!-- Vista previa pública -->
<a class="btn btn-ghost btn-sm" href="<?= url($p['slug']) ?>" target="_blank" title="Ver">
<i class="bi bi-eye"></i>
</a>
<!-- Eliminar -->
<?php if (\App\Core\Auth::can('pages.delete')): ?>
<form method="post" action="<?= url("admin/pages/{$p['id']}/delete") ?>" style="display:inline" onsubmit="return confirm('¿Eliminar?')">
<?= \App\Core\CSRF::field() ?>
<button class="btn btn-ghost btn-sm text-danger" title="Eliminar"><i class="bi bi-trash"></i></button>
</form>
<?php endif; ?>
</td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
</div>
</div>
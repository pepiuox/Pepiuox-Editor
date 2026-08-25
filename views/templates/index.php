<?php $pageTitle = 'Templates'; ?>
<div class="card">
  <div class="card-header">
    <h3><i class="bi bi-layout-text-window"></i> Templates</h3>
    <?php if (can('templates.create')): ?>
      <a class="btn btn-primary btn-sm" href="<?= url('admin/templates/create') ?>"><i class="bi bi-plus"></i> Nuevo</a>
    <?php endif; ?>
  </div>
  <div class="card-body p-0">
    <table class="table">
      <thead><tr><th>ID</th><th>Nombre</th><th>Sistema</th><th>Activo</th><th>Por defecto</th><th></th></tr></thead>
      <tbody>
      <?php foreach ($templates as $t): ?>
        <tr>
          <td><?= (int)$t['id'] ?></td>
          <td><?= e($t['name']) ?></td>
          <td><?= $t['is_system_template'] ? '✓' : '' ?></td>
          <td><?= $t['is_active'] ? '<span class="badge badge-success">Sí</span>' : '<span class="badge badge-draft">No</span>' ?></td>
          <td><?= $t['is_default'] ? '⭐' : '' ?></td>
          <td class="text-end">
            <a class="btn btn-ghost btn-sm" href="<?= url("admin/templates/{$t['id']}/edit") ?>"><i class="bi bi-pencil"></i></a>
            <form method="post" action="<?= url("admin/templates/{$t['id']}/duplicate") ?>" style="display:inline"><?= \App\Core\CSRF::field() ?><button class="btn btn-ghost btn-sm"><i class="bi bi-copy"></i></button></form>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
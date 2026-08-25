<?php $pageTitle = 'Roles y permisos'; ?>
<div class="card">
  <div class="card-header">
    <h3><i class="bi bi-shield-lock"></i> Roles del sistema</h3>
    <a class="btn btn-primary btn-sm" href="<?= url('admin/roles/create') ?>"><i class="bi bi-plus"></i> Nuevo rol</a>
  </div>
  <div class="card-body p-0">
    <table class="table">
      <thead><tr><th>ID</th><th>Nombre</th><th>Descripción</th><th>Sistema</th><th>Por defecto</th><th>Usuarios</th><th></th></tr></thead>
      <tbody>
      <?php foreach ($roles as $r): ?>
        <tr>
          <td><?= (int)$r['idRol'] ?></td>
          <td><strong><?= e($r['name']) ?></strong></td>
          <td><?= e($r['description']) ?></td>
          <td><?= $r['required'] ? '<span class="badge badge-info">Sí</span>' : '' ?></td>
          <td><?= $r['default_role'] ? '⭐' : '' ?></td>
          <td><span class="badge badge-published"><?= (int)$r['users_count'] ?></span></td>
          <td class="text-end">
            <a class="btn btn-ghost btn-sm" href="<?= url("admin/roles/{$r['idRol']}/edit") ?>"><i class="bi bi-pencil"></i></a>
            <?php if (!$r['required']): ?>
              <form method="post" action="<?= url("admin/roles/{$r['idRol']}/delete") ?>" style="display:inline" onsubmit="return confirm('¿Eliminar rol? Los usuarios serán reasignados al rol por defecto.')">
                <?= \App\Core\CSRF::field() ?>
                <button class="btn btn-ghost btn-sm text-danger"><i class="bi bi-trash"></i></button>
              </form>
            <?php endif; ?>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
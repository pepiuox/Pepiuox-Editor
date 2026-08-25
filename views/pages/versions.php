<?php $pageTitle = 'Versiones: ' . $page['title']; ?>
<div class="card">
  <div class="card-header">
    <h3><i class="bi bi-clock-history"></i> Versiones de <?= e($page['title']) ?></h3>
    <a class="btn btn-ghost btn-sm" href="<?= url("admin/pages/{$page['id']}/edit") ?>"><i class="bi bi-arrow-left"></i> Volver</a>
  </div>
  <div class="card-body p-0">
    <table class="table">
      <thead><tr><th>#</th><th>Título</th><th>Autor</th><th>Nota</th><th>Fecha</th><th></th></tr></thead>
      <tbody>
      <?php foreach ($versions as $v): ?>
        <tr>
          <td><strong>v<?= (int)$v['version_number'] ?></strong></td>
          <td><?= e($v['title']) ?></td>
          <td><?= e($v['username'] ?? '—') ?></td>
          <td><?= e($v['note'] ?? '') ?></td>
          <td><?= e($v['created_at']) ?></td>
          <td class="text-end">
            <a class="btn btn-ghost btn-sm" href="<?= url("admin/pages/{$page['id']}/versions/{$v['id']}/preview") ?>" target="_blank"><i class="bi bi-eye"></i></a>
            <form method="post" action="<?= url("admin/pages/{$page['id']}/versions/{$v['id']}/restore") ?>" style="display:inline"><?= \App\Core\CSRF::field() ?><button class="btn btn-ghost btn-sm" onclick="return confirm('¿Restaurar?')"><i class="bi bi-arrow-counterclockwise"></i></button></form>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
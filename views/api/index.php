<?php $pageTitle = 'API REST'; ?>
<div class="card">
  <div class="card-header">
    <h3><i class="bi bi-plug"></i> API Applications</h3>
    <a class="btn btn-primary btn-sm" href="<?= url('admin/api/create') ?>"><i class="bi bi-plus"></i> New</a>
  </div>
  <div class="card-body p-0">
    <table class="table">
      <thead><tr><th>Name</th><th>API Key</th><th>Status</th><th>Rate Limit</th><th>Last Used</th><th></th></tr></thead>
      <tbody>
      <?php foreach ($apps as $a): ?>
        <tr>
          <td><strong><?= e($a['app_name']) ?></strong><br><small class="text-muted"><?= e($a['description']) ?></small></td>
          <td><code><?= e(substr($a['api_key'], 0, 16)) ?>...</code></td>
          <td><span class="badge badge-<?= $a['status']==='active'?'published':($a['status']==='suspended'?'draft':'archived') ?>"><?= e($a['status']) ?></span></td>
          <td><?= (int)$a['rate_limit'] ?>/min</td>
          <td><?= e($a['last_used_at'] ?? '—') ?></td>
          <td class="text-end">
            <a class="btn btn-ghost btn-sm" href="<?= url("admin/api/{$a['id']}") ?>"><i class="bi bi-gear"></i></a>
            <form method="post" action="<?= url("admin/api/{$a['id']}/delete") ?>" style="display:inline" onsubmit="return confirm('Delete?')"><?= CSRF::field() ?><button class="btn btn-ghost btn-sm text-danger"><i class="bi bi-trash"></i></button></form>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
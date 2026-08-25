<?php $pageTitle = 'Auditoría'; ?>
<div class="card">
  <div class="card-header"><h3><i class="bi bi-journal-text"></i> Registro de actividad</h3></div>
  <div class="card-body p-0">
    <table class="table">
      <thead><tr><th>Fecha</th><th>Usuario</th><th>Acción</th><th>Descripción</th><th>IP</th></tr></thead>
      <tbody>
      <?php foreach ($logs as $l): ?>
        <tr>
          <td><?= e($l['created_at']) ?></td>
          <td><?= e($l['username'] ?? '—') ?></td>
          <td><span class="badge badge-info"><?= e($l['action']) ?></span></td>
          <td><?= e($l['description']) ?></td>
          <td><code><?= e($l['ip_address']) ?></code></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
<?php $pages = ceil($total / $limit); if ($pages > 1): ?>
<div class="d-flex justify-center gap-2 mt-3">
  <?php for ($i=1; $i<=$pages; $i++): ?>
    <a class="btn btn-sm <?= $i===$page?'btn-primary':'btn-ghost' ?>" href="<?= url("admin/audit?page=$i") ?>"><?= $i ?></a>
  <?php endfor; ?>
</div>
<?php endif; ?>
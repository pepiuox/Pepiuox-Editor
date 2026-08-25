<?php $pageTitle = 'Compartir: ' . $file['original_name']; ?>
<div class="card">
  <div class="card-header">
    <h3><i class="bi bi-share"></i> Compartir: <?= e($file['original_name']) ?></h3>
    <a class="btn btn-ghost btn-sm" href="<?= url('admin/files') ?>"><i class="bi bi-arrow-left"></i> Volver</a>
  </div>
  <div class="card-body">
    <form method="post" action="<?= url("admin/files/{$file['id']}/share") ?>" class="form-grid">
      <?= \App\Core\CSRF::field() ?>
      <div class="form-group">
        <label class="form-label">Usuario</label>
        <select name="shared_with" class="form-select" required>
          <?php foreach ($users as $u): ?>
            <option value="<?= (int)$u['id'] ?>"><?= e($u['username']) ?> (<?= e($u['email']) ?>)</option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="form-group">
        <label class="form-label">Nivel</label>
        <select name="access_level" class="form-select">
          <option value="view">Ver</option>
          <option value="download">Descargar</option>
          <option value="edit">Editar</option>
        </select>
      </div>
      <div class="form-group">
        <label class="form-label">Expira en (días, opcional)</label>
        <input type="number" name="days" class="form-control" min="1" placeholder="Ilimitado">
      </div>
      <div class="form-group full-width">
        <button class="btn btn-primary"><i class="bi bi-share"></i> Compartir</button>
      </div>
    </form>

    <hr>
    <h4>Comparticiones activas</h4>
    <table class="table">
      <thead><tr><th>Usuario</th><th>Nivel</th><th>Expira</th><th>Creado</th></tr></thead>
      <tbody>
      <?php foreach ($shares as $s): ?>
        <tr>
          <td><?= e($s['username'] ?? 'Público') ?></td>
          <td><span class="badge badge-info"><?= e($s['access_level']) ?></span></td>
          <td><?= $s['expires_at'] ? e($s['expires_at']) : '∞' ?></td>
          <td><?= e($s['created_at']) ?></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
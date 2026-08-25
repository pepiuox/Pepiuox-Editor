<?php $pageTitle = 'Redirecciones'; ?>
<div class="card">
  <div class="card-header">
    <h3><i class="bi bi-signpost-split"></i> Redirecciones</h3>
    <a class="btn btn-ghost btn-sm" href="<?= url('admin/seo') ?>"><i class="bi bi-arrow-left"></i> Volver</a>
  </div>
  <div class="card-body">
    <form method="post" action="<?= url('admin/seo/redirects/store') ?>" class="form-grid mb-4 p-3 bg-card-2 rounded">
      <?= csrf_field() ?>
      <div class="form-group">
        <label class="form-label">Desde (URL antigua)</label>
        <input type="text" name="from_path" class="form-control" placeholder="/pagina-antigua" required>
      </div>
      <div class="form-group">
        <label class="form-label">Hacia (URL nueva)</label>
        <input type="text" name="to_path" class="form-control" placeholder="/pagina-nueva o https://..." required>
      </div>
      <div class="form-group">
        <label class="form-label">Código HTTP</label>
        <select name="status_code" class="form-select">
          <option value="301">301 - Permanente</option>
          <option value="302">302 - Temporal</option>
          <option value="307">307 - Temporal (preserva método)</option>
        </select>
      </div>
      <div class="form-group" style="align-self:end">
        <button class="btn btn-primary"><i class="bi bi-plus"></i> Añadir</button>
      </div>
    </form>

    <div class="table-responsive">
      <table class="table">
        <thead>
          <tr><th>Desde</th><th>Hacia</th><th>Código</th><th>Visitas</th><th>Estado</th><th>Creada</th><th></th></tr>
        </thead>
        <tbody>
        <?php foreach ($redirects as $r): ?>
          <tr>
            <td><code><?= e($r['from_path']) ?></code></td>
            <td><code><?= e($r['to_path']) ?></code></td>
            <td><span class="badge badge-info"><?= $r['status_code'] ?></span></td>
            <td><?= (int)$r['hits'] ?></td>
            <td><?= $r['is_active'] ? '<span class="badge badge-published">Activa</span>' : '<span class="badge badge-archived">Inactiva</span>' ?></td>
            <td><?= e($r['created_at']) ?></td>
            <td>
              <form method="post" action="<?= url("admin/seo/redirects/{$r['id']}/delete") ?>" style="display:inline" onsubmit="return confirm('¿Eliminar?')">
                <?= csrf_field() ?>
                <button class="btn btn-ghost btn-sm text-danger"><i class="bi bi-trash"></i></button>
              </form>
            </td>
          </tr>
        <?php endforeach; ?>
        <?php if (!$redirects): ?>
          <tr><td colspan="7" class="text-center text-muted p-4">No hay redirecciones</td></tr>
        <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>
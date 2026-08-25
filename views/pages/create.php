<?php $pageTitle = 'Nueva página'; ?>
<div class="card">
  <div class="card-header"><h3><i class="bi bi-plus-circle"></i> Nueva página</h3></div>
  <div class="card-body">
    <form method="post" action="<?= url('admin/pages') ?>">
      <?= \App\Core\CSRF::field() ?>
      <div class="form-grid">
        <div class="form-group">
          <label class="form-label">Título</label>
          <input type="text" name="title" class="form-control" required>
        </div>
        <div class="form-group">
          <label class="form-label">Slug</label>
          <input type="text" name="slug" class="form-control" placeholder="auto si vacío">
        </div>
        <div class="form-group">
          <label class="form-label">Plantilla</label>
          <select name="template_id" class="form-select">
            <option value="">— Sin plantilla —</option>
            <?php foreach ($templates as $t): ?>
              <option value="<?= (int)$t['id'] ?>"><?= e($t['name']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="form-group">
          <label class="form-label">Página padre</label>
          <select name="parent_id" class="form-select">
            <option value="">— Ninguna —</option>
            <?php foreach ($pages as $p): ?>
              <option value="<?= (int)$p['id'] ?>"><?= e($p['title']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="form-group full-width">
          <label class="form-label">Descripción</label>
          <input type="text" name="description" class="form-control" maxlength="255">
        </div>
        <div class="form-group">
          <label class="form-label">H1</label>
          <input type="text" name="h1" class="form-control">
        </div>
        <div class="form-group">
          <label class="form-label">Estado</label>
          <select name="status" class="form-select">
            <option value="draft">Borrador</option>
            <option value="published">Publicada</option>
            <option value="archived">Archivada</option>
          </select>
        </div>
        <div class="form-group">
          <label class="form-label">Publicar el</label>
          <input type="datetime-local" name="publish_at" class="form-control">
        </div>
        <div class="form-group">
          <label class="form-check"><input type="checkbox" name="is_published" value="1" class="form-check-input"> Publicada</label>
          <label class="form-check"><input type="checkbox" name="startpage" value="1" class="form-check-input"> Página de inicio</label>
        </div>
      </div>
      <hr>
      <div class="d-flex gap-2">
        <button class="btn btn-primary" type="submit"><i class="bi bi-check"></i> Crear y editar</button>
        <a class="btn btn-ghost" href="<?= url('admin/pages') ?>">Cancelar</a>
      </div>
    </form>
  </div>
</div>
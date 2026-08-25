<?php $pageTitle = 'Nuevo rol'; ?>
<div class="card">
  <div class="card-header"><h3><i class="bi bi-plus-circle"></i> Nuevo rol</h3></div>
  <div class="card-body">
    <form method="post" action="<?= url('admin/roles') ?>">
      <?= \App\Core\CSRF::field() ?>
      <div class="form-grid">
        <div class="form-group">
          <label class="form-label">Nombre</label>
          <input type="text" name="name" class="form-control" required>
        </div>
        <div class="form-group">
          <label class="form-label">Descripción</label>
          <input type="text" name="description" class="form-control">
        </div>
      </div>
      <label class="form-check"><input type="checkbox" name="default_role" value="1" class="form-check-input"> Marcar como rol por defecto para nuevos usuarios</label>

      <hr>
      <h4 class="mt-3"><i class="bi bi-key"></i> Permisos</h4>
      <p class="text-muted text-small">Marca los permisos que tendrá este rol.</p>
      <?php foreach ($grouped as $cat => $perms): ?>
        <div class="card card-accent mb-3">
          <div class="card-header"><h5><?= e($cat) ?></h5>
            <label class="form-check"><input type="checkbox" class="form-check-input check-all"> Seleccionar todos</label>
          </div>
          <div class="card-body">
            <div class="form-grid">
              <?php foreach ($perms as $p): ?>
                <label class="form-check">
                  <input type="checkbox" name="permissions[]" value="<?= (int)$p['idPer'] ?>" class="form-check-input">
                  <span><?= e($p['name']) ?></span>
                  <small class="text-muted d-block"><?= e($p['description']) ?></small>
                </label>
              <?php endforeach; ?>
            </div>
          </div>
        </div>
      <?php endforeach; ?>

      <div class="d-flex gap-2 mt-3">
        <button class="btn btn-primary"><i class="bi bi-check"></i> Crear rol</button>
        <a class="btn btn-ghost" href="<?= url('admin/roles') ?>">Cancelar</a>
      </div>
    </form>
  </div>
</div>
<script>
document.querySelectorAll('.check-all').forEach(cb => {
  cb.addEventListener('change', e => {
    const card = e.target.closest('.card');
    card.querySelectorAll('input[name="permissions[]"]').forEach(c => c.checked = e.target.checked);
  });
});
</script>
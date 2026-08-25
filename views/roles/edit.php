<?php $pageTitle = 'Editar rol: ' . $role['name']; ?>
<div class="card">
  <div class="card-header"><h3><i class="bi bi-pencil-square"></i> <?= e($role['name']) ?></h3></div>
  <div class="card-body">
    <form method="post" action="<?= url("admin/roles/{$role['idRol']}") ?>">
      <?= \App\Core\CSRF::field() ?>
      <div class="form-grid">
        <div class="form-group">
          <label class="form-label">Nombre</label>
          <input type="text" name="name" class="form-control" value="<?= e($role['name']) ?>" required <?= $role['required']?'readonly':'' ?>>
        </div>
        <div class="form-group">
          <label class="form-label">Descripción</label>
          <input type="text" name="description" class="form-control" value="<?= e($role['description']) ?>">
        </div>
      </div>
      <?php if (!$role['required']): ?>
        <label class="form-check"><input type="checkbox" name="default_role" value="1" class="form-check-input" <?= $role['default_role']?'checked':'' ?>> Rol por defecto</label>
      <?php endif; ?>

      <hr>
      <h4 class="mt-3"><i class="bi bi-key"></i> Permisos</h4>
      <?php foreach ($grouped as $cat => $perms): ?>
        <div class="card card-accent mb-3">
          <div class="card-header"><h5><?= e($cat) ?></h5>
            <label class="form-check"><input type="checkbox" class="form-check-input check-all"> Todos</label>
          </div>
          <div class="card-body">
            <div class="form-grid">
              <?php foreach ($perms as $p): ?>
                <label class="form-check">
                  <input type="checkbox" name="permissions[]" value="<?= (int)$p['idPer'] ?>" class="form-check-input" <?= in_array($p['idPer'], $rolePerms)?'checked':'' ?>>
                  <span><?= e($p['name']) ?></span>
                  <small class="text-muted d-block"><?= e($p['description']) ?></small>
                </label>
              <?php endforeach; ?>
            </div>
          </div>
        </div>
      <?php endforeach; ?>

      <div class="d-flex gap-2 mt-3">
        <button class="btn btn-primary"><i class="bi bi-check"></i> Guardar</button>
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
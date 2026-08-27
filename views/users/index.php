<?php $pageTitle = 'Usuarios'; ?>
<div class="card">
  <div class="card-header">
    <h3><i class="bi bi-people"></i> Usuarios</h3>
<?php if (can('users.create')): ?>
          <a class="btn btn-primary btn-sm" href="<?= url('admin/users/create') ?>"><i class="bi bi-plus"></i> Nuevo</a>
<?php endif; ?>
  </div>
  <div class="card-body p-0">
    <table class="table">
      <thead><tr><th>ID</th><th>Usuario</th><th>Email</th><th>Rol</th><th>Estado</th><th>Último acceso</th><th></th><th></th></tr></thead>
      <tbody>
<?php foreach ($users as $u): ?>
            <tr>
              <td><?= (int) $u['id'] ?></td>
              <td><?= e($u['username']) ?></td>
              <td><?= e($u['email']) ?></td>
              <td><span class="badge badge-admin"><?= e($u['role']) ?></span></td>
              <td><?= $u['is_active'] ? '<span class="badge badge-success">Activo</span>' : '<span class="badge badge-draft">Inactivo</span>' ?></td>
              <td><?= e($u['last_login'] ?? '—') ?></td>
              <td class="text-end">
    <?php if (can('users.edit')): ?>
                  <a class="btn btn-ghost btn-sm" href="<?= url("admin/users/{$u['id']}/edit") ?>">
                  <i class="bi bi-pencil"></i></a>
    <?php endif; ?>
              </td>
              <td class="text-end">
    <?php if (can('users.ban')): ?>
        <?php if ($u['is_banned'] ?? 0): ?>
                  <a class="btn btn-ghost btn-sm text-success" href="<?= url("admin/users/banned") ?>" title="Baneado - Ver">
                    <i class="bi bi-person-x"></i>
                  </a>
        <?php else: ?>
                  <button type="button" class="btn btn-ghost btn-sm text-danger" 
                          onclick="quickBan(<?= (int) $u['id'] ?>, '<?= e($u['username']) ?>')" title="Banear">
                    <i class="bi bi-person-x"></i>
                  </button>
        <?php endif; ?>
    <?php endif; ?>
    </td>
            </tr>
<?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<script>
function quickBan(userId, username) {
  if (!confirm(`¿Banear a ${username}? Serás redirigido al formulario de baneo.`)) return;
  // Abrir modal de banear con usuario preseleccionado
  const modal = document.getElementById('banUserModal');
  if (modal) {
    document.getElementById('selectedUserId').value = userId;
    const info = document.getElementById('selectedUserInfo');
    info.innerHTML = `<i class="bi bi-person-check"></i> <strong>${username}</strong>`;
    info.style.display = 'block';
    modal.classList.add('active');
  }
}
</script>
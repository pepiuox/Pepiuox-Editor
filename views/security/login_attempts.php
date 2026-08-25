<?php
$pageTitle = 'Intentos de Login';
$totalPages = (int)ceil($total / $perPage);
?>

<div class="row g-3 mb-4">
  <div class="col-md-3"><div class="stat-card"><div class="stat-icon"><i class="bi bi-shield-check"></i></div><div class="stat-value"><?= $stats['total_today'] ?></div><div class="stat-label">Intentos hoy</div></div></div>
  <div class="col-md-3"><div class="stat-card"><div class="stat-icon"><i class="bi bi-check-circle text-success"></i></div><div class="stat-value"><?= $stats['success_today'] ?></div><div class="stat-label">Exitosos hoy</div></div></div>
  <div class="col-md-3"><div class="stat-card"><div class="stat-icon"><i class="bi bi-x-circle text-danger"></i></div><div class="stat-value"><?= $stats['failed_today'] ?></div><div class="stat-label">Fallidos hoy</div></div></div>
  <div class="col-md-3"><div class="stat-card"><div class="stat-icon"><i class="bi bi-slash-circle text-warning"></i></div><div class="stat-value"><?= $stats['blocked_ips'] ?></div><div class="stat-label">IPs bloqueadas</div></div></div>
</div>

<!-- IPs sospechosas -->
<?php if (!empty($stats['top_failed_ips'])): ?>
<div class="card mb-4">
  <div class="card-header"><h3><i class="bi bi-exclamation-triangle text-danger"></i> IPs con múltiples fallos (últimos 7 días)</h3></div>
  <div class="card-body p-0">
    <table class="table">
      <thead><tr><th>IP</th><th>Intentos</th><th>Último intento</th><th>Acciones</th></tr></thead>
      <tbody>
      <?php foreach ($stats['top_failed_ips'] as $ip): ?>
        <tr>
          <td><code><?= e($ip['ip_address']) ?></code></td>
          <td><span class="badge badge-danger"><?= (int)$ip['attempts'] ?></span></td>
          <td><small><?= e($ip['last_attempt']) ?></small></td>
          <td>
            <form method="post" action="<?= url('admin/security/login-attempts/block') ?>" class="d-inline">
              <?= csrf_field() ?>
              <input type="hidden" name="ip_address" value="<?= e($ip['ip_address']) ?>">
              <input type="hidden" name="reason" value="Múltiples intentos fallidos">
              <input type="hidden" name="days" value="30">
              <button class="btn btn-danger btn-sm"><i class="bi bi-slash-circle"></i> Bloquear 30 días</button>
            </form>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
<?php endif; ?>

<!-- IPs bloqueadas -->
<div class="card mb-4">
  <div class="card-header">
    <h3><i class="bi bi-slash-circle"></i> IPs bloqueadas</h3>
    <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#blockIpModal"><i class="bi bi-plus"></i> Bloquear IP</button>
  </div>
  <div class="card-body p-0">
    <table class="table">
      <thead><tr><th>IP</th><th>Motivo</th><th>Bloqueada hasta</th><th>Creada</th><th></th></tr></thead>
      <tbody>
      <?php foreach ($blockedIps as $b): ?>
        <tr>
          <td><code><?= e($b['ip_address']) ?></code></td>
          <td><?= e($b['reason'] ?? '—') ?></td>
          <td><?= $b['blocked_until'] ? e($b['blocked_until']) : '<span class="badge badge-danger">Permanente</span>' ?></td>
          <td><small><?= e($b['created_at']) ?></small></td>
          <td>
            <form method="post" action="<?= url("admin/security/login-attempts/{$b['id']}/unblock") ?>" style="display:inline" onsubmit="return confirm('¿Desbloquear?')">
              <?= csrf_field() ?>
              <button class="btn btn-ghost btn-sm text-success"><i class="bi bi-unlock"></i> Desbloquear</button>
            </form>
          </td>
        </tr>
      <?php endforeach; ?>
      <?php if (!$blockedIps): ?>
        <tr><td colspan="5" class="text-center text-muted p-3">No hay IPs bloqueadas</td></tr>
      <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- Historial de intentos -->
<div class="card">
  <div class="card-header">
    <h3><i class="bi bi-clock-history"></i> Historial de intentos</h3>
    <div class="d-flex gap-2">
      <div class="btn-group">
        <a class="btn btn-sm <?= $filter==='all'?'btn-primary':'btn-ghost' ?>" href="<?= url('admin/security/login-attempts?filter=all') ?>">Todos</a>
        <a class="btn btn-sm <?= $filter==='failed'?'btn-danger':'btn-ghost' ?>" href="<?= url('admin/security/login-attempts?filter=failed') ?>">Fallidos</a>
        <a class="btn btn-sm <?= $filter==='success'?'btn-success':'btn-ghost' ?>" href="<?= url('admin/security/login-attempts?filter=success') ?>">Exitosos</a>
      </div>
      <form method="post" action="<?= url('admin/security/login-attempts/clean') ?>" onsubmit="return confirm('¿Eliminar registros antiguos?')">
        <?= csrf_field() ?>
        <select name="days" class="form-select form-select-sm d-inline-block" style="width:auto">
          <option value="7">7 días</option>
          <option value="30" selected>30 días</option>
          <option value="90">90 días</option>
        </select>
        <button class="btn btn-warning btn-sm"><i class="bi bi-trash"></i> Limpiar</button>
      </form>
    </div>
  </div>
  <div class="card-body p-0">
    <div class="table-responsive">
      <table class="table">
        <thead><tr><th>Fecha</th><th>Usuario</th><th>IP</th><th>Estado</th><th>User Agent</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($attempts as $a): ?>
          <tr>
            <td><small><?= e($a['attempt_time']) ?></small></td>
            <td><strong><?= e($a['username']) ?></strong></td>
            <td><code><?= e($a['ip_address']) ?></code></td>
            <td>
              <?php if ($a['successful']): ?>
                <span class="badge badge-success"><i class="bi bi-check"></i> Exitoso</span>
              <?php else: ?>
                <span class="badge badge-danger"><i class="bi bi-x"></i> Fallido</span>
              <?php endif; ?>
            </td>
            <td><small class="text-muted"><?= e(mb_substr($a['user_agent'] ?? '', 0, 50)) ?></small></td>
            <td>
              <?php if (!$a['successful']): ?>
                <form method="post" action="<?= url('admin/security/login-attempts/block') ?>" style="display:inline">
                  <?= csrf_field() ?>
                  <input type="hidden" name="ip_address" value="<?= e($a['ip_address']) ?>">
                  <input type="hidden" name="reason" value="Intento fallido de login">
                  <input type="hidden" name="days" value="7">
                  <button class="btn btn-ghost btn-sm text-danger" title="Bloquear IP"><i class="bi bi-slash-circle"></i></button>
                </form>
              <?php endif; ?>
            </td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
  
  <?php if ($totalPages > 1): ?>
  <div class="card-footer">
    <div class="d-flex justify-between align-center w-100">
      <small class="text-muted"><?= number_format($total) ?> registros</small>
      <div class="d-flex gap-1">
        <?php for ($i = max(1,$page-2); $i <= min($totalPages,$page+2); $i++): ?>
          <a class="btn btn-sm <?= $i===$page?'btn-primary':'btn-ghost' ?>" href="<?= url("admin/security/login-attempts?page=$i&filter=$filter") ?>"><?= $i ?></a>
        <?php endfor; ?>
      </div>
    </div>
  </div>
  <?php endif; ?>
</div>

<!-- Modal: Bloquear IP -->
<div class="modal" id="blockIpModal">
  <div class="modal-overlay" onclick="this.parentElement.classList.remove('active')"></div>
  <div class="modal-content">
    <form method="post" action="<?= url('admin/security/login-attempts/block') ?>">
      <?= csrf_field() ?>
      <div class="modal-header">
        <h3><i class="bi bi-slash-circle"></i> Bloquear IP</h3>
        <button type="button" class="modal-close" onclick="this.closest('.modal').classList.remove('active')">×</button>
      </div>
      <div class="modal-body">
        <div class="form-group">
          <label class="form-label">Dirección IP</label>
          <input type="text" name="ip_address" class="form-control" required placeholder="192.168.1.1">
        </div>
        <div class="form-group">
          <label class="form-label">Motivo</label>
          <input type="text" name="reason" class="form-control" placeholder="Motivo del bloqueo">
        </div>
        <div class="form-group">
          <label class="form-label">Duración (días)</label>
          <select name="days" class="form-select">
            <option value="1">1 día</option>
            <option value="7">7 días</option>
            <option value="30" selected>30 días</option>
            <option value="90">90 días</option>
            <option value="365">1 año</option>
            <option value="0">Permanente</option>
          </select>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-ghost" onclick="this.closest('.modal').classList.remove('active')">Cancelar</button>
        <button type="submit" class="btn btn-danger"><i class="bi bi-slash-circle"></i> Bloquear</button>
      </div>
    </form>
  </div>
</div>
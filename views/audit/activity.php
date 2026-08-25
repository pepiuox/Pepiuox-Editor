<?php
$pageTitle = 'Activity Logs';
$totalPages = (int)ceil($total / $perPage);
$actionColor = fn($a) => match(true) {
    str_contains($a,'login') => 'info',
    str_contains($a,'created') => 'success',
    str_contains($a,'deleted') => 'danger',
    str_contains($a,'updated') => 'warning',
    default => 'secondary'
};
?>

<div class="row g-3 mb-4">
  <div class="col-md-3"><div class="stat-card"><div class="stat-icon"><i class="bi bi-journal-text"></i></div><div class="stat-value"><?= number_format($stats['total']) ?></div><div class="stat-label">Total registros</div></div></div>
  <div class="col-md-3"><div class="stat-card"><div class="stat-icon"><i class="bi bi-calendar-day"></i></div><div class="stat-value"><?= $stats['today'] ?></div><div class="stat-label">Hoy</div></div></div>
  <div class="col-md-3"><div class="stat-card"><div class="stat-icon"><i class="bi bi-calendar-week"></i></div><div class="stat-value"><?= $stats['this_week'] ?></div><div class="stat-label">Últimos 7 días</div></div></div>
  <div class="col-md-3"><div class="stat-card"><div class="stat-icon"><i class="bi bi-people"></i></div><div class="stat-value"><?= $stats['unique_users'] ?></div><div class="stat-label">Usuarios únicos (30d)</div></div></div>
</div>

<div class="card">
  <div class="card-header">
    <h3><i class="bi bi-journal-text"></i> Registros de actividad</h3>
    <div class="d-flex gap-2">
      <a class="btn btn-ghost btn-sm" href="<?= url('admin/audit/activity/export?' . http_build_query($filters)) ?>"><i class="bi bi-download"></i> Exportar CSV</a>
      <form method="post" action="<?= url('admin/audit/activity/clean') ?>" onsubmit="return confirm('¿Eliminar registros antiguos?')">
        <?= csrf_field() ?>
        <div class="d-flex gap-1">
          <select name="days" class="form-select form-select-sm" style="width:auto">
            <option value="30">30 días</option>
            <option value="60">60 días</option>
            <option value="90" selected>90 días</option>
            <option value="180">180 días</option>
          </select>
          <button class="btn btn-warning btn-sm"><i class="bi bi-trash"></i> Limpiar</button>
        </div>
      </form>
    </div>
  </div>
  
  <div class="card-body">
    <!-- Filtros -->
    <form method="get" action="<?= url('admin/audit/activity') ?>" class="form-grid mb-3">
      <div class="form-group">
        <label class="form-label">Usuario</label>
        <select name="user_id" class="form-select">
          <option value="">Todos</option>
          <?php foreach ($users as $u): ?>
            <option value="<?= $u['id'] ?>" <?= ($filters['user_id']??'')==$u['id']?'selected':'' ?>><?= e($u['username']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="form-group">
        <label class="form-label">Acción</label>
        <select name="action" class="form-select">
          <option value="">Todas</option>
          <?php foreach ($actions as $a): ?>
            <option value="<?= e($a) ?>" <?= ($filters['action']??'')==$a?'selected':'' ?>><?= e($a) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="form-group">
        <label class="form-label">Desde</label>
        <input type="date" name="date_from" class="form-control" value="<?= e($filters['date_from'] ?? '') ?>">
      </div>
      <div class="form-group">
        <label class="form-label">Hasta</label>
        <input type="date" name="date_to" class="form-control" value="<?= e($filters['date_to'] ?? '') ?>">
      </div>
      <div class="form-group full-width">
        <label class="form-label">Buscar</label>
        <div class="d-flex gap-2">
          <input type="text" name="search" class="form-control" value="<?= e($filters['search'] ?? '') ?>" placeholder="Descripción, IP, URL...">
          <button class="btn btn-primary"><i class="bi bi-search"></i> Filtrar</button>
          <a class="btn btn-ghost" href="<?= url('admin/audit/activity') ?>">Limpiar</a>
        </div>
      </div>
    </form>
  </div>
  
  <div class="card-body p-0">
    <div class="table-responsive">
      <table class="table">
        <thead>
          <tr>
            <th>Fecha</th>
            <th>Usuario</th>
            <th>Acción</th>
            <th>Descripción</th>
            <th>IP</th>
            <th>Método</th>
            <th></th>
          </tr>
        </thead>
        <tbody>
        <?php foreach ($logs as $l): ?>
          <tr>
            <td><small><?= e($l['created_at']) ?></small></td>
            <td>
              <?php if ($l['username']): ?>
                <span class="badge badge-<?= $l['role']==='Super admin'?'admin':($l['role']==='Admin'?'manager':'editor') ?>">
                  <?= e($l['username']) ?>
                </span>
              <?php else: ?>
                <span class="text-muted text-small">Sistema</span>
              <?php endif; ?>
            </td>
            <td><span class="badge badge-<?= $actionColor($l['action']) ?>"><?= e($l['action']) ?></span></td>
            <td><small><?= e(mb_substr($l['description'] ?? '', 0, 60)) ?></small></td>
            <td><code class="text-small"><?= e($l['ip_address'] ?? '—') ?></code></td>
            <td><span class="badge badge-ghost"><?= e($l['request_method'] ?? '—') ?></span></td>
            <td>
              <a class="btn btn-ghost btn-sm" href="<?= url("admin/audit/activity/{$l['id']}") ?>" title="Ver detalle"><i class="bi bi-eye"></i></a>
            </td>
          </tr>
        <?php endforeach; ?>
        <?php if (!$logs): ?>
          <tr><td colspan="7" class="text-center text-muted p-4">No hay registros</td></tr>
        <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
  
  <?php if ($totalPages > 1): ?>
  <div class="card-footer">
    <div class="d-flex justify-between align-center w-100 flex-wrap gap-2">
      <small class="text-muted">Mostrando <?= count($logs) ?> de <?= number_format($total) ?> registros</small>
      <div class="d-flex gap-1">
        <?php for ($i = max(1, $page-2); $i <= min($totalPages, $page+2); $i++): ?>
          <a class="btn btn-sm <?= $i===$page?'btn-primary':'btn-ghost' ?>" href="<?= url('admin/audit/activity?page='.$i.'&'.http_build_query($filters)) ?>"><?= $i ?></a>
        <?php endfor; ?>
      </div>
    </div>
  </div>
  <?php endif; ?>
</div>
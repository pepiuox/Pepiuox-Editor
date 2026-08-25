<?php
$pageTitle = 'Métricas avanzadas';
$fmtNum = fn($n) => number_format((int)$n, 0, ',', '.');
?>

<div class="d-flex justify-between align-center mb-3 flex-wrap gap-2">
  <h2 class="mb-0"><i class="bi bi-graph-up text-primary"></i> Métricas avanzadas</h2>
  <form method="get" action="<?= url('admin/analytics') ?>" class="d-flex gap-2">
    <select name="days" class="form-select form-select-sm" style="width:auto" onchange="this.form.submit()">
      <?php foreach ([7=>'7 días',15=>'15 días',30=>'30 días',60=>'60 días',90=>'90 días'] as $k=>$v): ?>
        <option value="<?= $k ?>" <?= $days===$k?'selected':'' ?>><?= $v ?></option>
      <?php endforeach; ?>
    </select>
    <div class="btn-group">
      <a class="btn btn-ghost btn-sm" href="<?= url('admin/analytics/export?type=visits&days='.$days) ?>"><i class="bi bi-download"></i> Visitas</a>
      <a class="btn btn-ghost btn-sm" href="<?= url('admin/analytics/export?type=logins&days='.$days) ?>"><i class="bi bi-download"></i> Logins</a>
      <a class="btn btn-ghost btn-sm" href="<?= url('admin/analytics/export?type=pages&days='.$days) ?>"><i class="bi bi-download"></i> Páginas</a>
    </div>
  </form>
</div>

<!-- Tarjetas resumen -->
<div class="row g-3 mb-4">
  <div class="col-md-3">
    <div class="stat-card">
      <div class="stat-icon"><i class="bi bi-eye"></i></div>
      <div class="stat-value"><?= $fmtNum($summary['visits']) ?></div>
      <div class="stat-label">Visitas totales</div>
    </div>
  </div>
  <div class="col-md-3">
    <div class="stat-card">
      <div class="stat-icon"><i class="bi bi-people"></i></div>
      <div class="stat-value"><?= $fmtNum($summary['uniqueVisitors']) ?></div>
      <div class="stat-label">Visitantes únicos</div>
    </div>
  </div>
  <div class="col-md-3">
    <div class="stat-card">
      <div class="stat-icon"><i class="bi bi-box-arrow-in-right"></i></div>
      <div class="stat-value"><?= $fmtNum($summary['logins']) ?></div>
      <div class="stat-label">Logins exitosos</div>
    </div>
  </div>
  <div class="col-md-3">
    <div class="stat-card">
      <div class="stat-icon"><i class="bi bi-person-plus"></i></div>
      <div class="stat-value"><?= $fmtNum($summary['newUsers']) ?></div>
      <div class="stat-label">Nuevos usuarios</div>
    </div>
  </div>
</div>

<!-- Gráficos principales -->
<div class="row g-3 mb-4">
  <div class="col-lg-8">
    <div class="chart-card">
      <div class="chart-title"><i class="bi bi-graph-up"></i> Visitas diarias</div>
      <div class="chart-container"><canvas id="visitsChart"></canvas></div>
    </div>
  </div>
  <div class="col-lg-4">
    <div class="chart-card">
      <div class="chart-title"><i class="bi bi-pie-chart"></i> Estado de páginas</div>
      <div class="chart-container"><canvas id="statusChart"></canvas></div>
    </div>
  </div>
</div>

<div class="row g-3 mb-4">
  <div class="col-lg-6">
    <div class="chart-card">
      <div class="chart-title"><i class="bi bi-shield-lock"></i> Logins (exitosos vs fallidos)</div>
      <div class="chart-container"><canvas id="loginsChart"></canvas></div>
    </div>
  </div>
  <div class="col-lg-6">
    <div class="chart-card">
      <div class="chart-title"><i class="bi bi-plug"></i> Uso de API</div>
      <div class="chart-container"><canvas id="apiChart"></canvas></div>
    </div>
  </div>
</div>

<!-- Tablas -->
<div class="row g-3">
  <div class="col-lg-6">
    <div class="card">
      <div class="card-header"><h3><i class="bi bi-trophy"></i> Top páginas</h3></div>
      <div class="card-body p-0">
        <table class="table">
          <thead><tr><th>#</th><th>Página</th><th>Visitas</th><th>Únicos</th></tr></thead>
          <tbody>
          <?php foreach ($topPages as $i => $p): ?>
            <tr>
              <td><strong><?= $i+1 ?></strong></td>
              <td><a href="<?= url("admin/pages/{$p['id']}/edit") ?>"><?= e($p['title']) ?></a><br><small class="text-muted">/<?= e($p['slug']) ?></small></td>
              <td><span class="badge badge-published"><?= $fmtNum($p['visits']) ?></span></td>
              <td><?= $fmtNum($p['unique_visitors']) ?></td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <div class="col-lg-6">
    <div class="card">
      <div class="card-header"><h3><i class="bi bi-exclamation-triangle text-danger"></i> IPs sospechosas</h3></div>
      <div class="card-body p-0">
        <table class="table">
          <thead><tr><th>IP</th><th>Intentos</th><th>Último</th><th></th></tr></thead>
          <tbody>
          <?php foreach ($suspiciousIps as $ip): ?>
            <tr>
              <td><code><?= e($ip['ip_address']) ?></code></td>
              <td><span class="badge badge-danger"><?= (int)$ip['attempts'] ?></span></td>
              <td><small><?= e($ip['last_attempt']) ?></small></td>
              <td><a class="btn btn-ghost btn-sm" href="<?= url('admin/security/block?ip='.urlencode($ip['ip_address'])) ?>" title="Bloquear"><i class="bi bi-slash-circle"></i></a></td>
            </tr>
          <?php endforeach; ?>
          <?php if (!$suspiciousIps): ?>
            <tr><td colspan="4" class="text-center text-muted p-3">Sin actividad sospechosa</td></tr>
          <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>

<div class="row g-3 mt-2">
  <div class="col-12">
    <div class="card">
      <div class="card-header"><h3><i class="bi bi-people-fill"></i> Usuarios más activos</h3></div>
      <div class="card-body p-0">
        <table class="table">
          <thead><tr><th>Usuario</th><th>Rol</th><th>Días activos</th><th>Total logins</th><th>Último login</th></tr></thead>
          <tbody>
          <?php foreach ($userActivity as $u): ?>
            <tr>
              <td><strong><?= e($u['username']) ?></strong></td>
              <td><span class="badge badge-info"><?= e($u['role']) ?></span></td>
              <td><?= (int)$u['active_days'] ?> / <?= $days ?></td>
              <td><?= $fmtNum($u['total_logins']) ?></td>
              <td><?= e($u['last_login'] ?? '—') ?></td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>

<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
Chart.defaults.color = '#8b949e';
Chart.defaults.borderColor = '#30363d';
Chart.defaults.font.family = "'Inter', sans-serif";

// Visitas
new Chart(document.getElementById('visitsChart'), {
  type: 'line',
  data: {
    labels: <?= json_encode(array_column($visitsByDay, 'day')) ?>,
    datasets: [
      { label: 'Visitas', data: <?= json_encode(array_column($visitsByDay, 'visits')) ?>,
        borderColor: '#00d4aa', backgroundColor: 'rgba(0,212,170,0.1)', fill: true, tension: 0.4 },
      { label: 'Únicos', data: <?= json_encode(array_column($visitsByDay, 'unique_visitors')) ?>,
        borderColor: '#3b82f6', backgroundColor: 'rgba(59,130,246,0.1)', fill: true, tension: 0.4 }
    ]
  },
  options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'top' } },
    scales: { y: { beginAtZero: true, grid: { color: 'rgba(48,54,61,0.5)' } }, x: { grid: { display: false } } } }
});

// Estado páginas
new Chart(document.getElementById('statusChart'), {
  type: 'doughnut',
  data: {
    labels: <?= json_encode(array_column($pageStatus, 'status')) ?>,
    datasets: [{
      data: <?= json_encode(array_column($pageStatus, 'total')) ?>,
      backgroundColor: ['#f59e0b','#00d4aa','#8b949e'],
      borderColor: '#1c2333', borderWidth: 2
    }]
  },
  options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'bottom' } } }
});

// Logins
new Chart(document.getElementById('loginsChart'), {
  type: 'bar',
  data: {
    labels: <?= json_encode(array_column($loginsByDay, 'day')) ?>,
    datasets: [
      { label: 'Exitosos', data: <?= json_encode(array_column($loginsByDay, 'success')) ?>, backgroundColor: '#22c55e' },
      { label: 'Fallidos', data: <?= json_encode(array_column($loginsByDay, 'failed')) ?>, backgroundColor: '#ef4444' }
    ]
  },
  options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'top' } },
    scales: { y: { beginAtZero: true, stacked: true }, x: { stacked: true, grid: { display: false } } } }
});

// API
new Chart(document.getElementById('apiChart'), {
  type: 'line',
  data: {
    labels: <?= json_encode(array_column($apiUsage, 'day')) ?>,
    datasets: [
      { label: 'Llamadas', data: <?= json_encode(array_column($apiUsage, 'calls')) ?>,
        borderColor: '#f59e0b', backgroundColor: 'rgba(245,158,11,0.1)', fill: true, tension: 0.4, yAxisID: 'y' },
      { label: 'Tiempo medio (ms)', data: <?= json_encode(array_map(fn($v)=>round($v['avg_time']*1000,2), $apiUsage)) ?>,
        borderColor: '#3b82f6', tension: 0.4, yAxisID: 'y1' }
    ]
  },
  options: { responsive: true, maintainAspectRatio: false,
    scales: {
      y: { type: 'linear', position: 'left', beginAtZero: true, grid: { color: 'rgba(48,54,61,0.5)' } },
      y1: { type: 'linear', position: 'right', beginAtZero: true, grid: { drawOnChartArea: false } },
      x: { grid: { display: false } }
    }
  }
});
</script>

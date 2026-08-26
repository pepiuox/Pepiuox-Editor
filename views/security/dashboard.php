<?php
/**
 * Dashboard de Seguridad — PePiuoX Editor
 * 
 * Variables esperadas desde SecurityDashboardController::index():
 *   $period, $stats, $hourly, $dailyAttempts, $topFailedIps,
 *   $targetedUsers, $activeBlockedIps, $bannedUsers,
 *   $activeSessions, $suspiciousActivity, $apiUsage
 */

// ═══════════════════════════════════════════════════════════════════
// VALORES POR DEFECTO (evita errores si el controlador falla)
// ═══════════════════════════════════════════════════════════════════
$period     = $period ?? 7;
$stats      = $stats ?? [
    'total_attempts'   => 0,
    'failed_attempts'  => 0,
    'success_attempts' => 0,
    'blocked_ips'      => 0,
    'banned_users'     => 0,
    'active_sessions'  => 0,
    'two_factor_users' => 0,
];
$hourly              = $hourly ?? [];
$dailyAttempts       = $dailyAttempts ?? [];
$topFailedIps        = $topFailedIps ?? [];
$targetedUsers       = $targetedUsers ?? [];
$activeBlockedIps    = $activeBlockedIps ?? [];
$bannedUsers         = $bannedUsers ?? [];
$activeSessions      = $activeSessions ?? [];
$suspiciousActivity  = $suspiciousActivity ?? [];
$apiUsage            = $apiUsage ?? [];

// ═══════════════════════════════════════════════════════════════════
// FUNCIÓN PHP: humanTimeAgo (FALTABA EN LA VERSIÓN ORIGINAL)
// ═══════════════════════════════════════════════════════════════════
if (!function_exists('humanTimeAgo')) {
    function humanTimeAgo($timestamp): string
    {
        if (empty($timestamp)) return '—';
        
        // Si es string, convertir a timestamp Unix
        if (is_string($timestamp)) {
            $timestamp = strtotime($timestamp);
            if ($timestamp === false) return '—';
        }
        
        // Si viene como timestamp Unix (int) de user_sessions.last_activity
        $now = time();
        $diff = $now - $timestamp;
        
        if ($diff < 0) return 'ahora';
        if ($diff < 60) return $diff . 's';
        if ($diff < 3600) return floor($diff / 60) . ' min';
        if ($diff < 86400) return floor($diff / 3600) . ' h';
        if ($diff < 604800) return floor($diff / 86400) . ' d';
        return floor($diff / 604800) . ' sem';
    }
}

// ═══════════════════════════════════════════════════════════════════
// CÁLCULO DE NIVEL DE RIESGO
// ═══════════════════════════════════════════════════════════════════
$riskLevel = function($failed, $total) {
    if ($total === 0) return 'low';
    $ratio = $failed / $total;
    if ($ratio > 0.5) return 'high';
    if ($ratio > 0.2) return 'medium';
    return 'low';
};

$risk = $riskLevel((int)($stats['failed_attempts'] ?? 0), (int)($stats['total_attempts'] ?? 0));
$riskColor = ['low' => 'success', 'medium' => 'warning', 'high' => 'danger'][$risk] ?? 'success';
$riskLabel = ['low' => 'Bajo', 'medium' => 'Medio', 'high' => 'Alto'][$risk] ?? 'Bajo';

$periodLabels = [1 => '24h', 7 => '7 días', 15 => '15 días', 30 => '30 días', 90 => '90 días'];
?>

<!-- ═══════════════════════════════════════════════════════════════════
ENCABEZADO
═══════════════════════════════════════════════════════════════════ -->
<div class="d-flex justify-between align-center mb-3 flex-wrap gap-2">
    <h2 class="mb-0"><i class="bi bi-shield-lock text-primary"></i> Dashboard de Seguridad</h2>
    <form method="get" action="<?= url('admin/security/dashboard') ?>" class="d-flex gap-2">
        <select name="period" class="form-select form-select-sm" style="width:auto" onchange="this.form.submit()">
            <?php foreach ($periodLabels as $k => $v): ?>
                <option value="<?= $k ?>" <?= $period == $k ? 'selected' : '' ?>><?= e($v) ?></option>
            <?php endforeach; ?>
        </select>
        <a class="btn btn-ghost btn-sm" href="<?= url('admin/security/login-attempts') ?>">
            <i class="bi bi-list"></i> Ver todos los intentos
        </a>
    </form>
</div>

<!-- ═══════════════════════════════════════════════════════════════════
NIVEL DE RIESGO GLOBAL
═══════════════════════════════════════════════════════════════════ -->
<div class="alert alert-<?= $riskColor ?> mb-4">
    <div class="d-flex align-center gap-3">
        <i class="bi bi-<?= $risk === 'high' ? 'exclamation-octagon' : ($risk === 'medium' ? 'exclamation-triangle' : 'shield-check') ?>" 
           style="font-size:2rem"></i>
        <div>
            <strong>Nivel de riesgo: <?= e($riskLabel) ?></strong>
            <div class="text-small">
                <?php if ($risk === 'high'): ?>
                    Se han detectado múltiples intentos sospechosos. Revisa las IPs bloqueadas y usuarios atacados.
                <?php elseif ($risk === 'medium'): ?>
                    Actividad inusual detectada. Monitoriza los intentos fallidos.
                <?php else: ?>
                    El sistema funciona con normalidad. Sin amenazas significativas.
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- ═══════════════════════════════════════════════════════════════════
TARJETAS DE ESTADÍSTICAS
═══════════════════════════════════════════════════════════════════ -->
<div class="row g-3 mb-4">
    <div class="col-md-3 col-sm-6">
        <div class="stat-card">
            <div class="stat-icon"><i class="bi bi-key"></i></div>
            <div class="stat-value"><?= number_format((int)($stats['total_attempts'] ?? 0)) ?></div>
            <div class="stat-label">Intentos de login</div>
            <div class="stat-trend trend-<?= ($stats['failed_attempts'] ?? 0) > ($stats['success_attempts'] ?? 0) ? 'down' : 'up' ?>">
                <i class="bi bi-<?= ($stats['failed_attempts'] ?? 0) > ($stats['success_attempts'] ?? 0) ? 'arrow-down' : 'arrow-up' ?>"></i>
                <?= number_format((int)($stats['success_attempts'] ?? 0)) ?> exitosos
            </div>
        </div>
    </div>
    <div class="col-md-3 col-sm-6">
        <div class="stat-card">
            <div class="stat-icon" style="background:rgba(239,68,68,0.1);color:var(--danger)"><i class="bi bi-x-circle"></i></div>
            <div class="stat-value"><?= number_format((int)($stats['failed_attempts'] ?? 0)) ?></div>
            <div class="stat-label">Intentos fallidos</div>
            <div class="stat-trend trend-down">
                <?= ($stats['total_attempts'] ?? 0) > 0 
                    ? round((($stats['failed_attempts'] ?? 0) / ($stats['total_attempts'] ?? 1)) * 100, 1) 
                    : 0 ?>% del total
            </div>
        </div>
    </div>
    <div class="col-md-3 col-sm-6">
        <div class="stat-card">
            <div class="stat-icon" style="background:rgba(245,158,11,0.1);color:var(--warning)"><i class="bi bi-slash-circle"></i></div>
            <div class="stat-value"><?= (int)($stats['blocked_ips'] ?? 0) ?></div>
            <div class="stat-label">IPs bloqueadas</div>
            <div class="stat-trend"><?= (int)($stats['banned_users'] ?? 0) ?> usuarios baneados</div>
        </div>
    </div>
    <div class="col-md-3 col-sm-6">
        <div class="stat-card">
            <div class="stat-icon" style="background:rgba(59,130,246,0.1);color:var(--info)"><i class="bi bi-people"></i></div>
            <div class="stat-value"><?= (int)($stats['active_sessions'] ?? 0) ?></div>
            <div class="stat-label">Sesiones activas</div>
            <div class="stat-trend"><?= (int)($stats['two_factor_users'] ?? 0) ?> con 2FA</div>
        </div>
    </div>
</div>

<!-- ═══════════════════════════════════════════════════════════════════
RATE LIMITING ACTIVO
═══════════════════════════════════════════════════════════════════ -->
<div class="row g-3 mb-4">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h3><i class="bi bi-speedometer2 text-warning"></i> Rate Limiting Activo</h3>
            </div>
            <div class="card-body p-0">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Ruta</th>
                            <th>Límite</th>
                            <th>Ventana</th>
                            <th>IPs Bloqueadas Ahora</th>
                            <th>Total Bloqueos (24h)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $rateLimits = [
                            ['ruta' => '/login', 'limite' => 5, 'ventana' => '1 min', 'prefix' => 'login'],
                            ['ruta' => '/forgot', 'limite' => 3, 'ventana' => '1 hora', 'prefix' => 'forgot'],
                            ['ruta' => '/reset', 'limite' => 5, 'ventana' => '1 hora', 'prefix' => 'reset'],
                            ['ruta' => '/2fa/challenge/verify', 'limite' => 3, 'ventana' => '1 min', 'prefix' => '2fa'],
                        ];
                        
                        foreach ($rateLimits as $rl):
                            try {
                                $blockedNow = (int)\App\Core\Database::fetch(
                                    "SELECT COUNT(*) AS c FROM cache_settings 
                                     WHERE cache_key LIKE ? AND expires_at > NOW()",
                                    ['rate_limit:' . $rl['prefix'] . ':%']
                                )['c'];
                                
                                $blocked24h = (int)\App\Core\Database::fetch(
                                    "SELECT COUNT(*) AS c FROM activity_log 
                                     WHERE action = 'rate_limit_exceeded' 
                                       AND description LIKE ? 
                                       AND created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)",
                                    ['%' . $rl['prefix'] . '%']
                                )['c'];
                            } catch (\Throwable $e) {
                                $blockedNow = 0;
                                $blocked24h = 0;
                            }
                        ?>
                            <tr>
                                <td><code><?= e($rl['ruta']) ?></code></td>
                                <td><span class="badge badge-info"><?= $rl['limite'] ?> intentos</span></td>
                                <td><span class="badge badge-ghost"><?= e($rl['ventana']) ?></span></td>
                                <td>
                                    <?php if ($blockedNow > 0): ?>
                                        <span class="badge badge-danger"><?= $blockedNow ?> IPs</span>
                                    <?php else: ?>
                                        <span class="text-muted">0</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="badge badge-<?= $blocked24h > 10 ? 'danger' : ($blocked24h > 0 ? 'warning' : 'success') ?>">
                                        <?= $blocked24h ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- ═══════════════════════════════════════════════════════════════════
GRÁFICOS PRINCIPALES
═══════════════════════════════════════════════════════════════════ -->
<div class="row g-3 mb-4">
    <div class="col-lg-8">
        <div class="chart-card">
            <div class="chart-title"><i class="bi bi-graph-up"></i> Intentos de login por hora (últimas 24h)</div>
            <div class="chart-container"><canvas id="hourlyChart"></canvas></div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="chart-card">
            <div class="chart-title"><i class="bi bi-pie-chart"></i> Distribución de intentos</div>
            <div class="chart-container"><canvas id="distributionChart"></canvas></div>
        </div>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-lg-6">
        <div class="chart-card">
            <div class="chart-title"><i class="bi bi-calendar-week"></i> Intentos por día</div>
            <div class="chart-container"><canvas id="dailyChart"></canvas></div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="chart-card">
            <div class="chart-title"><i class="bi bi-plug"></i> Uso de API</div>
            <div class="chart-container"><canvas id="apiChart"></canvas></div>
        </div>
    </div>
</div>

<!-- ═══════════════════════════════════════════════════════════════════
TABLAS DE AMENAZAS
═══════════════════════════════════════════════════════════════════ -->
<div class="row g-3 mb-4">
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header">
                <h3><i class="bi bi-exclamation-triangle text-danger"></i> Top IPs atacantes</h3>
            </div>
            <div class="card-body p-0">
                <table class="table">
                    <thead><tr><th>IP</th><th>Intentos</th><th>Usuarios</th><th>Último</th><th></th></tr></thead>
                    <tbody>
                        <?php foreach ($topFailedIps as $ip): ?>
                            <tr>
                                <td><code><?= e($ip['ip_address']) ?></code></td>
                                <td><span class="badge badge-danger"><?= (int)$ip['attempts'] ?></span></td>
                                <td><?= (int)($ip['users_targeted'] ?? 0) ?></td>
                                <td><small><?= e($ip['last_attempt'] ?? '—') ?></small></td>
                                <td>
                                    <form method="post" action="<?= url('admin/security/login-attempts/block') ?>" style="display:inline">
                                        <?= \App\Core\CSRF::field() ?>
                                        <input type="hidden" name="ip_address" value="<?= e($ip['ip_address']) ?>">
                                        <input type="hidden" name="reason" value="Ataque detectado desde dashboard">
                                        <input type="hidden" name="days" value="30">
                                        <button class="btn btn-ghost btn-sm text-danger" title="Bloquear">
                                            <i class="bi bi-slash-circle"></i>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (empty($topFailedIps)): ?>
                            <tr><td colspan="5" class="text-center text-muted p-3">Sin actividad sospechosa</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="col-lg-6">
        <div class="card">
            <div class="card-header">
                <h3><i class="bi bi-person-badge text-warning"></i> Usuarios más atacados</h3>
            </div>
            <div class="card-body p-0">
                <table class="table">
                    <thead><tr><th>Usuario</th><th>Intentos</th><th>IPs únicas</th><th>Último</th></tr></thead>
                    <tbody>
                        <?php foreach ($targetedUsers as $u): ?>
                            <tr>
                                <td><strong><?= e($u['username']) ?></strong></td>
                                <td><span class="badge badge-warning"><?= (int)$u['attempts'] ?></span></td>
                                <td><?= (int)($u['unique_ips'] ?? 0) ?></td>
                                <td><small><?= e($u['last_attempt'] ?? '—') ?></small></td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (empty($targetedUsers)): ?>
                            <tr><td colspan="4" class="text-center text-muted p-3">Sin usuarios atacados</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- ═══════════════════════════════════════════════════════════════════
IPs BLOQUEADAS Y USUARIOS BANEADOS
═══════════════════════════════════════════════════════════════════ -->
<div class="row g-3 mb-4">
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header">
                <h3><i class="bi bi-slash-circle text-danger"></i> IPs bloqueadas activas</h3>
                <span class="badge badge-danger"><?= count($activeBlockedIps) ?></span>
            </div>
            <div class="card-body p-0">
                <table class="table">
                    <thead><tr><th>IP</th><th>Motivo</th><th>Hasta</th><th></th></tr></thead>
                    <tbody>
                        <?php foreach ($activeBlockedIps as $b): ?>
                            <tr>
                                <td><code><?= e($b['ip_address']) ?></code></td>
                                <td><small><?= e(mb_substr($b['reason'] ?? '—', 0, 30)) ?></small></td>
                                <td>
                                    <?= !empty($b['blocked_until']) 
                                        ? '<small>' . e($b['blocked_until']) . '</small>' 
                                        : '<span class="badge badge-danger">∞</span>' ?>
                                </td>
                                <td>
                                    <form method="post" action="<?= url("admin/security/login-attempts/{$b['id']}/unblock") ?>" style="display:inline">
                                        <?= \App\Core\CSRF::field() ?>
                                        <button class="btn btn-ghost btn-sm text-success" title="Desbloquear">
                                            <i class="bi bi-unlock"></i>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (empty($activeBlockedIps)): ?>
                            <tr><td colspan="4" class="text-center text-muted p-3">No hay IPs bloqueadas</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="col-lg-6">
        <div class="card">
            <div class="card-header">
                <h3><i class="bi bi-person-x text-danger"></i> Usuarios baneados</h3>
                <span class="badge badge-danger"><?= count($bannedUsers) ?></span>
            </div>
            <div class="card-body p-0">
                <table class="table">
                    <thead><tr><th>Usuario</th><th>Email</th><th>Motivo</th><th>Baneado</th></tr></thead>
                    <tbody>
                        <?php foreach ($bannedUsers as $u): ?>
                            <tr>
                                <td><strong><?= e($u['username']) ?></strong></td>
                                <td><small><?= e($u['email']) ?></small></td>
                                <td><small><?= e(mb_substr($u['banned_reason'] ?? '—', 0, 30)) ?></small></td>
                                <td><small><?= e($u['updated_at'] ?? '—') ?></small></td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (empty($bannedUsers)): ?>
                            <tr><td colspan="4" class="text-center text-muted p-3">No hay usuarios baneados</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- ═══════════════════════════════════════════════════════════════════
SESIONES ACTIVAS Y ACTIVIDAD SOSPECHOSA
═══════════════════════════════════════════════════════════════════ -->
<div class="row g-3 mb-4">
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header">
                <h3><i class="bi bi-broadcast text-info"></i> Sesiones activas (últimos 30 min)</h3>
            </div>
            <div class="card-body p-0">
                <table class="table table-sm">
                    <thead><tr><th>Usuario</th><th>IP</th><th>Rol</th><th>Activo hace</th></tr></thead>
                    <tbody>
                        <?php foreach ($activeSessions as $s): ?>
                            <tr>
                                <td><strong><?= e($s['username'] ?? 'Anónimo') ?></strong></td>
                                <td><code class="text-small"><?= e($s['ip_address']) ?></code></td>
                                <td><span class="badge badge-info"><?= e($s['role'] ?? '—') ?></span></td>
                                <td>
                                    <small><?= humanTimeAgo($s['last_activity']) ?></small>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (empty($activeSessions)): ?>
                            <tr><td colspan="4" class="text-center text-muted p-3">No hay sesiones activas</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="col-lg-6">
        <div class="card">
            <div class="card-header">
                <h3><i class="bi bi-activity text-warning"></i> Actividad sospechosa (24h)</h3>
            </div>
            <div class="card-body p-0" style="max-height:400px;overflow-y:auto">
                <div class="timeline" style="padding:1rem">
                    <?php foreach ($suspiciousActivity as $a): ?>
                        <div class="timeline-item">
                            <span class="time"><?= e($a['created_at']) ?></span>
                            <div class="content">
                                <strong><?= e($a['username'] ?? 'Sistema') ?></strong> ·
                                <span class="badge badge-<?= (str_contains($a['action'], 'failed') || str_contains($a['action'], 'blocked')) ? 'danger' : 'warning' ?>">
                                    <?= e($a['action']) ?>
                                </span>
                                <br><small class="text-muted"><?= e($a['description'] ?? '') ?></small>
                            </div>
                        </div>
                    <?php endforeach; ?>
                    <?php if (empty($suspiciousActivity)): ?>
                        <div class="text-center text-muted p-3">Sin actividad sospechosa</div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ═══════════════════════════════════════════════════════════════════
CHART.JS
═══════════════════════════════════════════════════════════════════ -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
Chart.defaults.color = '#8b949e';
Chart.defaults.borderColor = '#30363d';
Chart.defaults.font.family = "'Inter', sans-serif";

// ═══ Datos desde PHP (escapados correctamente) ═══
const hourlyData = <?= json_encode($hourly, JSON_UNESCAPED_UNICODE) ?>;
const dailyData = <?= json_encode($dailyAttempts, JSON_UNESCAPED_UNICODE) ?>;
const apiData = <?= json_encode($apiUsage, JSON_UNESCAPED_UNICODE) ?>;
const statsData = <?= json_encode($stats, JSON_UNESCAPED_UNICODE) ?>;

// ═══ Intentos por hora ═══
const hourlyLabels = Array.from({length: 24}, (_, i) => String(i).padStart(2, '0') + ':00');
const hourlySuccess = new Array(24).fill(0);
const hourlyFailed = new Array(24).fill(0);

hourlyData.forEach(h => {
    const hour = parseInt(h.hour, 10);
    if (hour >= 0 && hour < 24) {
        hourlySuccess[hour] = parseInt(h.success || 0, 10);
        hourlyFailed[hour] = parseInt(h.failed || 0, 10);
    }
});

new Chart(document.getElementById('hourlyChart'), {
    type: 'bar',
    data: {
        labels: hourlyLabels,
        datasets: [
            { label: 'Exitosos', data: hourlySuccess, backgroundColor: '#22c55e', stack: 'a' },
            { label: 'Fallidos', data: hourlyFailed, backgroundColor: '#ef4444', stack: 'a' }
        ]
    },
    options: {
        responsive: true, maintainAspectRatio: false,
        plugins: { legend: { position: 'top' } },
        scales: { 
            y: { beginAtZero: true, stacked: true }, 
            x: { stacked: true, grid: { display: false } } 
        }
    }
});

// ═══ Distribución ═══
new Chart(document.getElementById('distributionChart'), {
    type: 'doughnut',
    data: {
        labels: ['Exitosos', 'Fallidos'],
        datasets: [{
            data: [statsData.success_attempts || 0, statsData.failed_attempts || 0],
            backgroundColor: ['#22c55e', '#ef4444'],
            borderColor: '#1c2333', borderWidth: 3
        }]
    },
    options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'bottom' } } }
});

// ═══ Intentos por día ═══
new Chart(document.getElementById('dailyChart'), {
    type: 'line',
    data: {
        labels: dailyData.map(d => d.day),
        datasets: [
            { label: 'Total', data: dailyData.map(d => d.total), borderColor: '#00d4aa', backgroundColor: 'rgba(0,212,170,0.1)', fill: true, tension: 0.4 },
            { label: 'Fallidos', data: dailyData.map(d => d.failed), borderColor: '#ef4444', tension: 0.4 }
        ]
    },
    options: { responsive: true, maintainAspectRatio: false, scales: { y: { beginAtZero: true } } }
});

// ═══ Uso de API ═══
new Chart(document.getElementById('apiChart'), {
    type: 'line',
    data: {
        labels: apiData.map(d => d.day),
        datasets: [
            { label: 'Llamadas', data: apiData.map(d => d.calls), borderColor: '#3b82f6', backgroundColor: 'rgba(59,130,246,0.1)', fill: true, tension: 0.4 },
            { label: 'Errores', data: apiData.map(d => d.errors || 0), borderColor: '#ef4444', tension: 0.4 }
        ]
    },
    options: { responsive: true, maintainAspectRatio: false, scales: { y: { beginAtZero: true } } }
});

// ═══ Actualización en tiempo real cada 30s ═══
setInterval(async () => {
    try {
        const r = await fetch('<?= url("admin/security/dashboard/realtime") ?>', { 
            headers: {'X-Requested-With':'XMLHttpRequest'} 
        });
        const d = await r.json();
        if (d.success) {
            console.log('Seguridad actualizada:', d.data);
        }
    } catch(e) {
        console.warn('Error actualizando dashboard:', e);
    }
}, 30000);
</script>
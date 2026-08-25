<?php
/**
 * Vista: Dispositivos de Confianza
 * 
 * Variables esperadas desde TrustedDevicesController::index():
 *   $devices (array), $stats (array con total, active, current)
 */

// ═══════════════════════════════════════════════════════════════════
// VALORES POR DEFECTO (evita errores si el controlador falla)
// ═══════════════════════════════════════════════════════════════════
$stats = $stats ?? ['total' => 0, 'active' => 0, 'current' => 0];
$devices = $devices ?? [];

// Asegurar que las claves existan
$stats['total']   = (int)($stats['total']   ?? 0);
$stats['active']  = (int)($stats['active']  ?? 0);
$stats['current'] = (int)($stats['current'] ?? 0);

$pageTitle = 'Dispositivos de Confianza';
?>

<!-- ═══════════════════════════════════════════════════════════════
TARJETAS DE ESTADÍSTICAS
═══════════════════════════════════════════════════════════════ -->
<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="stat-card">
            <div class="stat-icon"><i class="bi bi-laptop"></i></div>
            <div class="stat-value"><?= $stats['total'] ?></div>
            <div class="stat-label">Dispositivos registrados</div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="stat-card">
            <div class="stat-icon" style="background:rgba(34,197,94,0.1);color:var(--success)">
                <i class="bi bi-check-circle"></i>
            </div>
            <div class="stat-value"><?= $stats['active'] ?></div>
            <div class="stat-label">Activos</div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="stat-card">
            <div class="stat-icon" style="background:rgba(59,130,246,0.1);color:var(--info)">
                <i class="bi bi-pc-display"></i>
            </div>
            <div class="stat-value"><?= $stats['current'] ?></div>
            <div class="stat-label">Este dispositivo</div>
        </div>
    </div>
</div>

<!-- ═══════════════════════════════════════════════════════════════
TABLA DE DISPOSITIVOS
═══════════════════════════════════════════════════════════════ -->
<div class="card">
    <div class="card-header">
        <h3><i class="bi bi-laptop"></i> Dispositivos de confianza</h3>
        <?php if ($stats['total'] > 0): ?>
            <form method="post" action="<?= url('admin/security/trusted-devices/revoke-all') ?>"
                  onsubmit="return confirm('¿Revocar TODOS los dispositivos? Tendrás que volver a verificar 2FA en cada uno.')">
                <?= \App\Core\CSRF::field() ?>
                <button class="btn btn-danger btn-sm">
                    <i class="bi bi-trash"></i> Revocar todos
                </button>
            </form>
        <?php endif; ?>
    </div>
    <div class="card-body p-0">
        <?php if (empty($devices)): ?>
            <div class="text-center text-muted p-5">
                <i class="bi bi-laptop" style="font-size:3rem"></i>
                <p class="mt-2">No tienes dispositivos de confianza registrados.</p>
                <p class="text-small">Cuando inicies sesión con 2FA, podrás marcar un dispositivo como de confianza para omitir el código en los próximos 30 días.</p>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Dispositivo</th>
                            <th>Navegador</th>
                            <th>IP</th>
                            <th>Último uso</th>
                            <th>Expira</th>
                            <th>Estado</th>
                            <th style="width:100px"></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($devices as $d): 
                            // Fallbacks para cada dispositivo
                            $d['id']            = (int)($d['id'] ?? 0);
                            $d['device_name']   = $d['device_name'] ?? 'Dispositivo desconocido';
                            $d['browser']       = $d['browser'] ?? 'Desconocido';
                            $d['platform']      = $d['platform'] ?? 'Desconocido';
                            $d['ip_address']    = $d['ip_address'] ?? '—';
                            $d['last_heartbeat']= $d['last_heartbeat'] ?? $d['last_used_at'] ?? '—';
                            $d['expires_at']    = $d['expires_at'] ?? date('Y-m-d H:i:s');
                            $d['is_current']    = (bool)($d['is_current'] ?? false);
                            $d['icon']          = $d['icon'] ?? 'globe';
                            
                            $isExpired = strtotime($d['expires_at']) < time();
                        ?>
                            <tr class="<?= $d['is_current'] ? 'table-active' : '' ?>">
                                <td>
                                    <div class="d-flex align-center gap-2">
                                        <i class="bi bi-<?= e($d['icon']) ?>" style="font-size:1.5rem;color:var(--accent)"></i>
                                        <div>
                                            <strong><?= e($d['device_name']) ?></strong>
                                            <?php if ($d['is_current']): ?>
                                                <span class="badge badge-success ms-2">Este dispositivo</span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </td>
                                <td><small><?= e($d['browser']) ?> / <?= e($d['platform']) ?></small></td>
                                <td><code class="text-small"><?= e($d['ip_address']) ?></code></td>
                                <td><small><?= e($d['last_heartbeat']) ?></small></td>
                                <td>
                                    <small><?= e(date('d/m/Y', strtotime($d['expires_at']))) ?></small>
                                    <?php if ($isExpired): ?>
                                        <span class="badge badge-archived">Expirado</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if (!$isExpired): ?>
                                        <span class="badge badge-success">Activo</span>
                                    <?php else: ?>
                                        <span class="badge badge-archived">Expirado</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if (!$d['is_current']): ?>
                                        <form method="post" action="<?= url("admin/security/trusted-devices/{$d['id']}/revoke") ?>"
                                              style="display:inline"
                                              onsubmit="return confirm('¿Revocar este dispositivo?')">
                                            <?= \App\Core\CSRF::field() ?>
                                            <button class="btn btn-ghost btn-sm text-danger" title="Revocar">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </form>
                                    <?php else: ?>
                                        <small class="text-muted">Actual</small>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- ═══════════════════════════════════════════════════════════════
SECCIÓN DE NOTIFICACIONES
═══════════════════════════════════════════════════════════════ -->
<div class="card mt-3">
    <div class="card-body">
        <h5><i class="bi bi-bell text-primary"></i> Notificaciones de seguridad</h5>
        <p class="text-small text-muted mb-2">
            Cada vez que se añade un nuevo dispositivo de confianza a tu cuenta, recibirás:
        </p>
        <ul class="text-small text-muted" style="padding-left:1.5rem;line-height:1.8">
            <li><i class="bi bi-bell-fill text-warning"></i> <strong>Notificación en el panel</strong> de forma inmediata</li>
            <li><i class="bi bi-envelope-fill text-info"></i> <strong>Email de alerta</strong> con los detalles del dispositivo</li>
        </ul>
        <div class="alert alert-info mt-2 mb-0" style="font-size:.85rem">
            <i class="bi bi-shield-check"></i>
            <strong>Estas notificaciones no se pueden desactivar</strong> porque son alertas críticas de seguridad.
            Si recibes una notificación que no reconoces, <strong>revoca el dispositivo inmediatamente</strong>
            y cambia tu contraseña.
        </div>
    </div>
</div>

<!-- ═══════════════════════════════════════════════════════════════
SECCIÓN INFORMATIVA
═══════════════════════════════════════════════════════════════ -->
<div class="card mt-3">
    <div class="card-body">
        <h5><i class="bi bi-info-circle text-primary"></i> ¿Qué son los dispositivos de confianza?</h5>
        <p class="text-small text-muted">
            Los dispositivos de confianza te permiten <strong>omitir el código 2FA</strong> durante 30 días en los dispositivos que usas regularmente.
            Esto es útil para tu computadora personal o teléfono, pero <strong>nunca marques dispositivos públicos o compartidos</strong>.
        </p>
        <ul class="text-small text-muted" style="padding-left:1.5rem;line-height:1.8">
            <li>Máximo <?= \App\Services\TrustedDeviceService::MAX_DEVICES_PER_USER ?> dispositivos por usuario</li>
            <li>Cada dispositivo expira automáticamente después de 30 días</li>
            <li>Puedes revocar cualquier dispositivo en cualquier momento</li>
            <li>Si pierdes un dispositivo, revócalo inmediatamente desde aquí</li>
        </ul>
    </div>
</div>
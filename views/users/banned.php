<?php
$pageTitle = 'Usuarios Baneados';
$fmtDate = fn($d) => $d ? date('d/m/Y H:i', strtotime($d)) : '—';

// Valores por defecto para evitar errores
$stats = $stats ?? ['total_banned' => 0, 'permanent' => 0, 'temporary' => 0, 'expired' => 0];
$bannedUsers = $bannedUsers ?? [];
$topReasons = $topReasons ?? [];
$filters = $filters ?? ['search' => ''];
$filter = $filter ?? 'all';
?>

<!-- Estadísticas -->
<div class="row g-3 mb-4">
    <div class="col-md-3 col-sm-6">
        <div class="stat-card">
            <div class="stat-icon" style="background:rgba(239,68,68,0.1);color:var(--danger)">
                <i class="bi bi-person-x"></i>
            </div>
            <div class="stat-value"><?= (int)$stats['total_banned'] ?></div>
            <div class="stat-label">Total baneados</div>
        </div>
    </div>
    <div class="col-md-3 col-sm-6">
        <div class="stat-card">
            <div class="stat-icon" style="background:rgba(239,68,68,0.15);color:var(--danger)">
                <i class="bi bi-infinity"></i>
            </div>
            <div class="stat-value"><?= (int)$stats['permanent'] ?></div>
            <div class="stat-label">Permanentes</div>
        </div>
    </div>
    <div class="col-md-3 col-sm-6">
        <div class="stat-card">
            <div class="stat-icon" style="background:rgba(245,158,11,0.1);color:var(--warning)">
                <i class="bi bi-clock-history"></i>
            </div>
            <div class="stat-value"><?= (int)$stats['temporary'] ?></div>
            <div class="stat-label">Temporales</div>
        </div>
    </div>
    <div class="col-md-3 col-sm-6">
        <div class="stat-card">
            <div class="stat-icon" style="background:rgba(139,148,158,0.1);color:var(--text-muted)">
                <i class="bi bi-hourglass-split"></i>
            </div>
            <div class="stat-value"><?= (int)$stats['expired'] ?></div>
            <div class="stat-label">Expirados</div>
        </div>
    </div>
</div>

<!-- Acciones rápidas -->
<div class="card mb-4">
    <div class="card-body">
        <div class="d-flex justify-between align-center flex-wrap gap-2">
            <div class="d-flex gap-2 flex-wrap">
                <a class="btn btn-ghost btn-sm" href="<?= url('admin/users') ?>">
                    <i class="bi bi-arrow-left"></i> Volver a usuarios
                </a>
                <?php if (can('users.view.ban_history') ?? false): ?>
                    <a class="btn btn-ghost btn-sm" href="<?= url('admin/users/banned/history') ?>">
                        <i class="bi bi-clock-history"></i> Historial global
                    </a>
                <?php endif; ?>
                <?php if (can('users.ban') ?? false): ?>
                    <button class="btn btn-primary btn-sm" onclick="openBanModal()">
                        <i class="bi bi-person-x"></i> Banear usuario
                    </button>
                <?php endif; ?>
            </div>

            <!-- Filtros -->
            <form method="get" action="<?= url('admin/users/banned') ?>" class="d-flex gap-2">
                <input type="text" name="search" class="form-control form-control-sm"
                       placeholder="Buscar..." value="<?= e($filters['search'] ?? '') ?>" style="width:200px">
                <select name="filter" class="form-select form-select-sm" style="width:auto">
                    <option value="all" <?= $filter==='all'?'selected':'' ?>>Todos</option>
                    <option value="permanent" <?= $filter==='permanent'?'selected':'' ?>>Permanentes</option>
                    <option value="temporary" <?= $filter==='temporary'?'selected':'' ?>>Temporales</option>
                    <option value="expired" <?= $filter==='expired'?'selected':'' ?>>Expirados</option>
                </select>
                <button class="btn btn-ghost btn-sm"><i class="bi bi-search"></i></button>
            </form>
        </div>
    </div>
</div>

<!-- Lista de usuarios baneados -->
<div class="card">
    <div class="card-header">
        <h3><i class="bi bi-person-x"></i> Usuarios baneados</h3>
        <span class="badge badge-danger"><?= count($bannedUsers) ?> usuarios</span>
    </div>
    <div class="card-body p-0">
        <?php if (empty($bannedUsers)): ?>
            <div class="text-center text-muted p-5">
                <i class="bi bi-emoji-smile" style="font-size:3rem"></i>
                <p class="mt-2">No hay usuarios baneados<?= !empty($filters['search']) ? ' con esos criterios' : '' ?>.</p>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Usuario</th>
                            <th>Email</th>
                            <th>Rol</th>
                            <th>Motivo</th>
                            <th>Baneado por</th>
                            <th>Fecha</th>
                            <th>Expira</th>
                            <th>Estado</th>
                            <th style="width:150px">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($bannedUsers as $u): 
                            // Fallbacks para cada campo
                            $uId = (int)($u['id'] ?? 0);
                            $username = $u['username'] ?? 'Desconocido';
                            $email = $u['email'] ?? '';
                            $role = $u['role'] ?? 'Guest';
                            $bannedReason = $u['banned_reason'] ?? 'Sin motivo';
                            $bannedByName = $u['banned_by_name'] ?? 'Sistema';
                            $bannedAt = $u['banned_at'] ?? null;
                            $banExpiresAt = $u['ban_expires_at'] ?? null;
                            $banStatus = $u['ban_status'] ?? 'active';
                            $initial = mb_substr($u['full_name'] ?? $username, 0, 1);
                            
                            $isExpired = $banExpiresAt && strtotime($banExpiresAt) < time();
                            if ($isExpired) $banStatus = 'expired';
                        ?>
                            <tr>
                                <td>
                                    <div class="d-flex align-center gap-2">
                                        <div class="avatar" style="width:32px;height:32px;border-radius:50%;background:rgba(239,68,68,0.15);color:var(--danger);display:grid;place-items:center;font-weight:600;font-size:.8rem">
                                            <?= e(strtoupper($initial)) ?>
                                        </div>
                                        <div>
                                            <strong><?= e($username) ?></strong>
                                            <?php if (!empty($u['full_name'])): ?>
                                                <br><small class="text-muted"><?= e($u['full_name']) ?></small>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </td>
                                <td><small><?= e($email) ?></small></td>
                                <td><span class="badge badge-<?= strtolower(str_replace(' ','-',$role)) ?>"><?= e($role) ?></span></td>
                                <td>
                                    <span title="<?= e($bannedReason) ?>">
                                        <?= e(mb_substr($bannedReason, 0, 40)) ?><?= mb_strlen($bannedReason) > 40 ? '...' : '' ?>
                                    </span>
                                </td>
                                <td><small><?= e($bannedByName) ?></small></td>
                                <td><small><?= e($fmtDate($bannedAt)) ?></small></td>
                                <td>
                                    <?php if ($banExpiresAt): ?>
                                        <small><?= e($fmtDate($banExpiresAt)) ?></small>
                                    <?php else: ?>
                                        <span class="badge badge-danger">∞ Permanente</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php
                                    $statusBadge = match($banStatus) {
                                        'permanent' => '<span class="badge badge-danger"><i class="bi bi-infinity"></i> Permanente</span>',
                                        'active'    => '<span class="badge badge-warning"><i class="bi bi-clock"></i> Activo</span>',
                                        'expired'   => '<span class="badge badge-archived"><i class="bi bi-hourglass-split"></i> Expirado</span>',
                                        default     => '<span class="badge badge-archived">—</span>',
                                    };
                                    ?>
                                    <?= $statusBadge ?>
                                </td>
                                <td>
                                    <div class="d-flex gap-1">
                                        <?php if (can('users.view.ban_history') ?? false): ?>
                                            <a class="btn btn-ghost btn-sm" 
                                               href="<?= url("admin/users/banned/{$uId}/history") ?>" 
                                               title="Historial">
                                                <i class="bi bi-clock-history"></i>
                                            </a>
                                        <?php endif; ?>
                                        
                                        <?php if (can('users.ban') ?? false): ?>
                                            <!-- ═══ BOTÓN DE DESBANEAR — AHORA CON URL DIRECTA ═══ -->
                                            <form method="post" 
                                                  action="<?= url("admin/users/banned/{$uId}/unban") ?>" 
                                                  style="display:inline"
                                                  onsubmit="return confirm('¿Confirmas desbanear a <?= e($username) ?>?')">
                                                <?= \App\Core\CSRF::field() ?>
                                                <input type="hidden" name="reactivate" value="1">
                                                <button type="submit" class="btn btn-ghost btn-sm text-success" title="Desbanear">
                                                    <i class="bi bi-person-check"></i>
                                                </button>
                                            </form>
                                            
                                            <?php if ($banExpiresAt && !$isExpired): ?>
                                                <!-- ═══ BOTÓN DE EXTENDER — URL DIRECTA ═══ -->
                                                <button type="button" class="btn btn-ghost btn-sm text-warning"
                                                        onclick="openExtendModal(<?= $uId ?>, '<?= e(addslashes($username)) ?>')" 
                                                        title="Extender">
                                                    <i class="bi bi-clock"></i>
                                                </button>
                                            <?php endif; ?>
                                        <?php endif; ?>
                                    </div>
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
MODAL: Extender baneo (el único que necesita JS dinámico)
═══════════════════════════════════════════════════════════════ -->
<div class="modal" id="extendModal">
    <div class="modal-overlay" onclick="this.parentElement.classList.remove('active')"></div>
    <div class="modal-content">
        <!-- ═══ FORM CON ACTION POR DEFECTO (fallback) ═══ -->
        <form method="post" id="extendForm" action="<?= url('admin/users/banned/0/extend') ?>">
            <?= \App\Core\CSRF::field() ?>
            <div class="modal-header">
                <h3><i class="bi bi-clock text-warning"></i> Extender baneo</h3>
                <button type="button" class="modal-close" onclick="this.closest('.modal').classList.remove('active')">×</button>
            </div>
            <div class="modal-body">
                <p>Extender el baneo de <strong id="extendUsername"></strong></p>
                <div class="form-group">
                    <label class="form-label">Días adicionales</label>
                    <input type="number" name="days" class="form-control" value="7" min="1" max="3650" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Notas (opcional)</label>
                    <textarea name="notes" class="form-control" rows="2"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-ghost" onclick="this.closest('.modal').classList.remove('active')">Cancelar</button>
                <button type="submit" class="btn btn-warning"><i class="bi bi-clock"></i> Extender</button>
            </div>
        </form>
    </div>
</div>

<!-- ═══════════════════════════════════════════════════════════════
MODAL: Banear usuario (simplificado)
═══════════════════════════════════════════════════════════════ -->
<?php if (can('users.ban') ?? false): ?>
<div class="modal" id="banUserModal">
    <div class="modal-overlay" onclick="this.parentElement.classList.remove('active')"></div>
    <div class="modal-content modal-lg">
        <form method="post" action="<?= url('admin/users/banned/ban') ?>">
            <?= \App\Core\CSRF::field() ?>
            <div class="modal-header">
                <h3><i class="bi bi-person-x text-danger"></i> Banear usuario</h3>
                <button type="button" class="modal-close" onclick="this.closest('.modal').classList.remove('active')">×</button>
            </div>
            <div class="modal-body">
                <div class="form-group">
                    <label class="form-label">Usuario a banear <span class="text-danger">*</span></label>
                    <input type="text" id="userSearch" class="form-control" 
                           placeholder="Buscar por nombre, email o username..." autocomplete="off">
                    <input type="hidden" name="user_id" id="selectedUserId" required>
                    <div id="userSearchResults" class="mt-2" style="max-height:200px;overflow-y:auto"></div>
                    <div id="selectedUserInfo" class="alert alert-info mt-2" style="display:none"></div>
                </div>
                <div class="form-group">
                    <label class="form-label">Motivo del baneo <span class="text-danger">*</span></label>
                    <textarea name="reason" class="form-control" rows="2" required 
                              placeholder="Ej: Violación de los términos de servicio"></textarea>
                </div>
                <div class="form-group">
                    <label class="form-label">Duración</label>
                    <div class="d-flex gap-2 flex-wrap">
                        <label class="form-check"><input type="radio" name="duration" value="1" class="form-check-input"> 1 día</label>
                        <label class="form-check"><input type="radio" name="duration" value="7" class="form-check-input" checked> 7 días</label>
                        <label class="form-check"><input type="radio" name="duration" value="30" class="form-check-input"> 30 días</label>
                        <label class="form-check"><input type="radio" name="duration" value="90" class="form-check-input"> 90 días</label>
                        <label class="form-check"><input type="radio" name="duration" value="365" class="form-check-input"> 1 año</label>
                        <?php if (can('users.ban.permanent') ?? false): ?>
                            <label class="form-check">
                                <input type="radio" name="duration" value="permanent" class="form-check-input">
                                <span class="text-danger">∞ Permanente</span>
                            </label>
                        <?php endif; ?>
                    </div>
                </div>
                <label class="form-check">
                    <input type="checkbox" name="notify_email" value="1" class="form-check-input">
                    <span class="form-check-label">Notificar al usuario por email</span>
                </label>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-ghost" onclick="this.closest('.modal').classList.remove('active')">Cancelar</button>
                <button type="submit" class="btn btn-danger"><i class="bi bi-person-x"></i> Banear usuario</button>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>

<script>
// ═══ Abrir modal de banear ═══
function openBanModal() {
    document.getElementById('banUserModal').classList.add('active');
}

// ═══ Extender baneo ═══
function openExtendModal(userId, username) {
    document.getElementById('extendUsername').textContent = username;
    // ✅ IMPORTANTE: La URL se construye correctamente con el ID
    document.getElementById('extendForm').action = '<?= url("admin/users/banned") ?>/' + userId + '/extend';
    document.getElementById('extendModal').classList.add('active');
}

// ═══ Búsqueda de usuarios (para banear) ═══
let searchTimeout;
const userSearch = document.getElementById('userSearch');
if (userSearch) {
    userSearch.addEventListener('input', (e) => {
        clearTimeout(searchTimeout);
        const q = e.target.value.trim();
        const container = document.getElementById('userSearchResults');
        
        if (q.length < 2) {
            container.innerHTML = '';
            return;
        }
        
        searchTimeout = setTimeout(async () => {
            try {
                const r = await fetch(`<?= url('admin/users/banned/search') ?>?q=${encodeURIComponent(q)}`, {
                    headers: {'X-Requested-With':'XMLHttpRequest'}
                });
                const d = await r.json();
                
                if (!d.success || !d.data.length) {
                    container.innerHTML = '<div class="text-muted text-small p-2">Sin resultados</div>';
                    return;
                }
                
                container.innerHTML = d.data.map(u => `
                    <div class="p-2 border-bottom" style="cursor:pointer;background:var(--bg-card-2);border-radius:4px;margin-bottom:2px"
                         onclick="selectUser(${u.id}, '${u.username.replace(/'/g,"\\'")}', '${(u.email||'').replace(/'/g,"\\'")}')">
                        <strong>${u.username}</strong> <small class="text-muted">(${u.email||''})</small>
                        <span class="badge badge-${(u.role||'').toLowerCase().replace(/ /g,'-')} ms-2">${u.role||''}</span>
                    </div>
                `).join('');
            } catch(err) {
                console.error('Error buscando usuario:', err);
            }
        }, 300);
    });
}

function selectUser(id, username, email) {
    document.getElementById('selectedUserId').value = id;
    document.getElementById('userSearchResults').innerHTML = '';
    document.getElementById('userSearch').value = '';
    const info = document.getElementById('selectedUserInfo');
    info.innerHTML = `<i class="bi bi-person-check"></i> <strong>${username}</strong> (${email})`;
    info.style.display = 'block';
}

function setReason(reason) {
    const reasonField = document.querySelector('[name=reason]');
    if (reasonField) reasonField.value = reason;
}
</script>
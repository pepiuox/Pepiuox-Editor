<?php
/**
 * Vista: Editar Usuario
 * Variables esperadas: $user, $roles
 */

// Fallbacks seguros
$user = $user ?? [];
$roles = $roles ?? [];
$u = $user;

// Valores por defecto para evitar errores
$uId = (int)($u['id'] ?? 0);
$username = $u['username'] ?? '';
$email = $u['email'] ?? '';
$fullName = $u['full_name'] ?? '';
$role = $u['role'] ?? 'Guest';
$isActive = (int)($u['is_active'] ?? 1);
$createdAt = $u['created_at'] ?? '—';
$lastLogin = $u['last_login'] ?? 'Nunca';
$loginCount = (int)($u['login_count'] ?? 0);
$lastIp = $u['last_ip'] ?? '—';
$twoFactorEnabled = (int)($u['two_factor_enabled'] ?? 0);
$initial = mb_substr($fullName ?: $username, 0, 1);

$pageTitle = 'Editar Usuario: ' . $username;
?>

<div class="d-flex justify-between align-center mb-3 flex-wrap gap-2">
    <h2 class="mb-0">
        <i class="bi bi-person-gear text-primary"></i> Editar usuario:
        <span class="text-primary"><?= e($username) ?></span>
    </h2>
    <a class="btn btn-ghost" href="<?= url('admin/users') ?>">
        <i class="bi bi-arrow-left"></i> Volver al listado
    </a>
</div>

<div class="row g-4">
    <div class="col-lg-8">
        <!-- Información básica -->
        <div class="card">
            <div class="card-header">
                <h3><i class="bi bi-person"></i> Información básica</h3>
            </div>
            <div class="card-body">
                <form method="post" action="<?= url("admin/users/{$uId}") ?>" id="userForm">
                    <?= \App\Core\CSRF::field() ?>
                    
                    <div class="form-grid">
                        <div class="form-group">
                            <label class="form-label">
                                <i class="bi bi-at"></i> Nombre de usuario
                            </label>
                            <input type="text" name="username" class="form-control"
                                   value="<?= e($username) ?>" required
                                   minlength="3" maxlength="50" pattern="[a-zA-Z0-9_\-\.]+">
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">
                                <i class="bi bi-envelope"></i> Email
                            </label>
                            <input type="email" name="email" class="form-control"
                                   value="<?= e($email) ?>" required maxlength="100">
                        </div>
                        
                        <div class="form-group full-width">
                            <label class="form-label">
                                <i class="bi bi-person-badge"></i> Nombre completo
                            </label>
                            <input type="text" name="full_name" class="form-control"
                                   value="<?= e($fullName) ?>" maxlength="100">
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">
                                <i class="bi bi-shield-lock"></i> Rol
                            </label>
                            <select name="role" class="form-select" required>
                                <?php foreach ($roles as $r): ?>
                                    <option value="<?= e($r['name']) ?>"
                                        <?= $role === $r['name'] ? 'selected' : '' ?>>
                                        <?= e($r['name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">
                                <i class="bi bi-toggle-on"></i> Estado
                            </label>
                            <select name="is_active" class="form-select">
                                <option value="1" <?= $isActive ? 'selected' : '' ?>>✓ Activo</option>
                                <option value="0" <?= !$isActive ? 'selected' : '' ?>>✗ Inactivo</option>
                            </select>
                        </div>
                    </div>
                    
                    <hr class="my-4">
                    
                    <div class="d-flex justify-end gap-2">
                        <a class="btn btn-ghost" href="<?= url('admin/users') ?>">Cancelar</a>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check-circle"></i> Guardar cambios
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Cambiar contraseña -->
        <div class="card">
            <div class="card-header">
                <h3><i class="bi bi-key"></i> Cambiar contraseña</h3>
            </div>
            <div class="card-body">
                <form method="post" action="<?= url("admin/users/{$uId}/password") ?>" id="passwordForm">
                    <?= \App\Core\CSRF::field() ?>
                    
                    <div class="alert alert-warning">
                        <i class="bi bi-exclamation-triangle"></i>
                        <strong>Precaución:</strong> Al cambiar la contraseña, el usuario tendrá que iniciar sesión nuevamente.
                    </div>
                    
                    <div class="form-grid">
                        <div class="form-group full-width">
                            <label class="form-label">Nueva contraseña</label>
                            <div class="input-group">
                                <input type="password" name="password" class="form-control"
                                       id="newPassword" minlength="8"
                                       pattern="(?=.*[a-z])(?=.*[A-Z])(?=.*\d).{8,}">
                                <button type="button" class="btn btn-ghost" onclick="togglePassword('newPassword')">
                                    <i class="bi bi-eye" id="newPasswordIcon"></i>
                                </button>
                                <button type="button" class="btn btn-ghost" onclick="generatePassword()">
                                    <i class="bi bi-shuffle"></i> Generar
                                </button>
                            </div>
                            <div class="progress mt-2">
                                <div class="progress-bar" id="newPasswordStrength" style="width:0%"></div>
                            </div>
                        </div>
                        
                        <div class="form-group full-width">
                            <label class="form-label">Confirmar nueva contraseña</label>
                            <input type="password" name="password_confirmation" class="form-control"
                                   id="newPasswordConfirm" minlength="8">
                            <small class="form-text text-danger" id="newPasswordMatchError" style="display:none">
                                Las contraseñas no coinciden
                            </small>
                        </div>
                    </div>
                    
                    <div class="d-flex justify-end">
                        <button type="submit" class="btn btn-warning">
                            <i class="bi bi-key-fill"></i> Cambiar contraseña
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Panel lateral -->
    <div class="col-lg-4">
        <div class="card">
            <div class="card-body text-center">
                <div class="avatar mx-auto mb-3" style="width:80px;height:80px;font-size:2rem">
                    <?= e(strtoupper($initial)) ?>
                </div>
                <h4 class="mb-1"><?= e($fullName ?: $username) ?></h4>
                <p class="text-muted text-small mb-2">@<?= e($username) ?></p>
                <span class="badge badge-<?= strtolower(str_replace(' ', '-', $role)) ?>">
                    <?= e($role) ?>
                </span>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h3><i class="bi bi-info-circle"></i> Información</h3>
            </div>
            <div class="card-body">
                <div class="d-flex justify-between mb-2">
                    <span class="text-muted text-small">ID</span>
                    <strong>#<?= $uId ?></strong>
                </div>
                <div class="d-flex justify-between mb-2">
                    <span class="text-muted text-small">Creado</span>
                    <strong class="text-small"><?= e($createdAt) ?></strong>
                </div>
                <div class="d-flex justify-between mb-2">
                    <span class="text-muted text-small">Último login</span>
                    <strong class="text-small"><?= e($lastLogin) ?></strong>
                </div>
                <div class="d-flex justify-between mb-2">
                    <span class="text-muted text-small">Total logins</span>
                    <strong><?= $loginCount ?></strong>
                </div>
                <div class="d-flex justify-between mb-2">
                    <span class="text-muted text-small">Última IP</span>
                    <code class="text-small"><?= e($lastIp) ?></code>
                </div>
                <div class="d-flex justify-between mb-2">
                    <span class="text-muted text-small">2FA</span>
                    <strong>
                        <?php if ($twoFactorEnabled): ?>
                            <span class="text-success"><i class="bi bi-shield-check"></i> Activo</span>
                        <?php else: ?>
                            <span class="text-muted"><i class="bi bi-shield-x"></i> Inactivo</span>
                        <?php endif; ?>
                    </strong>
                </div>
            </div>
        </div>

        <!-- Zona peligrosa -->
        <?php 
        $currentUserId = \App\Core\Auth::id();
        $canDelete = \App\Core\Auth::can('users.delete');
        if ($canDelete && $uId !== $currentUserId): 
        ?>
            <div class="card card-danger">
                <div class="card-header" style="background:rgba(239,68,68,0.08)">
                    <h3 style="color:var(--danger)"><i class="bi bi-exclamation-triangle"></i> Zona peligrosa</h3>
                </div>
                <div class="card-body">
                    <p class="text-small text-muted">
                        Eliminar un usuario es permanente y no se puede deshacer. Todas sus páginas, archivos y sesiones serán eliminados.
                    </p>
                    <form method="post" action="<?= url("admin/users/{$uId}/delete") ?>"
                          onsubmit="return confirm('¿Estás SEGURO de eliminar este usuario? Esta acción NO se puede deshacer.')">
                        <?= \App\Core\CSRF::field() ?>
                        <button type="submit" class="btn btn-danger btn-block">
                            <i class="bi bi-trash"></i> Eliminar usuario
                        </button>
                    </form>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
function togglePassword(inputId) {
    const input = document.getElementById(inputId);
    const icon = document.getElementById(inputId + 'Icon');
    if (input.type === 'password') {
        input.type = 'text';
        icon.className = 'bi bi-eye-slash';
    } else {
        input.type = 'password';
        icon.className = 'bi bi-eye';
    }
}

function generatePassword() {
    const chars = 'ABCDEFGHJKLMNPQRSTUVWXYZabcdefghjkmnpqrstuvwxyz23456789!@#$%';
    let password = '';
    password += 'ABCDEFGHJKLMNPQRSTUVWXYZ'[Math.floor(Math.random() * 24)];
    password += 'abcdefghjkmnpqrstuvwxyz'[Math.floor(Math.random() * 23)];
    password += '23456789'[Math.floor(Math.random() * 8)];
    for (let i = 0; i < 9; i++) {
        password += chars[Math.floor(Math.random() * chars.length)];
    }
    password = password.split('').sort(() => Math.random() - 0.5).join('');
    
    document.getElementById('newPassword').value = password;
    document.getElementById('newPasswordConfirm').value = password;
    checkNewPasswordStrength(password);
}

function checkNewPasswordStrength(password) {
    const bar = document.getElementById('newPasswordStrength');
    let score = 0;
    if (password.length >= 8) score++;
    if (password.length >= 12) score++;
    if (/[a-z]/.test(password) && /[A-Z]/.test(password)) score++;
    if (/\d/.test(password)) score++;
    if (/[^a-zA-Z0-9]/.test(password)) score++;
    
    const colors = ['var(--border)', 'var(--danger)', 'var(--warning)', 'var(--warning)', 'var(--success)', 'var(--success)'];
    bar.style.width = (score * 20) + '%';
    bar.style.background = colors[score];
}

document.getElementById('newPassword').addEventListener('input', function() {
    checkNewPasswordStrength(this.value);
    checkNewPasswordMatch();
});

function checkNewPasswordMatch() {
    const pass = document.getElementById('newPassword').value;
    const confirm = document.getElementById('newPasswordConfirm').value;
    const error = document.getElementById('newPasswordMatchError');
    error.style.display = (confirm && pass !== confirm) ? 'block' : 'none';
}

document.getElementById('newPasswordConfirm').addEventListener('input', checkNewPasswordMatch);

document.getElementById('passwordForm').addEventListener('submit', function(e) {
    const pass = document.getElementById('newPassword').value;
    const confirm = document.getElementById('newPasswordConfirm').value;
    if (pass && pass !== confirm) {
        e.preventDefault();
        alert('Las contraseñas no coinciden');
    }
});
</script>
<?php
/**
 * Vista: Perfil de Usuario
 * Variables esperadas: $user, $languages
 */

// ═══════════════════════════════════════════════════════════════════
// FALLBACKS SEGUROS (evita errores si el controlador falla)
// ═══════════════════════════════════════════════════════════════════
$user = $user ?? [];
$languages = $languages ?? [];

// Valores por defecto para columnas que pueden no existir
$userId       = (int)($user['id'] ?? 0);
$username     = $user['username'] ?? 'Usuario';
$email        = $user['email'] ?? '';
$fullName     = $user['full_name'] ?? '';
$bio          = $user['bio'] ?? '';
$avatar       = $user['avatar'] ?? null;
$locale       = $user['locale'] ?? 'es';
$theme        = $user['theme'] ?? 'dark';
$timezone     = $user['timezone'] ?? 'UTC';
$role         = $user['role'] ?? 'Guest';
$createdAt    = $user['created_at'] ?? '—';
$lastLogin    = $user['last_login'] ?? '—';
$lastIp       = $user['last_ip'] ?? '—';
$twoFactor    = (int)($user['two_factor_enabled'] ?? 0);
$initial      = mb_substr($fullName ?: $username, 0, 1);

// URL del avatar
$avatarUrl = $avatar ? url('uploads/' . $avatar) : '';

// Timezones (fallback si falla DateTimeZone)
try {
    $timezones = DateTimeZone::listIdentifiers();
} catch (\Throwable $e) {
    $timezones = ['UTC', 'Europe/Madrid', 'America/Mexico_City', 'America/Bogota'];
}

// Helper de traducción seguro
if (!function_exists('t')) {
    function t(string $key, array $params = []): string {
        return $key; // Fallback: devuelve la clave si no hay traducción
    }
}

$pageTitle = t('profile');
?>

<div class="row g-4">
    <!-- ═══════════════════════════════════════════════════════════════
    TARJETA DE PERFIL
    ═══════════════════════════════════════════════════════════════ -->
    <div class="col-md-4">
        <div class="card">
            <div class="card-body text-center">
                <div class="avatar-large mx-auto mb-3" style="width:120px;height:120px;border-radius:50%;background:linear-gradient(135deg,var(--accent),var(--accent-dark));display:grid;place-items:center;font-size:3rem;color:var(--bg-principal);font-weight:700;overflow:hidden">
                    <?php if ($avatarUrl && is_file(ROOT_PATH . '/public/uploads/' . $avatar)): ?>
                        <img src="<?= e($avatarUrl) ?>" alt="" style="width:100%;height:100%;object-fit:cover">
                    <?php else: ?>
                        <?= e(strtoupper($initial)) ?>
                    <?php endif; ?>
                </div>
                <h3><?= e($fullName ?: $username) ?></h3>
                <p class="text-muted">@<?= e($username) ?></p>
                <span class="badge badge-admin"><?= e($role) ?></span>
                <?php if ($twoFactor): ?>
                    <span class="badge badge-success mt-1"><i class="bi bi-shield-check"></i> 2FA</span>
                <?php endif; ?>
                <hr>
                <div class="text-start text-small">
                    <div class="mb-2"><i class="bi bi-envelope text-primary"></i> <?= e($email) ?></div>
                    <div class="mb-2"><i class="bi bi-calendar text-primary"></i> <?= t('created_at') ?>: <?= e($createdAt) ?></div>
                    <div class="mb-2"><i class="bi bi-clock text-primary"></i> <?= t('last_login') ?>: <?= e($lastLogin) ?></div>
                    <div><i class="bi bi-globe text-primary"></i> <?= e($lastIp) ?></div>
                </div>
            </div>
        </div>
    </div>

    <!-- ═══════════════════════════════════════════════════════════════
    FORMULARIOS
    ═══════════════════════════════════════════════════════════════ -->
    <div class="col-md-8">
        <!-- Datos personales -->
        <div class="card">
            <div class="card-header"><h3><i class="bi bi-person"></i> <?= t('personal_data') ?></h3></div>
            <div class="card-body">
                <form method="post" action="<?= url('admin/profile') ?>" enctype="multipart/form-data">
                    <!-- ✅ CSRF con namespace completo -->
                    <?= \App\Core\CSRF::field() ?>
                    
                    <div class="form-grid">
                        <div class="form-group">
                            <label class="form-label"><?= t('profile.full_name') ?></label>
                            <input type="text" name="full_name" class="form-control" value="<?= e($fullName) ?>">
                        </div>
                        <div class="form-group">
                            <label class="form-label"><?= t('auth.email') ?></label>
                            <input type="email" class="form-control" value="<?= e($email) ?>" disabled>
                            <small class="form-text"><?= t('email_cannot_be_changed') ?></small>
                        </div>
                        <div class="form-group full-width">
                            <label class="form-label"><?= t('profile.bio') ?></label>
                            <textarea name="bio" class="form-control" rows="3"><?= e($bio) ?></textarea>
                        </div>
                        <div class="form-group">
                            <label class="form-label"><?= t('profile.avatar') ?></label>
                            <input type="file" name="avatar" class="form-control" accept="image/*">
                            <small class="form-text">JPG, PNG, GIF, WebP. Máx 2MB.</small>
                        </div>
                    </div>

                    <hr>
                    <h5><i class="bi bi-gear"></i> <?= t('profile.preferences') ?></h5>
                    <div class="form-grid">
                        <div class="form-group">
                            <label class="form-label"><?= t('profile.language') ?></label>
                            <select name="locale" class="form-select">
                                <?php if (empty($languages)): ?>
                                    <option value="es" <?= $locale === 'es' ? 'selected' : '' ?>>🇪🇸 Español</option>
                                    <option value="en" <?= $locale === 'en' ? 'selected' : '' ?>>🇬🇧 English</option>
                                <?php else: ?>
                                    <?php foreach ($languages as $l): ?>
                                        <option value="<?= e($l['code']) ?>" <?= $locale === $l['code'] ? 'selected' : '' ?>>
                                            <?= e($l['flag'] ?? '🌐') ?> <?= e($l['native_name'] ?? $l['name'] ?? $l['code']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label"><?= t('profile.theme') ?></label>
                            <select name="theme" class="form-select">
                                <option value="dark" <?= $theme === 'dark' ? 'selected' : '' ?>>🌙 Dark</option>
                                <option value="light" <?= $theme === 'light' ? 'selected' : '' ?>>☀️ Light</option>
                                <option value="auto" <?= $theme === 'auto' ? 'selected' : '' ?>>⚙️ Auto</option>
                            </select>
                        </div>
                        <div class="form-group full-width">
                            <label class="form-label"><?= t('profile.timezone') ?></label>
                            <select name="timezone" class="form-select">
                                <?php foreach ($timezones as $tz): ?>
                                    <option value="<?= e($tz) ?>" <?= $timezone === $tz ? 'selected' : '' ?>><?= e($tz) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <button class="btn btn-primary"><i class="bi bi-check"></i> <?= t('save') ?></button>
                </form>
            </div>
        </div>

        <!-- Cambiar contraseña -->
        <div class="card">
            <div class="card-header"><h3><i class="bi bi-key"></i> <?= t('profile.change_password') ?></h3></div>
            <div class="card-body">
                <form method="post" action="<?= url('admin/profile/password') ?>">
                    <!-- ✅ CSRF con namespace completo -->
                    <?= \App\Core\CSRF::field() ?>
                    
                    <div class="form-grid">
                        <div class="form-group full-width">
                            <label class="form-label"><?= t('profile.current_password') ?></label>
                            <input type="password" name="current_password" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label"><?= t('profile.new_password') ?></label>
                            <input type="password" name="new_password" class="form-control" minlength="8" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label"><?= t('profile.confirm_password') ?></label>
                            <input type="password" name="confirm_password" class="form-control" minlength="8" required>
                        </div>
                    </div>

                    <button class="btn btn-warning"><i class="bi bi-key-fill"></i> <?= t('profile.change_password') ?></button>
                </form>
            </div>
        </div>

        <!-- Sesiones activas -->
        <div class="card">
            <div class="card-header"><h3><i class="bi bi-shield-lock"></i> <?= t('security') ?></h3></div>
            <div class="card-body">
                <div class="d-flex justify-between align-center mb-3">
                    <div>
                        <strong><?= t('two_factor_auth') ?></strong>
                        <p class="text-muted text-small mb-0"><?= t('2fa_description') ?></p>
                    </div>
                    <a href="<?= url('admin/2fa') ?>" class="btn btn-ghost btn-sm">
                        <?= $twoFactor 
                            ? '<i class="bi bi-shield-check text-success"></i> ' . t('enabled') 
                            : '<i class="bi bi-shield-x"></i> ' . t('enable') ?>
                    </a>
                </div>
                <hr>
                <div class="d-flex justify-between align-center">
                    <div>
                        <strong><?= t('active_sessions') ?></strong>
                        <p class="text-muted text-small mb-0"><?= t('sessions_description') ?></p>
                    </div>
                    <form method="post" action="<?= url('admin/profile/sessions/close') ?>" 
                          onsubmit="return confirm('<?= t('close_all_sessions_confirm') ?>')">
                        <!-- ✅ CSRF con namespace completo -->
                        <?= \App\Core\CSRF::field() ?>
                        <button type="submit" class="btn btn-danger btn-sm">
                            <i class="bi bi-x-circle"></i> <?= t('close_all') ?>
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
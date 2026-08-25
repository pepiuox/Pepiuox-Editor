<?php 
$pageTitle = 'Configuración del Sistema';
$s = $settings ?? [];
$general = $s['general'] ?? [];
$seo = $s['seo'] ?? [];
$cache = $s['cache'] ?? [];
$mail = $s['mail'] ?? [];
?>

<div class="card">
  <div class="card-header">
    <h3><i class="bi bi-gear"></i> Configuración del Sistema</h3>
  </div>
  <div class="card-body">
    <form method="post" action="<?= url('admin/settings/update') ?>" id="settingsForm">
      <?= \App\Core\CSRF::field() ?>

      <!-- Pestañas de Navegación -->
      <ul class="nav-tabs mb-4">
        <li><button type="button" class="nav-link active" data-tab="general"><i class="bi bi-info-circle"></i> General</button></li>
        <li><button type="button" class="nav-link" data-tab="seo"><i class="bi bi-search"></i> SEO</button></li>
        <li><button type="button" class="nav-link" data-tab="cache"><i class="bi bi-hdd"></i> Cache</button></li>
        <li><button type="button" class="nav-link" data-tab="mail"><i class="bi bi-envelope"></i> Correo</button></li>
      </ul>

      <!-- 1. GENERAL -->
      <div class="tab-content active" data-tab="general">
        <div class="form-grid">
          <div class="form-group">
            <label class="form-label">Nombre del Sitio</label>
            <input type="text" name="settings[general][site_name]" class="form-control" value="<?= e($general['site_name'] ?? '') ?>">
          </div>
          <div class="form-group">
            <label class="form-label">Descripción Corta</label>
            <input type="text" name="settings[general][site_description]" class="form-control" value="<?= e($general['site_description'] ?? '') ?>">
          </div>
          <div class="form-group">
            <label class="form-label">Idioma por defecto</label>
            <select name="settings[general][site_locale]" class="form-select">
              <option value="es" <?= ($general['site_locale'] ?? 'es') === 'es' ? 'selected' : '' ?>>🇪🇸 Español</option>
              <option value="en" <?= ($general['site_locale'] ?? '') === 'en' ? 'selected' : '' ?>>🇬🇧 English</option>
            </select>
          </div>
          <div class="form-group">
            <label class="form-label">Zona Horaria</label>
            <select name="settings[general][site_timezone]" class="form-select">
              <?php foreach (timezone_identifiers_list() as $tz): ?>
                <option value="<?= e($tz) ?>" <?= ($general['site_timezone'] ?? 'Europe/Madrid') === $tz ? 'selected' : '' ?>><?= e($tz) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>
      </div>

      <!-- 2. SEO -->
      <div class="tab-content" data-tab="seo">
        <div class="form-grid">
          <div class="form-group full-width">
            <label class="form-label">Meta Title por defecto</label>
            <input type="text" name="settings[seo][default_meta_title]" class="form-control" value="<?= e($seo['default_meta_title'] ?? '') ?>">
          </div>
          <div class="form-group full-width">
            <label class="form-label">Meta Description por defecto</label>
            <textarea name="settings[seo][default_meta_description]" class="form-control" rows="3"><?= e($seo['default_meta_description'] ?? '') ?></textarea>
          </div>
          <div class="form-group">
            <label class="form-label">Robots por defecto</label>
            <select name="settings[seo][default_robots]" class="form-select">
              <option value="index, follow" <?= ($seo['default_robots'] ?? '') === 'index, follow' ? 'selected' : '' ?>>Index, Follow</option>
              <option value="noindex, follow" <?= ($seo['default_robots'] ?? '') === 'noindex, follow' ? 'selected' : '' ?>>Noindex, Follow</option>
              <option value="noindex, nofollow" <?= ($seo['default_robots'] ?? '') === 'noindex, nofollow' ? 'selected' : '' ?>>Noindex, Nofollow</option>
            </select>
          </div>
        </div>
      </div>

      <!-- 3. CACHE -->
      <div class="tab-content" data-tab="cache">
        <div class="form-grid">
          <div class="form-group">
            <label class="form-label">Estado del Cache</label>
            <div class="d-flex align-center gap-2 mt-1">
              <label class="form-switch">
                <input type="hidden" name="settings[cache][enabled]" value="0">
                <input type="checkbox" name="settings[cache][enabled]" value="1" class="form-check-input" <?= ($cache['enabled'] ?? false) ? 'checked' : '' ?>>
                <span class="form-check-label ms-2">Activar caché de páginas</span>
              </label>
            </div>
          </div>
          <div class="form-group">
            <label class="form-label">TTL (Tiempo de vida en segundos)</label>
            <input type="number" name="settings[cache][ttl]" class="form-control" value="<?= e($cache['ttl'] ?? 3600) ?>" min="60" step="60">
            <small class="form-text">Ej: 3600 = 1 hora</small>
          </div>
        </div>
      </div>

      <!-- 4. CORREO -->
      <div class="tab-content" data-tab="mail">
        <div class="form-grid">
          <div class="form-group">
            <label class="form-label">Servidor SMTP (Host)</label>
            <input type="text" name="settings[mail][smtp_host]" class="form-control" placeholder="smtp.gmail.com" value="<?= e($mail['smtp_host'] ?? '') ?>">
          </div>
          <div class="form-group">
            <label class="form-label">Puerto SMTP</label>
            <input type="number" name="settings[mail][smtp_port]" class="form-control" value="<?= e($mail['smtp_port'] ?? 587) ?>">
          </div>
          <div class="form-group">
            <label class="form-label">Usuario SMTP</label>
            <input type="text" name="settings[mail][smtp_user]" class="form-control" value="<?= e($mail['smtp_user'] ?? '') ?>">
          </div>
          <div class="form-group">
            <label class="form-label">Contraseña SMTP</label>
            <input type="password" name="settings[mail][smtp_pass]" class="form-control" value="<?= e($mail['smtp_pass'] ?? '') ?>">
          </div>
          <div class="form-group">
            <label class="form-label">Email Remitente</label>
            <input type="email" name="settings[mail][from_email]" class="form-control" value="<?= e($mail['from_email'] ?? '') ?>">
          </div>
          <div class="form-group">
            <label class="form-label">Nombre Remitente</label>
            <input type="text" name="settings[mail][from_name]" class="form-control" value="<?= e($mail['from_name'] ?? '') ?>">
          </div>
        </div>
      </div>

      <!-- Botones de Acción -->
      <div class="d-flex justify-end gap-2 mt-4 pt-3 border-top">
        <button type="button" class="btn btn-ghost" onclick="history.back()">Cancelar</button>
        <button type="submit" class="btn btn-primary">
          <i class="bi bi-check-circle"></i> Guardar Configuración
        </button>
      </div>
    </form>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
  // Lógica de Pestañas (Tabs)
  document.querySelectorAll('.nav-tabs .nav-link').forEach(btn => {
    btn.addEventListener('click', (e) => {
      e.preventDefault();
      const tabId = btn.dataset.tab;
      
      // Desactivar todos
      document.querySelectorAll('.nav-tabs .nav-link').forEach(b => b.classList.remove('active'));
      document.querySelectorAll('.tab-content').forEach(c => c.classList.remove('active'));
      
      // Activar el seleccionado
      btn.classList.add('active');
      document.querySelector(`.tab-content[data-tab="${tabId}"]`).classList.add('active');
    });
        });
});
</script>
<?php $pageTitle = 'SEO Global'; ?>
<div class="card">
  <div class="card-header">
    <h3><i class="bi bi-globe"></i> Configuración SEO Global</h3>
  </div>
  <div class="card-body">
    <form method="post" action="<?= url('admin/seo/global/save') ?>">
      <?= csrf_field() ?>
      
      <h5 class="mb-3"><i class="bi bi-info-circle text-primary"></i> Valores por defecto</h5>
      <p class="text-muted text-small mb-3">Estos valores se usarán cuando una página no tenga su propio SEO configurado.</p>
      
      <div class="form-grid">
        <div class="form-group full-width">
          <label class="form-label">Meta Title por defecto <span class="char-counter" id="cc-title">0/60</span></label>
          <input type="text" name="default_meta_title" class="form-control" maxlength="70" value="<?= e($settings['default_meta_title']) ?>">
        </div>
        <div class="form-group full-width">
          <label class="form-label">Meta Description por defecto <span class="char-counter" id="cc-desc">0/160</span></label>
          <textarea name="default_meta_description" class="form-control" rows="3" maxlength="160"><?= e($settings['default_meta_description']) ?></textarea>
        </div>
        <div class="form-group">
          <label class="form-label">Robots por defecto</label>
          <select name="default_robots" class="form-select">
            <?php foreach (['index, follow','noindex, follow','noindex, nofollow','index, nofollow'] as $r): ?>
              <option value="<?= $r ?>" <?= $settings['default_robots']===$r?'selected':'' ?>><?= $r ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="form-group">
          <label class="form-label">Imagen OG por defecto (URL)</label>
          <input type="url" name="default_og_image" class="form-control" value="<?= e($settings['default_og_image']) ?>" placeholder="https://...">
        </div>
      </div>

      <hr class="my-4">
      <h5 class="mb-3"><i class="bi bi-graph-up text-primary"></i> Integraciones</h5>
      
      <div class="form-grid">
        <div class="form-group">
          <label class="form-label">Google Analytics ID</label>
          <input type="text" name="ga_tracking" class="form-control" value="<?= e($settings['ga_tracking']) ?>" placeholder="G-XXXXXXXXXX">
          <small class="form-text">Ej: G-ABC123XYZ</small>
        </div>
        <div class="form-group">
          <label class="form-label">Google Search Console</label>
          <input type="text" name="gsc_verification" class="form-control" value="<?= e($settings['gsc_verification']) ?>" placeholder="Código de verificación">
        </div>
        <div class="form-group">
          <label class="form-label">Twitter / X (handle)</label>
          <input type="text" name="twitter_site" class="form-control" value="<?= e($settings['twitter_site']) ?>" placeholder="@tuusuario">
        </div>
      </div>

      <div class="d-flex justify-end gap-2 mt-4 pt-3 border-top">
        <button type="submit" class="btn btn-primary"><i class="bi bi-check-circle"></i> Guardar configuración</button>
      </div>
    </form>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
  const t = document.querySelector('[name=default_meta_title]');
  const d = document.querySelector('[name=default_meta_description]');
  const ccT = document.getElementById('cc-title');
  const ccD = document.getElementById('cc-desc');
  const upd = () => {
    ccT.textContent = t.value.length + '/60';
    ccT.classList.toggle('warning', t.value.length > 60);
    ccD.textContent = d.value.length + '/160';
    ccD.classList.toggle('warning', d.value.length > 150);
    ccD.classList.toggle('danger', d.value.length > 160);
  };
  t.addEventListener('input', upd); d.addEventListener('input', upd); upd();
});
</script>
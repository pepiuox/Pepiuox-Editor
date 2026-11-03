<?php $pageTitle = 'Traducciones'; ?>
<div class="card">
  <div class="card-header">
    <h3><i class="bi bi-translate"></i> Traducciones</h3>
  </div>
  <div class="card-body">
    <form method="get" action="<?= url('admin/translations') ?>" class="form-grid mb-3">
      <div class="form-group">
        <label class="form-label">Idioma</label>
        <select name="lang" class="form-select" onchange="this.form.submit()">
          <?php foreach ($languages as $l): ?>
            <option value="<?= e($l['code']) ?>" <?= $lang===$l['code']?'selected':'' ?>><?= e($l['flag']) ?> <?= e($l['native_name']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="form-group">
        <label class="form-label">Grupo</label>
        <select name="group" class="form-select" onchange="this.form.submit()">
          <?php foreach ($groups as $g): ?>
            <option value="<?= e($g['group']) ?>" <?= $group===$g['group']?'selected':'' ?>><?= e($g['group']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="form-group">
        <label class="form-label"><?= t('search') ?></label>
        <input type="text" name="search" class="form-control" value="<?= e($search) ?>" placeholder="Buscar...">
      </div>
      <div class="form-group" style="align-self:end">
        <button class="btn btn-primary"><i class="bi bi-search"></i> <?= t('search') ?></button>
      </div>
    </form>

    <form method="post" action="<?= url('admin/translations/save') ?>">
      <?= \App\Core\CSRF::field() ?>
      <input type="hidden" name="lang" value="<?= e($lang) ?>">
      <input type="hidden" name="group" value="<?= e($group) ?>">
      <table class="table">
        <thead><tr><th style="width:30%">Key</th><th>Valor</th></tr></thead>
        <tbody>
        <?php foreach ($translations as $t): ?>
          <tr>
            <td><code><?= e($t['key']) ?></code></td>
            <td><input type="text" name="entries[<?= e($t['key']) ?>]" class="form-control" value="<?= e($t['value']) ?>"></td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
      <button class="btn btn-primary"><i class="bi bi-check"></i> <?= t('save') ?></button>
    </form>

    <hr>
    <h5>Añadir nueva clave</h5>
    <form method="post" action="<?= url('admin/translations/add') ?>" class="form-grid">
      <?= \App\Core\CSRF::field() ?>
      <input type="hidden" name="lang" value="<?= e($lang) ?>">
      <input type="hidden" name="group" value="<?= e($group) ?>">
      <div class="form-group"><label class="form-label">Key</label><input type="text" name="key" class="form-control" required></div>
      <div class="form-group"><label class="form-label">Value</label><input type="text" name="value" class="form-control" required></div>
      <div class="form-group full-width"><button class="btn btn-primary"><i class="bi bi-plus"></i> Añadir</button></div>
    </form>
  </div>
</div>
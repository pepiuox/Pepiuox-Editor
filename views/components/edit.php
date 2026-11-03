<?php
$pageTitle = $component ? 'Editar componente' : 'Nuevo componente';
$c = $component ?? ['id'=>null,'name'=>'','category'=>'general','html'=>'','css'=>'','is_active'=>1];
?>
<div class="card">
  <div class="card-header"><h3><i class="bi bi-puzzle"></i> <?= e($pageTitle) ?></h3></div>
  <div class="card-body">
    <form method="post" action="<?= url($component ? "admin/components/{$c['id']}" : 'admin/components') ?>">
      <?= \App\Core\CSRF::field() ?>
      <div class="form-grid">
        <div class="form-group">
          <label class="form-label">Nombre</label>
          <input type="text" name="name" class="form-control" value="<?= e($c['name']) ?>" required>
        </div>
        <div class="form-group">
          <label class="form-label">Categoría</label>
          <select name="category" class="form-select">
            <?php foreach (['general','layout','header','footer','hero','card','form','navigation','media'] as $cat): ?>
              <option value="<?= $cat ?>" <?= $c['category']===$cat?'selected':'' ?>><?= ucfirst($cat) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>

      <ul class="nav-tabs mt-3">
        <li><button type="button" class="nav-link active" data-tab="html"><i class="bi bi-code-slash"></i> HTML</button></li>
        <li><button type="button" class="nav-link" data-tab="css"><i class="bi bi-palette2"></i> CSS</button></li>
        <li><button type="button" class="nav-link" data-tab="preview"><i class="bi bi-eye"></i> Preview</button></li>
      </ul>
      <div class="tab-content active" data-tab="html">
        <textarea name="html" id="cm-html" class="form-control" rows="15"><?= e($c['html']) ?></textarea>
      </div>
      <div class="tab-content" data-tab="css">
        <textarea name="css" id="cm-css" class="form-control" rows="15"><?= e($c['css']) ?></textarea>
      </div>
      <div class="tab-content" data-tab="preview">
        <div id="preview" style="min-height:300px;background:#fff;padding:1rem;border-radius:var(--radius-sm)"></div>
      </div>

      <label class="form-check mt-3"><input type="checkbox" name="is_active" value="1" class="form-check-input" <?= $c['is_active']?'checked':'' ?>> Activo</label>

      <div class="d-flex gap-2 mt-3">
        <button class="btn btn-primary"><i class="bi bi-check"></i> Guardar</button>
        <a class="btn btn-ghost" href="<?= url('admin/components') ?>">Cancelar</a>
      </div>
    </form>
  </div>
</div>

<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/codemirror@5.65.16/lib/codemirror.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/codemirror@5.65.16/theme/dracula.min.css">
<script src="https://cdn.jsdelivr.net/npm/codemirror@5.65.16/lib/codemirror.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/codemirror@5.65.16/mode/htmlmixed/htmlmixed.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/codemirror@5.65.16/mode/css/css.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', () => {
  const cmHtml = CodeMirror.fromTextArea(document.getElementById('cm-html'), { mode:'htmlmixed', theme:'dracula', lineNumbers:true });
  const cmCss = CodeMirror.fromTextArea(document.getElementById('cm-css'), { mode:'css', theme:'dracula', lineNumbers:true });

  document.querySelectorAll('.nav-tabs .nav-link').forEach(btn => {
    btn.addEventListener('click', () => {
      const tab = btn.dataset.tab;
      document.querySelectorAll('.nav-tabs .nav-link').forEach(b => b.classList.remove('active'));
      document.querySelectorAll('.tab-content').forEach(c => c.classList.remove('active'));
      btn.classList.add('active');
      document.querySelector('.tab-content[data-tab="'+tab+'"]').classList.add('active');
      if (tab === 'preview') {
        document.getElementById('preview').innerHTML = '<style>'+cmCss.getValue()+'</style>' + cmHtml.getValue();
      }
    });
  });
});
</script>
<?php
$pageTitle = $template ? 'Editar template' : 'Nuevo template';
$t = $template ?? ['id'=>null,'name'=>'','description'=>'','content_html'=>'','content_css'=>'','content_js'=>'','content_php'=>'','grapesjs_data'=>null,'is_active'=>1,'is_default'=>0,'is_system_template'=>0];
?>
<div class="card">
  <div class="card-header"><h3><i class="bi bi-layout-text-window"></i> <?= e($pageTitle) ?></h3></div>
  <div class="card-body">
    <form method="post" action="<?= url($template ? "admin/templates/{$t['id']}" : 'admin/templates') ?>" id="templateForm">
      <?= \App\Core\CSRF::field() ?>
      <div class="form-grid">
        <div class="form-group">
          <label class="form-label">Nombre</label>
          <input type="text" name="name" class="form-control" value="<?= e($t['name']) ?>" required>
        </div>
        <div class="form-group">
          <label class="form-label">Descripción</label>
          <input type="text" name="description" class="form-control" value="<?= e($t['description']) ?>">
        </div>
      </div>

      <ul class="nav-tabs mt-3">
        <li><button type="button" class="nav-link active" data-tab="visual"><i class="bi bi-palette"></i> Visual</button></li>
        <li><button type="button" class="nav-link" data-tab="html"><i class="bi bi-code-slash"></i> HTML</button></li>
        <li><button type="button" class="nav-link" data-tab="css"><i class="bi bi-palette2"></i> CSS</button></li>
        <li><button type="button" class="nav-link" data-tab="js"><i class="bi bi-braces"></i> JS</button></li>
        <?php if (can('templates.edit')): ?><li><button type="button" class="nav-link" data-tab="php"><i class="bi bi-filetype-php"></i> PHP</button></li><?php endif; ?>
      </ul>

      <div class="tab-content active" data-tab="visual">
        <div id="gjs-template" style="height:600px"></div>
      </div>
      <div class="tab-content" data-tab="html">
        <textarea name="content_html" id="cm-html" class="form-control" rows="18"><?= e($t['content_html']) ?></textarea>
      </div>
      <div class="tab-content" data-tab="css">
        <textarea name="content_css" id="cm-css" class="form-control" rows="18"><?= e($t['content_css']) ?></textarea>
      </div>
      <div class="tab-content" data-tab="js">
        <textarea name="content_js" id="cm-js" class="form-control" rows="18"><?= e($t['content_js']) ?></textarea>
      </div>
      <?php if (can('templates.edit')): ?>
      <div class="tab-content" data-tab="php">
        <textarea name="content_php" id="cm-php" class="form-control" rows="18"><?= e($t['content_php']) ?></textarea>
      </div>
      <?php endif; ?>
      <input type="hidden" name="grapesjs_data" id="grapesjs-data" value="<?= e($t['grapesjs_data'] ?? '') ?>">

      <hr>
      <label class="form-check"><input type="checkbox" name="is_active" value="1" class="form-check-input" <?= $t['is_active']?'checked':'' ?>> Activo</label>
      <label class="form-check"><input type="checkbox" name="is_default" value="1" class="form-check-input" <?= $t['is_default']?'checked':'' ?>> Por defecto</label>

      <div class="d-flex gap-2 mt-3">
        <button class="btn btn-primary" type="submit"><i class="bi bi-check"></i> Guardar</button>
        <a class="btn btn-ghost" href="<?= url('admin/templates') ?>">Cancelar</a>
      </div>
    </form>
  </div>
</div>

<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/grapesjs@0.21.10/dist/css/grapes.min.css">
<script src="https://cdn.jsdelivr.net/npm/grapesjs@0.21.10/dist/grapes.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', () => {
  // Tabs
  document.querySelectorAll('.nav-tabs .nav-link').forEach(btn => {
    btn.addEventListener('click', () => {
      const tab = btn.dataset.tab;
      document.querySelectorAll('.nav-tabs .nav-link').forEach(b => b.classList.remove('active'));
      document.querySelectorAll('.tab-content').forEach(c => c.classList.remove('active'));
      btn.classList.add('active');
      document.querySelector('.tab-content[data-tab="'+tab+'"]').classList.add('active');
    });
  });

  // GrapesJS
  const initialData = document.getElementById('grapesjs-data').value;
  const editor = grapesjs.init({
    container: '#gjs-template',
    height: '600px',
    fromElement: false,
    storageManager: { type: 'none' },
    components: initialData ? JSON.parse(initialData).pages?.[0]?.components || '' : (document.getElementById('cm-html').value || ''),
    style: document.getElementById('cm-css').value || '',
    plugins: ['gjs-blocks-basic','grapesjs-bootstrap5'],
    pluginsOpts: { 'gjs-blocks-basic': {}, 'grapesjs-bootstrap5': {} },
    // Cargar componentes personalizados desde la BD
fetch('<?= url("admin/components/api") ?>')
  .then(r => r.json())
  .then(d => {
    if (!d.success) return;
    const bm = editor.BlockManager;
    const grouped = {};
    d.data.forEach(c => {
      if (!grouped[c.category]) grouped[c.category] = [];
      grouped[c.category].push(c);
    });
    Object.entries(grouped).forEach(([cat, items]) => {
      items.forEach(c => {
        bm.add('custom-' + c.id, {
          label: c.name,
          category: '📦 ' + cat,
          content: {
            type: 'default',
            content: c.html,
            style: c.css,
          },
        });
      });
    });
  });
  });

  // Sincronizar al guardar
  document.getElementById('templateForm').addEventListener('submit', () => {
    const html = editor.getHtml();
    const css = editor.getCss();
    document.getElementById('cm-html').value = html;
    document.getElementById('cm-css').value = css;
    document.getElementById('grapesjs-data').value = JSON.stringify(editor.getProjectData());
  });
});
</script>
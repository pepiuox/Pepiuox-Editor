<?php
$pageTitle = 'Editar: ' . $page['title'];
$seo = $seo ?? ['basic'=>[],'og'=>[],'twitter'=>[]];
?>
<div class="card">
  <div class="card-header">
    <h3><i class="bi bi-pencil-square"></i> <?= e($page['title']) ?></h3>
    <div class="d-flex gap-2">
      <a class="btn btn-ghost btn-sm" href="<?= url($page['slug']) ?>" target="_blank"><i class="bi bi-eye"></i> Ver</a>
      <a class="btn btn-ghost btn-sm" href="<?= url("admin/pages/{$page['id']}/versions") ?>"><i class="bi bi-clock-history"></i> Versiones</a>
      <button class="btn btn-primary btn-sm" id="btnSave"><i class="bi bi-check"></i> Guardar</button>
    </div>
  </div>
  <div class="card-body p-0">
    <form id="pageForm" method="post" action="<?= url("admin/pages/{$page['id']}") ?>">
      <?= \App\Core\CSRF::field() ?>
      <input type="hidden" name="mode" id="editMode" value="code">

      <!-- Barra de modo -->
      <div class="editor-toolbar d-flex justify-between align-center p-2 border-bottom">
        <div class="d-flex gap-2">
          <button type="button" class="btn btn-sm btn-primary mode-btn active" data-mode="code"><i class="bi bi-code-slash"></i> Código</button>
          <button type="button" class="btn btn-sm btn-ghost mode-btn" data-mode="visual"><i class="bi bi-palette"></i> Visual</button>
          <button type="button" class="btn btn-sm btn-ghost" id="btnPreview"><i class="bi bi-phone"></i> Preview</button>
        </div>
        <div class="d-flex gap-2 align-center">
          <span class="text-small text-muted" id="saveStatus">Listo</span>
        </div>
      </div>

      <div class="d-flex" style="min-height:70vh">
        <!-- Panel izquierdo: propiedades -->
        <aside class="editor-sidebar-left p-3 border-end" style="width:280px;overflow-y:auto">
          <h5 class="text-small text-muted mb-2">PROPIEDADES</h5>
          <div class="form-group">
            <label class="form-label">Título</label>
            <input type="text" name="title" class="form-control" value="<?= e($page['title']) ?>" required>
          </div>
          <div class="form-group">
            <label class="form-label">Slug</label>
            <input type="text" name="slug" class="form-control" value="<?= e($page['slug']) ?>">
          </div>
          <div class="form-group">
            <label class="form-label">H1</label>
            <input type="text" name="h1" class="form-control" value="<?= e($page['h1'] ?? '') ?>">
          </div>
          <div class="form-group">
            <label class="form-label">Descripción</label>
            <textarea name="description" class="form-control" rows="2"><?= e($page['description']) ?></textarea>
          </div>
          <div class="form-group">
            <label class="form-label">Plantilla</label>
            <select name="template_id" class="form-select">
              <option value="">— Sin plantilla —</option>
              <?php foreach ($templates as $t): ?>
                <option value="<?= (int)$t['id'] ?>" <?= ($page['template_id']==$t['id'])?'selected':'' ?>><?= e($t['name']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="form-group">
            <label class="form-label">Padre</label>
            <select name="parent_id" class="form-select">
              <option value="">— Ninguna —</option>
              <?php foreach ($pages as $p): if ($p['id']==$page['id']) continue; ?>
                <option value="<?= (int)$p['id'] ?>" <?= ($page['parent_id']==$p['id'])?'selected':'' ?>><?= e($p['title']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="form-group">
            <label class="form-label">Estado</label>
            <select name="status" class="form-select">
              <?php foreach (['draft'=>'Borrador','published'=>'Publicada','archived'=>'Archivada'] as $k=>$v): ?>
                <option value="<?= $k ?>" <?= $page['status']==$k?'selected':'' ?>><?= $v ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="form-group">
            <label class="form-label">Publicar el</label>
            <input type="datetime-local" name="publish_at" class="form-control" value="<?= e($page['publish_at'] ? date('Y-m-d\TH:i', strtotime($page['publish_at'])) : '') ?>">
          </div>
          <label class="form-check"><input type="checkbox" name="is_published" value="1" class="form-check-input" <?= $page['is_published']?'checked':'' ?>> Publicada</label>
          <label class="form-check"><input type="checkbox" name="startpage" value="1" class="form-check-input" <?= $page['startpage']?'checked':'' ?>> Página de inicio</label>
          <div class="form-group mt-3">
            <label class="form-label">Nota de versión</label>
            <input type="text" name="version_note" class="form-control" placeholder="Opcional">
          </div>
        </aside>

        <!-- Centro: editor -->
        <main class="flex-grow-1 position-relative">
          <!-- Modo código -->
          <div id="modeCode" class="h-100">
            <div class="tabs-container">
              <div class="tabs">
                <button type="button" class="tab-btn active" data-lang="html"><i class="bi bi-code-slash"></i> HTML</button>
                <button type="button" class="tab-btn" data-lang="css"><i class="bi bi-palette2"></i> CSS</button>
                <button type="button" class="tab-btn" data-lang="js"><i class="bi bi-braces"></i> JavaScript</button>
                <?php if (can('pages.edit')): ?><button type="button" class="tab-btn" data-lang="php"><i class="bi bi-filetype-php"></i> PHP</button><?php endif; ?>
              </div>
            </div>
            <div style="height:calc(70vh - 50px)">
              <textarea id="cm-html"><?= e($page['content_html']) ?></textarea>
              <textarea id="cm-css" style="display:none"><?= e($page['content_css']) ?></textarea>
              <textarea id="cm-js" style="display:none"><?= e($page['content_js']) ?></textarea>
              <?php if (can('pages.edit')): ?><textarea id="cm-php" style="display:none"><?= e($page['content_php']) ?></textarea><?php endif; ?>
            </div>
          </div>
          <!-- Modo visual -->
          <div id="modeVisual" style="display:none;height:calc(70vh - 50px)">
            <div id="gjs"></div>
          </div>
        </main>

        <!-- Panel derecho: SEO -->
        <aside class="editor-sidebar-right p-3 border-start" style="width:320px;overflow-y:auto">
          <h5 class="text-small text-muted mb-2">SEO</h5>
         
          <div class="form-group">
            <label class="form-label">Meta title <span class="char-counter" id="cc-title">0/60</span></label>
            <input type="text" name="seo_title" class="form-control" maxlength="70" value="<?= e($seo['basic']['title'] ?? '') ?>">
          </div>
          <div class="form-group">
            <label class="form-label">Meta description <span class="char-counter" id="cc-desc">0/160</span></label>
            <textarea name="seo_description" class="form-control" rows="3" maxlength="160"><?= e($seo['basic']['description'] ?? '') ?></textarea>
          </div>
          <div class="form-group">
            <label class="form-label">Keywords</label>
            <input type="text" name="seo_keywords" class="form-control" value="<?= e($seo['basic']['keywords'] ?? '') ?>">
          </div>
          <div class="form-group">
            <label class="form-label">Canonical</label>
            <input type="url" name="seo_canonical" class="form-control" value="<?= e($seo['basic']['canonical'] ?? '') ?>">
          </div>
          <div class="form-group">
            <label class="form-label">Robots</label>
            <input type="text" name="seo_robots" class="form-control" value="<?= e($seo['basic']['robots'] ?? 'index,follow') ?>">
          </div>
          <hr>
          <h5 class="text-small text-muted mb-2">OPEN GRAPH</h5>
          <div class="form-group"><label class="form-label">OG Title</label><input type="text" name="og_title" class="form-control" value="<?= e($seo['og']['title'] ?? '') ?>"></div>
          <div class="form-group"><label class="form-label">OG Description</label><textarea name="og_description" class="form-control" rows="2"><?= e($seo['og']['description'] ?? '') ?></textarea></div>
          <div class="form-group"><label class="form-label">OG Image URL</label><input type="url" name="og_image" class="form-control" value="<?= e($seo['og']['image'] ?? '') ?>"></div>
          <hr>
          <h5 class="text-small text-muted mb-2">TWITTER</h5>
          <div class="form-group"><label class="form-label">Card</label>
            <select name="twitter_card" class="form-select">
              <option value="summary" <?= ($seo['twitter']['card']??'')==='summary'?'selected':'' ?>>Summary</option>
              <option value="summary_large_image" <?= ($seo['twitter']['card']??'summary_large_image')==='summary_large_image'?'selected':'' ?>>Summary large image</option>
            </select>
          </div>
          <div class="form-group"><label class="form-label">Twitter Title</label><input type="text" name="twitter_title" class="form-control" value="<?= e($seo['twitter']['title'] ?? '') ?>"></div>
          <div class="form-group"><label class="form-label">Twitter Image</label><input type="url" name="twitter_image" class="form-control" value="<?= e($seo['twitter']['image'] ?? '') ?>"></div>
        <hr>
<div class="d-grid gap-2">
  <a class="btn btn-primary btn-sm" href="<?= url("admin/seo/{$page['id']}/analyze") ?>">
    <i class="bi bi-clipboard-data"></i> Analizar SEO
  </a>
  <a class="btn btn-outline btn-sm" href="<?= url("admin/seo/{$page['id']}/competitor") ?>">
    <i class="bi bi-trophy"></i> Análisis de competencia
  </a>
  <a class="btn btn-outline btn-sm" href="<?= url("admin/seo/{$page['id']}/meta-generator") ?>">
    <i class="bi bi-magic"></i> Generar meta description
  </a>
</div>
        </aside>
      </div>
      <input type="hidden" name="content_html" id="hidden-html">
      <input type="hidden" name="content_css" id="hidden-css">
      <input type="hidden" name="content_js" id="hidden-js">
      <input type="hidden" name="content_php" id="hidden-php">
      <input type="hidden" name="grapesjs_data" id="grapesjs-data" value="<?= e($page['grapesjs_data'] ?? '') ?>">
    </form>
  </div>
</div>

<!-- CodeMirror -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/codemirror@5.65.16/lib/codemirror.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/codemirror@5.65.16/theme/dracula.min.css">
<script src="https://cdn.jsdelivr.net/npm/codemirror@5.65.16/lib/codemirror.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/codemirror@5.65.16/mode/htmlmixed/htmlmixed.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/codemirror@5.65.16/mode/xml/xml.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/codemirror@5.65.16/mode/css/css.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/codemirror@5.65.16/mode/javascript/javascript.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/codemirror@5.65.16/mode/php/php.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/codemirror@5.65.16/addon/edit/closebrackets.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/codemirror@5.65.16/addon/edit/closetag.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/codemirror@5.65.16/addon/fold/foldcode.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/codemirror@5.65.16/addon/fold/foldgutter.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/codemirror@5.65.16/addon/fold/brace-fold.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/codemirror@5.65.16/addon/fold/xml-fold.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/codemirror@5.65.16/addon/search/search.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/codemirror@5.65.16/addon/search/searchcursor.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/codemirror@5.65.16/addon/dialog/dialog.min.js"></script>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/codemirror@5.65.16/addon/fold/foldgutter.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/codemirror@5.65.16/addon/dialog/dialog.min.css">

<!-- GrapesJS -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/grapesjs@0.21.10/dist/css/grapes.min.css">
<script src="https://cdn.jsdelivr.net/npm/grapesjs@0.21.10/dist/grapes.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/grapesjs-blocks-basic@1.0.2/dist/index.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/grapesjs-plugin-forms@2.0.6/dist/index.min.js"></script>

<style>
.editor-toolbar{background:var(--bg-card-2)}
.editor-sidebar-left,.editor-sidebar-right{background:var(--bg-card)}
</style>

<script>
const PAGE_ID = <?= (int)$page['id'] ?>;
const CSRF = document.querySelector('input[name="_csrf_token"]').value;

let cmHtml, cmCss, cmJs, cmPhp, gjsEditor;
let currentMode = 'code';
let currentLang = 'html';
let dirty = false;

document.addEventListener('DOMContentLoaded', () => {
  // CodeMirror
  const cmOpts = (mode) => ({
    mode, theme:'dracula', lineNumbers:true, lineWrapping:true,
    autoCloseBrackets:true, autoCloseTags: mode==='htmlmixed',
    foldGutter:true, gutters:["CodeMirror-linenumbers","CodeMirror-foldgutter"],
    extraKeys: {"Ctrl-Space":"autocomplete","Ctrl-S":()=>save()},
    indentUnit: 2, tabSize: 2,
  });
  cmHtml = CodeMirror.fromTextArea(document.getElementById('cm-html'), cmOpts('htmlmixed'));
  cmCss  = CodeMirror.fromTextArea(document.getElementById('cm-css'),  cmOpts('css'));
  cmJs   = CodeMirror.fromTextArea(document.getElementById('cm-js'),   cmOpts('javascript'));
  const phpEl = document.getElementById('cm-php');
  cmPhp  = phpEl ? CodeMirror.fromTextArea(phpEl, cmOpts('php')) : null;

  [cmHtml,cmCss,cmJs,cmPhp].forEach(cm => {
    if (!cm) return;
    cm.on('change', () => { dirty = true; updateStatus(); });
  });

  // Tabs de lenguaje
  document.querySelectorAll('.tabs .tab-btn').forEach(btn => {
    btn.addEventListener('click', () => switchLang(btn.dataset.lang));
  });

  // GrapesJS
  const initData = document.getElementById('grapesjs-data').value;
  gjsEditor = grapesjs.init({
    container: '#gjs',
    height: '100%',
    storageManager: { type: 'none' },
    components: initData ? (JSON.parse(initData).pages?.[0]?.components || cmHtml.getValue()) : cmHtml.getValue(),
    style: cmCss.getValue(),
    plugins: ['gjs-blocks-basic','gjs-plugin-forms'],
    pluginsOpts: { 'gjs-blocks-basic': {}, 'gjs-plugin-forms': {} },
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
  gjsEditor.on('change:canvasOffset', () => { dirty = true; updateStatus(); });

  // Cambio de modo
  document.querySelectorAll('.mode-btn').forEach(b => {
    b.addEventListener('click', () => switchMode(b.dataset.mode));
  });

  // Guardar
  document.getElementById('btnSave').addEventListener('click', save);
  document.getElementById('btnPreview').addEventListener('click', preview);

  // Autosave cada 30s
  setInterval(autosave, 30000);

  // Contadores SEO
  const seoT = document.querySelector('[name=seo_title]');
  const seoD = document.querySelector('[name=seo_description]');
  const ccT = document.getElementById('cc-title');
  const ccD = document.getElementById('cc-desc');
  const upd = () => {
    ccT.textContent = (seoT.value.length) + '/60';
    ccT.classList.toggle('warning', seoT.value.length > 60);
    ccD.textContent = (seoD.value.length) + '/160';
    ccD.classList.toggle('warning', seoD.value.length > 150);
    ccD.classList.toggle('danger', seoD.value.length > 160);
  };
  seoT.addEventListener('input', upd); seoD.addEventListener('input', upd); upd();

  // Ctrl+S global
  document.addEventListener('keydown', e => {
    if ((e.ctrlKey||e.metaKey) && e.key === 's') { e.preventDefault(); save(); }
  });
});

function switchLang(lang) {
  currentLang = lang;
  document.querySelectorAll('.tabs .tab-btn').forEach(b => b.classList.toggle('active', b.dataset.lang===lang));
  cmHtml.getWrapperElement().style.display = lang==='html' ? '' : 'none';
  cmCss.getWrapperElement().style.display  = lang==='css'  ? '' : 'none';
  cmJs.getWrapperElement().style.display   = lang==='js'   ? '' : 'none';
  if (cmPhp) cmPhp.getWrapperElement().style.display = lang==='php' ? '' : 'none';
  setTimeout(() => { ({html:cmHtml,css:cmCss,js:cmJs,php:cmPhp}[lang]).refresh(); }, 10);
}

function switchMode(mode) {
  if (mode === currentMode) return;
  if (mode === 'visual') {
    // Pasar de CodeMirror a GrapesJS
    gjsEditor.setComponents(cmHtml.getValue());
    gjsEditor.setStyle(cmCss.getValue());
  } else {
    // Pasar de GrapesJS a CodeMirror
    cmHtml.setValue(gjsEditor.getHtml());
    cmCss.setValue(gjsEditor.getCss());
  }
  currentMode = mode;
  document.getElementById('editMode').value = mode;
  document.getElementById('modeCode').style.display   = mode==='code' ? '' : 'none';
  document.getElementById('modeVisual').style.display = mode==='visual' ? '' : 'none';
  document.querySelectorAll('.mode-btn').forEach(b => b.classList.toggle('active', b.dataset.mode===mode));
  if (mode==='code') cmHtml.refresh();
}

function syncToHidden() {
  if (currentMode === 'visual') {
    document.getElementById('hidden-html').value = gjsEditor.getHtml();
    document.getElementById('hidden-css').value  = gjsEditor.getCss();
    document.getElementById('hidden-js').value   = cmJs.getValue();
    if (cmPhp) document.getElementById('hidden-php').value = cmPhp.getValue();
    document.getElementById('grapesjs-data').value = JSON.stringify(gjsEditor.getProjectData());
  } else {
    document.getElementById('hidden-html').value = cmHtml.getValue();
    document.getElementById('hidden-css').value  = cmCss.getValue();
    document.getElementById('hidden-js').value   = cmJs.getValue();
    if (cmPhp) document.getElementById('hidden-php').value = cmPhp.getValue();
    document.getElementById('grapesjs-data').value = JSON.stringify(gjsEditor.getProjectData());
  }
}

function save() {
  syncToHidden();
  document.getElementById('saveStatus').textContent = 'Guardando...';
  document.getElementById('saveStatus').classList.add('text-warning');
  fetch(`<?= url("admin/pages/{$page['id']}") ?>`, {
    method: 'POST',
    body: new FormData(document.getElementById('pageForm')),
    headers: {'X-Requested-With':'XMLHttpRequest'}
  }).then(r => r.json()).then(d => {
    dirty = false;
    document.getElementById('saveStatus').textContent = 'Guardado ✓';
    document.getElementById('saveStatus').classList.remove('text-warning');
    document.getElementById('saveStatus').classList.add('text-success');
    setTimeout(() => document.getElementById('saveStatus').textContent = 'Listo', 2000);
  }).catch(() => {
    document.getElementById('saveStatus').textContent = 'Error al guardar';
    document.getElementById('saveStatus').classList.add('text-danger');
  });
}

function autosave() {
  if (!dirty) return;
  syncToHidden();
  const fd = new FormData();
  ['content_html','content_css','content_js','content_php','grapesjs_data'].forEach(k => {
    const el = document.getElementById(k.replace('_','-').replace('content_','hidden-').replace('grapesjs_data','grapesjs-data'));
    // simplificado:
  });
  // Usar los hidden fields ya sincronizados
  fd.append('content_html', document.getElementById('hidden-html').value);
  fd.append('content_css',  document.getElementById('hidden-css').value);
  fd.append('content_js',   document.getElementById('hidden-js').value);
  fd.append('content_php',  document.getElementById('hidden-php')?.value || '');
  fd.append('grapesjs_data', document.getElementById('grapesjs-data').value);
  fd.append('_csrf_token', CSRF);
  fetch(`<?= url("admin/pages/{$page['id']}/autosave") ?>`, {
    method:'POST', body: fd, headers:{'X-Requested-With':'XMLHttpRequest'}
  }).then(r=>r.json()).then(()=>{ dirty = false; });
}

function preview() {
  syncToHidden();
  const w = window.open('', '_blank');
  w.document.write('<html><head><style>' + document.getElementById('hidden-css').value + '</style></head><body>' + document.getElementById('hidden-html').value + '<script>' + document.getElementById('hidden-js').value + '<\/script></body></html>');
}

function updateStatus() {
  const s = document.getElementById('saveStatus');
  if (dirty) { s.textContent = 'Cambios pendientes'; s.classList.add('text-warning'); }
}
</script>
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

<!-- ferificar funcion de codigo -->

<!-- Barra de modo -->
<div class="editor-toolbar d-flex justify-between align-center p-2 border-bottom">
  <div class="d-flex gap-2">
    <button type="button" class="btn btn-sm btn-ghost mode-btn" data-mode="code"><i class="bi bi-code-slash"></i> Código</button>
    <button type="button" class="btn btn-sm btn-ghost mode-btn" data-mode="visual"><i class="bi bi-palette"></i> Visual (GrapesJS)</button>
    <button type="button" class="btn btn-sm btn-primary mode-btn active" data-mode="wysiwyg"><i class="bi bi-file-richtext"></i> Editor rico</button>
    <button type="button" class="btn btn-sm btn-ghost" id="btnPreview"><i class="bi bi-phone"></i> Preview</button>
  </div>
  <div class="d-flex gap-2 align-center">
    <span class="text-small text-muted" id="saveStatus">Listo</span>
  </div>
</div>

<div class="d-flex" style="min-height:70vh">
  <!-- Panel izquierdo: propiedades (igual que antes) -->
  <aside class="editor-sidebar-left p-3 border-end" style="width:280px;overflow-y:auto">
    <!-- ... contenido igual que en fase 9 ... -->
  </aside>

  <!-- Centro: editor -->
  <main class="flex-grow-1 position-relative">
    <!-- Modo código -->
    <div id="modeCode" style="display:none" class="h-100">
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

    <!-- Modo visual (GrapesJS) -->
    <div id="modeVisual" style="display:none;height:calc(70vh - 50px)">
      <div id="gjs"></div>
    </div>

    <!-- Modo WYSIWYG (TinyMCE) -->
    <div id="modeWysiwyg" class="h-100">
      <textarea id="wysiwyg-editor"><?= e($page['content_html']) ?></textarea>
    </div>
  </main>

  <!-- Panel derecho: SEO (igual que antes) -->
  <aside class="editor-sidebar-right p-3 border-start" style="width:320px;overflow-y:auto">
    <!-- ... contenido igual que en fase 9 ... -->
  </aside>
</div>
<!-- TinyMCE -->
<script src="https://cdn.jsdelivr.net/npm/tinymce@6.8.2/tinymce.min.js"></script>
<script>
let tinymceEditor = null;
let currentMode = 'wysiwyg'; // ahora empieza en WYSIWYG

document.addEventListener('DOMContentLoaded', () => {
  // Inicializar TinyMCE
  tinymce.init({
    selector: '#wysiwyg-editor',
    height: 'calc(70vh - 50px)',
    menubar: 'file edit view insert format tools table help',
    plugins: [
      'advlist','autolink','lists','link','image','charmap','preview','anchor',
      'searchreplace','visualblocks','code','fullscreen','insertdatetime',
      'media','table','code','help','wordcount','emoticons','codesample'
    ],
    toolbar: 'undo redo | blocks | bold italic forecolor | ' +
             'alignleft aligncenter alignright alignjustify | ' +
             'bullist numlist outdent indent | link image media table | ' +
             'removeformat code fullscreen help',
    content_style: 'body { font-family: Inter, -apple-system, sans-serif; font-size: 14px; background: #0d1117; color: #e6edf3; } ' +
                   'a { color: #00d4aa; } ' +
                   'img { max-width: 100%; height: auto; } ' +
                   'table { border-collapse: collapse; width: 100%; } ' +
                   'td, th { border: 1px solid #30363d; padding: 8px; }',
    skin: false,  // usamos el tema oscuro del CMS
    content_css: false,
    branding: false,
    promotion: false,
    statusbar: true,
    resize: true,
    automatic_uploads: true,
    images_upload_url: '<?= url("admin/files/wysiwyg-upload") ?>',
    images_upload_credentials: true,
    file_picker_types: 'image media file',
    file_picker_callback: function(cb, value, meta) {
      // Abrir selector de archivos del CMS
      const input = document.createElement('input');
      input.setAttribute('type', 'file');
      input.setAttribute('accept', meta.accept || '*/*');
      input.onchange = async () => {
        const file = input.files[0];
        const fd = new FormData();
        fd.append('files[]', file);
        fd.append('_csrf_token', CSRF);
        const r = await fetch('<?= url("admin/files/wysiwyg-upload") ?>', { method: 'POST', body: fd });
        const d = await r.json();
        if (d.success) cb(d.location, { text: d.name });
      };
      input.click();
    },
    style_formats: [
      { title: 'Encabezados', items: [
        { title: 'H1', format: 'h1' }, { title: 'H2', format: 'h2' },
        { title: 'H3', format: 'h3' }, { title: 'H4', format: 'h4' }
      ]},
      { title: 'Blocks', items: [
        { title: 'Párrafo', format: 'p' },
        { title: 'Blockquote', format: 'blockquote' },
        { title: 'Pre', format: 'pre' }
      ]},
      { title: 'Bootstrap', items: [
        { title: 'Container', inline: 'div', attributes: { class: 'container' } },
        { title: 'Row', inline: 'div', attributes: { class: 'row' } },
        { title: 'Col-6', inline: 'div', attributes: { class: 'col-md-6' } },
        { title: 'Alert success', inline: 'div', attributes: { class: 'alert alert-success' } },
        { title: 'Alert danger', inline: 'div', attributes: { class: 'alert alert-danger' } },
        { title: 'Badge', inline: 'span', attributes: { class: 'badge badge-primary' } },
        { title: 'Button', inline: 'a', attributes: { class: 'btn btn-primary' } }
      ]}
    ],
    templates: [
      { title: 'Hero section', description: 'Sección hero con título y botón',
        content: '<div class="container py-5 text-center"><h1 class="display-4">Título principal</h1><p class="lead">Subtítulo descriptivo</p><a href="#" class="btn btn-primary btn-lg mt-3">Acción</a></div>' },
      { title: 'Card', description: 'Tarjeta Bootstrap',
        content: '<div class="card"><div class="card-body"><h5 class="card-title">Título</h5><p class="card-text">Contenido</p><a href="#" class="btn btn-primary">Ver más</a></div></div>' },
      { title: 'Dos columnas', description: 'Grid de 2 columnas',
        content: '<div class="row"><div class="col-md-6">Columna 1</div><div class="col-md-6">Columna 2</div></div>' }
    ],
    setup: function(editor) {
      tinymceEditor = editor;
      editor.on('change keyup', () => { dirty = true; updateStatus(); });
    }
  });

  // Actualizar función switchMode para soportar wysiwyg
  window.switchMode = function(mode) {
    if (mode === currentMode) return;

    // Sincronizar contenido al salir del modo actual
    if (currentMode === 'wysiwyg' && tinymceEditor) {
      const html = tinymceEditor.getContent();
      cmHtml.setValue(html);
      if (mode === 'visual') gjsEditor.setComponents(html);
    } else if (currentMode === 'visual') {
      const html = gjsEditor.getHtml();
      cmHtml.setValue(html);
      cmCss.setValue(gjsEditor.getCss());
      if (mode === 'wysiwyg' && tinymceEditor) tinymceEditor.setContent(html);
    } else if (currentMode === 'code') {
      if (mode === 'wysiwyg' && tinymceEditor) tinymceEditor.setContent(cmHtml.getValue());
      if (mode === 'visual') {
        gjsEditor.setComponents(cmHtml.getValue());
        gjsEditor.setStyle(cmCss.getValue());
      }
    }

    currentMode = mode;
    document.getElementById('editMode').value = mode;
    document.getElementById('modeCode').style.display    = mode==='code' ? '' : 'none';
    document.getElementById('modeVisual').style.display  = mode==='visual' ? '' : 'none';
    document.getElementById('modeWysiwyg').style.display = mode==='wysiwyg' ? '' : 'none';
    document.querySelectorAll('.mode-btn').forEach(b => b.classList.toggle('active', b.dataset.mode===mode));
    if (mode==='code') cmHtml.refresh();
  };

  // Actualizar syncToHidden para incluir wysiwyg
  window.syncToHidden = function() {
    let html = '';
    if (currentMode === 'wysiwyg' && tinymceEditor) {
      html = tinymceEditor.getContent();
    } else if (currentMode === 'visual') {
      html = gjsEditor.getHtml();
      document.getElementById('hidden-css').value = gjsEditor.getCss();
      document.getElementById('grapesjs-data').value = JSON.stringify(gjsEditor.getProjectData());
    } else {
      html = cmHtml.getValue();
      document.getElementById('hidden-css').value = cmCss.getValue();
    }
    document.getElementById('hidden-html').value = html;
    document.getElementById('hidden-js').value = cmJs.getValue();
    if (cmPhp) document.getElementById('hidden-php').value = cmPhp.getValue();
  };
});
</script>

<style>
/* Tema oscuro para TinyMCE */
.tox-tinymce { border: 1px solid var(--border) !important; border-radius: var(--radius-sm) !important; }
.tox .tox-toolbar__primary, .tox .tox-toolbar__overflow, .tox .tox-menubar { background: var(--bg-card-2) !important; }
.tox .tox-tbtn { color: var(--text-secondary) !important; }
.tox .tox-tbtn:hover { background: var(--bg-hover) !important; color: var(--accent) !important; }
.tox .tox-tbtn--enabled { background: var(--bg-hover) !important; color: var(--accent) !important; }
.tox .tox-statusbar { background: var(--bg-card) !important; color: var(--text-muted) !important; border-top: 1px solid var(--border) !important; }
.tox .tox-statusbar a { color: var(--accent) !important; }
.tox .tox-menu { background: var(--bg-card) !important; border: 1px solid var(--border) !important; }
.tox .tox-collection__item { color: var(--text-secondary) !important; }
.tox .tox-collection__item--active { background: var(--bg-hover) !important; color: var(--accent) !important; }
.tox .tox-dialog { background: var(--bg-card) !important; color: var(--text-secondary) !important; border: 1px solid var(--border) !important; }
.tox .tox-dialog__title { color: var(--text-primary) !important; }
.tox .tox-label, .tox .tox-label~.tox-control-wrap { color: var(--text-secondary) !important; }
.tox .tox-textfield, .tox .tox-textarea { background: var(--bg-input) !important; color: var(--text-secondary) !important; border: 1px solid var(--border) !important; }
.tox .tox-button { background: var(--accent) !important; border-color: var(--accent) !important; color: var(--bg-principal) !important; }
.tox .tox-button:hover { background: var(--accent-dark) !important; }
.tox .tox-button--secondary { background: var(--bg-card-2) !important; color: var(--text-secondary) !important; }
</style>
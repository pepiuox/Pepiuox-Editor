<?php $pageTitle = 'Generador de Meta Description'; ?>

<div class="d-flex justify-between align-center mb-3 flex-wrap gap-2">
  <h2 class="mb-0"><i class="bi bi-magic text-primary"></i> Generador Automático de Meta Description</h2>
  <a class="btn btn-ghost" href="<?= url("admin/pages/{$page['id']}/edit") ?>"><i class="bi bi-arrow-left"></i> Volver al editor</a>
</div>

<div class="row g-4">
  <!-- Columna izquierda: Configuración -->
  <div class="col-lg-5">
    <div class="card">
      <div class="card-header"><h3><i class="bi bi-gear"></i> Configuración</h3></div>
      <div class="card-body">
        <div class="form-group">
          <label class="form-label">Título de la página</label>
          <input type="text" id="genTitle" class="form-control" value="<?= e($page['title']) ?>">
        </div>
        <div class="form-group">
          <label class="form-label">Keyword principal (opcional)</label>
          <input type="text" id="genKeyword" class="form-control" placeholder="Ej: cms profesional">
          <small class="form-text">Si se proporciona, se priorizará en las descripciones.</small>
        </div>
        <button class="btn btn-primary btn-block" id="btnGenerate">
          <i class="bi bi-magic"></i> Generar 3 variantes
        </button>
      </div>
    </div>

    <div class="card">
      <div class="card-header"><h3><i class="bi bi-info-circle"></i> Mejores prácticas</h3></div>
      <div class="card-body text-small">
        <ul style="list-style:disc;padding-left:1.25rem;color:var(--text-secondary)">
          <li>Longitud ideal: <strong>120-160 caracteres</strong></li>
          <li>Incluir la <strong>keyword principal</strong> naturalmente</li>
          <li>Terminar con una <strong>llamada a la acción</strong></li>
          <li>Ser <strong>única</strong> para cada página</li>
          <li>Evitar comillas dobles y caracteres especiales</li>
        </ul>
      </div>
    </div>
  </div>

  <!-- Columna derecha: Resultados -->
  <div class="col-lg-7">
    <div id="results">
      <div class="card">
        <div class="card-body text-center text-muted p-5">
          <i class="bi bi-magic" style="font-size:3rem"></i>
          <p class="mt-2">Haz clic en "Generar" para crear 3 variantes de meta description.</p>
        </div>
      </div>
    </div>
  </div>
</div>

<script>
document.getElementById('btnGenerate').addEventListener('click', async () => {
  const btn = document.getElementById('btnGenerate');
  btn.disabled = true;
  btn.innerHTML = '<i class="bi bi-hourglass-split"></i> Generando...';
  
  const fd = new FormData();
  fd.append('title', document.getElementById('genTitle').value);
  fd.append('keyword', document.getElementById('genKeyword').value);
  fd.append('_csrf_token', document.querySelector('input[name="_csrf_token"]')?.value || '');
  
  try {
    const r = await fetch('<?= url("admin/seo/{$page['id']}/generate-meta") ?>', {
      method: 'POST', body: fd, headers: {'X-Requested-With':'XMLHttpRequest'}
    });
    const d = await r.json();
    
    if (!d.success) throw new Error(d.message || 'Error');
    
    const results = document.getElementById('results');
    results.innerHTML = d.data.map((v, i) => `
      <div class="card card-accent mb-3">
        <div class="card-header">
          <h3>
            <span class="badge badge-${v.score >= 70 ? 'success' : (v.score >= 50 ? 'warning' : 'danger')} me-2">
              Score: ${v.score}
            </span>
            ${v.label}
          </h3>
          <span class="text-small text-muted">${v.length} caracteres</span>
        </div>
        <div class="card-body">
          <div class="google-result mb-3">
            <div class="google-url"><?= e(BASE_URL . '/' . $page['slug']) ?></div>
            <div class="google-title"><?= e($page['title']) ?></div>
            <div class="google-desc">${escapeHtml(v.text)}</div>
          </div>
          <div class="d-flex gap-2">
            <button class="btn btn-ghost btn-sm" onclick="copyText(\`${escapeJs(v.text)}\`)">
              <i class="bi bi-clipboard"></i> Copiar
            </button>
            <button class="btn btn-primary btn-sm" onclick="applyToPage(\`${escapeJs(v.text)}\`)">
              <i class="bi bi-check"></i> Aplicar a la página
            </button>
          </div>
        </div>
      </div>
    `).join('');
  } catch(e) {
    document.getElementById('results').innerHTML = `
      <div class="alert alert-danger"><i class="bi bi-exclamation-triangle"></i> ${e.message}</div>
    `;
  } finally {
    btn.disabled = false;
    btn.innerHTML = '<i class="bi bi-magic"></i> Generar 3 variantes';
  }
});

function copyText(t) {
  navigator.clipboard.writeText(t).then(() => toast('Copiado al portapapeles'));
}

async function applyToPage(text) {
  if (!confirm('¿Aplicar esta descripción a la página?')) return;
  const fd = new FormData();
  fd.append('seo_description', text);
  fd.append('_csrf_token', document.querySelector('input[name="_csrf_token"]')?.value || '');
  const r = await fetch('<?= url("admin/seo/{$page['id']}/apply-meta") ?>', {
    method: 'POST', body: fd, headers: {'X-Requested-With':'XMLHttpRequest'}
  });
  const d = await r.json();
  if (d.success) {
    toast('Meta description aplicada correctamente', 'success');
    setTimeout(() => location.href = '<?= url("admin/pages/{$page['id']}/edit") ?>', 1000);
  } else {
    toast(d.message || 'Error al aplicar', 'error');
  }
}

function escapeHtml(s) { const d = document.createElement('div'); d.textContent = s; return d.innerHTML; }
function escapeJs(s) { return s.replace(/`/g, '\\`').replace(/\$/g, '\\$'); }
</script>
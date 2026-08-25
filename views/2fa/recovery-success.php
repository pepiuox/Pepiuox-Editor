<?php
$pageTitle = '2FA Activado - Códigos de Recuperación';
?>
<div class="container" style="max-width:700px;margin:2rem auto;padding:1.5rem">
  <div class="card">
    <div class="card-header">
      <h3><i class="bi bi-shield-check text-success"></i> ¡2FA Activado Correctamente!</h3>
    </div>
    <div class="card-body">
      <div class="alert alert-success">
        <i class="bi bi-check-circle"></i>
        <strong>La autenticación en dos pasos está ahora activa en tu cuenta.</strong>
      </div>

      <div class="alert alert-warning">
        <i class="bi bi-exclamation-triangle"></i>
        <strong>¡IMPORTANTE!</strong> Estos códigos de recuperación se muestran <strong>UNA SOLA VEZ</strong>. 
        Guárdalos en un lugar seguro (gestor de contraseñas, caja fuerte, etc.). 
        Los necesitarás si pierdes acceso a tu app autenticadora.
      </div>

      <!-- ═══ CÓDIGOS ═══ -->
      <div class="recovery-codes-grid" style="display:grid;grid-template-columns:repeat(2,1fr);gap:.75rem;margin:1.5rem 0;padding:1.25rem;background:var(--bg-principal);border:1px solid var(--border);border-radius:var(--radius)">
        <?php foreach ($formattedCodes as $i => $code): ?>
          <div class="recovery-code-item" style="display:flex;align-items:center;gap:.5rem;padding:.6rem .9rem;background:var(--bg-card);border:1px solid var(--border);border-radius:var(--radius-sm);font-family:var(--font-mono);font-size:1rem;letter-spacing:1px">
            <span style="color:var(--text-muted);font-size:.75rem;width:20px"><?= str_pad($i+1, 2, '0', STR_PAD_LEFT) ?></span>
            <code style="color:var(--accent);font-weight:600;flex:1"><?= e($code) ?></code>
            <button type="button" class="btn btn-ghost btn-sm" onclick="copyCode('<?= e($code) ?>')" title="Copiar" style="padding:2px 6px">
              <i class="bi bi-clipboard"></i>
            </button>
          </div>
        <?php endforeach; ?>
      </div>

      <!-- ═══ ACCIONES ═══ -->
      <div class="d-flex gap-2 flex-wrap" style="margin-top:1.5rem">
        <a href="<?= url('admin/2fa/download-codes') ?>" class="btn btn-primary">
          <i class="bi bi-download"></i> Descargar como TXT
        </a>
        <button type="button" class="btn btn-ghost" onclick="copyAllCodes()">
          <i class="bi bi-clipboard-check"></i> Copiar todos
        </button>
        <a href="<?= url('admin/profile') ?>" class="btn btn-ghost ms-auto">
          <i class="bi bi-check"></i> He guardado los códigos
        </a>
      </div>

      <div class="alert alert-info" style="margin-top:1.5rem;font-size:.85rem">
        <i class="bi bi-info-circle"></i>
        <strong>Consejos de seguridad:</strong>
        <ul style="margin:.5rem 0 0 1.25rem;line-height:1.8">
          <li>Cada código es de <strong>un solo uso</strong></li>
          <li>Tienes <strong><?= count($formattedCodes) ?> códigos</strong> en total</li>
          <li>Cuando te queden pocos, el sistema te pedirá regenerarlos</li>
          <li>Puedes regenerarlos en cualquier momento desde tu perfil</li>
          <li>Al usar un código, recibirás una notificación por email</li>
        </ul>
      </div>
    </div>
  </div>
</div>

<!-- Código oculto para copiar -->
<textarea id="allCodes" style="position:absolute;left:-9999px"><?= e(implode("\n", $formattedCodes)) ?></textarea>

<script>
function copyCode(code) {
  navigator.clipboard.writeText(code).then(() => {
    showToast('✓ Código copiado: ' + code, 'success');
  });
}

function copyAllCodes() {
  const textarea = document.getElementById('allCodes');
  textarea.select();
  document.execCommand('copy');
  showToast('✓ Todos los códigos copiados al portapapeles', 'success');
}

function showToast(msg, type) {
  const c = document.querySelector('.toast-container') || (() => {
    const d = document.createElement('div');
    d.className = 'toast-container';
    document.body.appendChild(d);
    return d;
  })();
  const t = document.createElement('div');
  t.className = 'toast toast-' + type;
  t.innerHTML = msg;
  c.appendChild(t);
  setTimeout(() => { t.style.opacity = '0'; setTimeout(() => t.remove(), 300); }, 3000);
}
</script>
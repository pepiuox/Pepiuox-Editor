<?php
$remainingWarning = \App\Core\Session::get('2fa_recovery_warning');
?>
<div class="login-container">
  <div class="login-right" style="flex:1">
    <div class="login-form-wrapper">
      <h2 class="form-title"><i class="bi bi-shield-lock"></i> Verificación en dos pasos</h2>
      <p class="form-subtitle">Ingresa el código de tu app autenticadora o un código de recuperación.</p>

      <?php foreach (get_flash() as $type => $msg): ?>
        <div class="alert alert-<?= $type==='error'?'danger':$type ?>"><?= $msg ?></div>
      <?php endforeach; ?>

      <!-- ═══ PESTAÑAS: TOTP vs Recuperación ═══ -->
      <div class="nav-tabs mb-3">
        <button type="button" class="nav-link active" data-mode="totp" onclick="switch2FAMode('totp')">
          <i class="bi bi-phone"></i> App autenticadora
        </button>
        <button type="button" class="nav-link" data-mode="recovery" onclick="switch2FAMode('recovery')">
          <i class="bi bi-key"></i> Código de recuperación
        </button>
      </div>

      <!-- ═══ FORMULARIO TOTP ═══ -->
      <form method="post" action="<?= url('2fa/challenge/verify') ?>" id="formTotp">
        <?= \App\Core\CSRF::field() ?>
        <input type="hidden" name="mode" value="totp">
        <div class="form-group">
          <label class="form-label">Código de 6 dígitos</label>
          <input type="text" name="code" class="form-control text-mono text-large text-center" 
                 maxlength="6" pattern="\d{6}" inputmode="numeric" autocomplete="one-time-code"
                 placeholder="000000" autofocus>
          <small class="form-text">Abre tu app (Google Authenticator, Authy, etc.) e ingresa el código actual.</small>
        </div>
        <!-- ═══ NUEVO: Checkbox para confiar en el dispositivo ═══ -->
      <label class="form-check mt-3 mb-3">
        <input type="checkbox" name="trust_device" value="1" class="form-check-input">
        <span class="form-check-label">
          <i class="bi bi-laptop"></i> Confiar en este dispositivo durante 30 días
        </span>
        <small class="form-text d-block mt-1">
          Marca esta opción solo si estás en tu dispositivo personal. No lo marques en computadoras públicas.
        </small>
      </label>
        <button type="submit" class="btn btn-primary btn-block">Verificar</button>
      </form>
<!-- ═══ NUEVO: Checkbox para confiar en el dispositivo ═══ -->
<div class="card card-accent mt-3 mb-3" style="border-left:4px solid var(--accent)">
  <div class="card-body" style="padding:1rem">
    <label class="form-check mb-2">
      <input type="checkbox" name="trust_device" value="1" class="form-check-input">
      <span class="form-check-label">
        <i class="bi bi-laptop text-primary"></i> 
        <strong>Confiar en este dispositivo durante 30 días</strong>
      </span>
    </label>
    <small class="form-text d-block">
      <i class="bi bi-info-circle"></i> 
      Al marcar esta opción, no necesitarás ingresar el código 2FA en este dispositivo 
      durante los próximos 30 días. 
      <strong class="text-warning">Recibirás un email de confirmación</strong> como alerta de seguridad.
    </small>
  </div>
</div>
      <!-- ═══ FORMULARIO RECUPERACIÓN ═══ -->
      <form method="post" action="<?= url('2fa/challenge/verify') ?>" id="formRecovery" style="display:none">
        <?= \App\Core\CSRF::field() ?>
        <input type="hidden" name="mode" value="recovery">
        <div class="form-group">
          <label class="form-label">Código de recuperación</label>
          <input type="text" name="recovery_code" class="form-control text-mono text-large text-center" 
                 maxlength="9" placeholder="XXXX-XXXX" autocomplete="off"
                 style="text-transform:uppercase;letter-spacing:2px">
          <small class="form-text">
            Ingresa uno de los códigos de 8 caracteres que guardaste al activar 2FA. 
            Cada código es de un solo uso.
          </small>
        </div>
        <div class="alert alert-warning" style="font-size:.85rem">
          <i class="bi bi-exclamation-triangle"></i>
          <strong>Importante:</strong> Al usar un código de recuperación, recibirás una notificación por email y el código quedará invalidado.
        </div>
        <button type="submit" class="btn btn-warning btn-block">Usar código de recuperación</button>
      </form>

      <div class="login-footer">
        <a href="<?= url('login') ?>">← Volver al login</a>
      </div>
    </div>
  </div>
</div>

<script>
function switch2FAMode(mode) {
  // Actualizar pestañas
  document.querySelectorAll('.nav-tabs .nav-link').forEach(btn => {
    btn.classList.toggle('active', btn.dataset.mode === mode);
  });
  
  // Mostrar/ocultar formularios
  document.getElementById('formTotp').style.display = mode === 'totp' ? 'block' : 'none';
  document.getElementById('formRecovery').style.display = mode === 'recovery' ? 'block' : 'none';
  
  // Enfocar el input correspondiente
  setTimeout(() => {
    if (mode === 'totp') {
      document.querySelector('#formTotp input[name=code]').focus();
    } else {
      document.querySelector('#formRecovery input[name=recovery_code]').focus();
    }
  }, 100);
}

// Auto-formatear código de recuperación (agregar guion)
document.querySelector('#formRecovery input[name=recovery_code]').addEventListener('input', function(e) {
  let v = e.target.value.toUpperCase().replace(/[^A-Z0-9]/g, '');
  if (v.length > 4) {
    v = v.slice(0, 4) + '-' + v.slice(4, 8);
  }
  e.target.value = v;
});
</script>
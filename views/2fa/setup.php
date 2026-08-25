<?php $pageTitle = 'Configurar 2FA'; ?>
<div class="card">
  <div class="card-header"><h3><i class="bi bi-shield-lock"></i> Configurar autenticación en dos pasos</h3></div>
  <div class="card-body">
    <div class="row g-4">
      <div class="col-md-6">
        <h5>1. Escanea el código QR</h5>
        <p class="text-muted text-small">Usa Google Authenticator, Authy, 1Password u otra app TOTP.</p>
        <div class="p-3 bg-card-2 rounded text-center">
          <img src="https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=<?= urlencode($qr) ?>" alt="QR" style="background:#fff;padding:8px;border-radius:8px">
        </div>
        <h5 class="mt-3">O introduce la clave manualmente:</h5>
        <div class="code-block">
          <div class="code-header"><span>Clave secreta</span></div>
          <pre><?= e($secret) ?></pre>
        </div>
      </div>
      <div class="col-md-6">
        <h5>2. Verifica el código</h5>
        <p class="text-muted text-small">Introduce el código de 6 dígitos que muestra tu app para confirmar.</p>
        <form method="post" action="<?= url('admin/2fa/verify') ?>">
          <?= \App\Core\CSRF::field() ?>
          <div class="form-group">
            <label class="form-label">Código de 6 dígitos</label>
            <input type="text" name="code" class="form-control text-mono text-large text-center" maxlength="6" pattern="\d{6}" required autofocus autocomplete="one-time-code">
          </div>
          <button class="btn btn-primary btn-block"><i class="bi bi-check-circle"></i> Activar 2FA</button>
        </form>
        <div class="alert alert-warning mt-3">
          <i class="bi bi-exclamation-triangle"></i> Guarda la clave secreta en un lugar seguro. Si pierdes el acceso a tu app, no podrás entrar sin ella.
        </div>
      </div>
    </div>
  </div>
</div>
<?php // views/auth/reset.php ?>
<?php $cfg = require __DIR__ . '/../../config/config.php'; ?>
<div class="auth-container">
  <div class="auth-box">
    <div class="auth-header">
      <h2><i class="bi bi-shield-lock"></i> Restablecer Contraseña</h2>
      <p>Ingresa tu nueva contraseña.</p>
    </div>

    <?php foreach (get_flash() as $type => $msg): ?>
      <div class="alert alert-<?= $type === 'error' ? 'danger' : ($type === 'success' ? 'success' : $type) ?>">
        <?= $msg ?>
      </div>
    <?php endforeach; ?>

    <form method="post" action="<?= url('reset') ?>">
      <?= \App\Core\CSRF::field() ?>
      
      <input type="hidden" name="token" value="<?= e($token ?? '') ?>">
      <input type="hidden" name="email" value="<?= e($email ?? '') ?>">

      <div class="form-group">
        <label class="form-label">Nueva Contraseña</label>
        <input type="password" name="password" class="form-control" 
               required minlength="8" maxlength="72">
        <small class="text-muted">Mínimo 8 caracteres. Debe incluir mayúscula, minúscula, número y carácter especial.</small>
      </div>

      <div class="form-group">
        <label class="form-label">Repetir Contraseña</label>
        <input type="password" name="rpassword" class="form-control" 
               required minlength="8" maxlength="72">
      </div>

      <button type="submit" class="btn btn-primary w-100">
        <i class="bi bi-check-circle"></i> Restablecer Contraseña
      </button>
    </form>

    <div class="auth-footer text-center mt-3">
      <a href="<?= url('login') ?>">← Volver al inicio de sesión</a>
    </div>
  </div>
</div>
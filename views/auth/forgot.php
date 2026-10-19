<?php // views/auth/forgot.php ?>
<?php $cfg = require __DIR__ . '/../../config/config.php'; ?>
 <div class="login-right" style="flex:1">
    <div class="login-form-wrapper">
  <div class="auth-box">
    <div class="auth-header">
      <h2><i class="bi bi-key"></i> Recuperar Contraseña</h2>
      <p>Ingresa tu correo electrónico para recibir un enlace de recuperación.</p>
    </div>

    <?php foreach (get_flash() as $type => $msg): ?>
      <div class="alert alert-<?= $type === 'error' ? 'danger' : ($type === 'success' ? 'success' : $type) ?>">
        <?= $msg ?>
      </div>
    <?php endforeach; ?>

    <form method="post" action="<?= url('forgot') ?>">
      <?= \App\Core\CSRF::field() ?>
      
      <div class="form-group">
        <label class="form-label">Correo Electrónico</label>
        <input type="email" name="email" class="form-control" 
               value="<?= e($_POST['email'] ?? '') ?>" 
               required placeholder="tu@email.com">
      </div>

      <button type="submit" class="btn btn-primary w-100">
        <i class="bi bi-envelope"></i> Enviar Enlace de Recuperación
      </button>
    </form>

    <div class="auth-footer text-center mt-3">
      <a href="<?= url('login') ?>">← Volver al inicio de sesión</a>
    </div>
  </div>
</div>
    </div>
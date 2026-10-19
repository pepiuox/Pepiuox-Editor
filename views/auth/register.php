<?php // views/auth/register.php ?>
<?php // $cfg = require __DIR__ . '/../../config/config.php'; ?>
<div class="login-container">
  <div class="login-left">
    <div class="register-content">
      <div class="register-brand"><i class="bi bi-code-slash"></i> <?= e($cfg['app_name'] ?? 'CMS') ?></div>
      <p>CMS profesional con editor visual y de código.</p>
      <div class="feature-item"><div class="icon"><i class="bi bi-palette"></i></div>
        <div><h5>Editor visual</h5><p>GrapesJS con drag & drop.</p></div></div>
      <div class="feature-item"><div class="icon"><i class="bi bi-braces"></i></div>
        <div><h5>Editor de código</h5><p>CodeMirror con resaltado.</p></div></div>
      <div class="feature-item"><div class="icon"><i class="bi bi-shield-lock"></i></div>
        <div><h5>Seguro</h5><p>CSRF, PDO, sesiones protegidas.</p></div></div>
    </div>
  </div>
  <div class="login-right">
    <div class="register-form-wrapper">
      <h2 class="form-title">Crear Cuenta</h2>
      <p class="form-subtitle">Regístrate para comenzar</p>

      <?php foreach (get_flash() as $type => $msg): ?>
        <div class="alert alert-<?= $type === 'error' ? 'danger' : ($type === 'success' ? 'success' : $type) ?>">
          <?= $msg ?>
        </div>
      <?php endforeach; ?>

      <form class="register-form" method="post" action="<?= url('register') ?>">
        <?= \App\Core\CSRF::field() ?>
        
        <div class="form-group">
          <label class="form-label">Usuario</label>
          <input type="text" name="username" class="form-control" 
                 value="<?= e($_POST['username'] ?? '') ?>" 
                 required autofocus minlength="3" maxlength="50"
                 pattern="[a-zA-Z0-9_\-]+" 
                 title="Solo letras, números, guiones y guiones bajos">
          <small class="text-muted">Mínimo 3 caracteres, solo letras, números, guiones y guiones bajos.</small>
        </div>

        <div class="form-group">
          <label class="form-label">E-mail</label>
          <input type="email" name="email" class="form-control" 
                 value="<?= e($_POST['email'] ?? '') ?>" 
                 required maxlength="100">
        </div>

        <div class="form-group">
          <label class="form-label">Contraseña</label>
          <input type="password" name="password" class="form-control" 
                 required minlength="8" maxlength="72">
          <small class="text-muted">Mínimo 8 caracteres. Debe incluir mayúscula, minúscula, número y carácter especial.</small>
        </div>

        <div class="form-group">
          <label class="form-label">Repetir Contraseña</label>
          <input type="password" name="rpassword" class="form-control" 
                 required minlength="8" maxlength="72">
        </div>

        <div class="form-check mb-3">
          <input type="checkbox" name="remember" class="form-check-input" value="1" 
                 <?= isset($_POST['remember']) ? 'checked' : '' ?>>
          <label class="form-check-label">Recordarme</label>
        </div>

        <button type="submit" class="btn btn-primary btn-block w-100">
          <i class="bi bi-person-plus"></i> Registrarse
        </button>
      </form>

      <div class="register-footer mt-3 text-center">
        <a href="<?= url('login') ?>">¿Ya tienes cuenta? Inicia sesión</a>
        <span class="mx-2">|</span>
        <a href="<?= url('forgot') ?>">¿Olvidaste tu contraseña?</a>
      </div>
    </div>
  </div>
</div>
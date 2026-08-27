<?php $cfg = require __DIR__ . '/../../config/config.php'; ?>
<div class="login-container">
  <div class="login-left">
    <div class="login-content">
      <div class="login-brand"><i class="bi bi-code-slash"></i> <?= e($cfg['app_name']) ?></div>
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
    <div class="login-form-wrapper">
      <h2 class="form-title">Bienvenido</h2>
      <p class="form-subtitle">Inicia sesión para continuar</p>

      <?php foreach (get_flash() as $type => $msg): ?>
        <div class="alert alert-<?= $type==='error'?'danger':$type ?>"><?= $msg ?></div>
      <?php endforeach; ?>

      <form class="login-form" method="post" action="<?= url('login') ?>">
        <?= \App\Core\CSRF::field() ?>
        <div class="form-group">
          <label class="form-label">Usuario</label>
          <input type="text" name="username" class="form-control" required autofocus>
        </div>
        <div class="form-group">
          <label class="form-label">Contraseña</label>
          <input type="password" name="password" class="form-control" required>
        </div>
        <label class="form-check">
          <input type="checkbox" name="remember" class="form-check-input" value="1">
          <span class="form-check-label">Recuérdame</span>
        </label>
        <button type="submit" class="btn btn-primary btn-block mt-3">Entrar</button>
      </form>
      <div class="login-footer">
        <a href="<?= url('forgot') ?>">¿Olvidaste tu contraseña?</a>
        <a href="<?= url('register') ?>">¿Registrate y se un usuario?</a>
      </div>
    </div>
  </div>
</div>
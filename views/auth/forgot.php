<div class="login-container">
  <div class="login-right" style="flex:1">
    <div class="login-form-wrapper">
      <h2 class="form-title">¿Olvidaste tu contraseña?</h2>
      <p class="form-subtitle">Introduce tu email y te enviaremos un enlace para restablecerla.</p>

      <?php foreach (get_flash() as $type => $msg): ?>
        <div class="alert alert-<?= $type==='error'?'danger':$type ?>"><?= $msg ?></div>
      <?php endforeach; ?>

      <form method="post" action="<?= url('forgot') ?>">
        <?= \App\Core\CSRF::field() ?>
        <div class="form-group">
          <label class="form-label">Email</label>
          <input type="email" name="email" class="form-control" required autofocus>
        </div>
        <button class="btn btn-primary btn-block">Enviar enlace</button>
      </form>
      <div class="login-footer">
        <a href="<?= url('login') ?>">← Volver al login</a>
      </div>
    </div>
  </div>
</div>
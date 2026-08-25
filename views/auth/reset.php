<div class="login-container">
  <div class="login-right" style="flex:1">
    <div class="login-form-wrapper">
      <h2 class="form-title">Nueva contraseña</h2>
      <p class="form-subtitle">Introduce tu nueva contraseña.</p>

      <?php foreach (get_flash() as $type => $msg): ?>
        <div class="alert alert-<?= $type==='error'?'danger':$type ?>"><?= $msg ?></div>
      <?php endforeach; ?>

      <form method="post" action="<?= url('reset') ?>">
        <?= \App\Core\CSRF::field() ?>
        <input type="hidden" name="token" value="<?= e($token) ?>">
        <div class="form-group">
          <label class="form-label">Nueva contraseña</label>
          <input type="password" name="password" class="form-control" required minlength="8">
        </div>
        <div class="form-group">
          <label class="form-label">Confirmar contraseña</label>
          <input type="password" name="password_confirmation" class="form-control" required minlength="8">
        </div>
        <button class="btn btn-primary btn-block">Guardar contraseña</button>
      </form>
    </div>
  </div>
</div>
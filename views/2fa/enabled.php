<?php
$pageTitle = '2FA Activado';
$remainingColor = $remaining <= 2 ? 'danger' : ($remaining <= 5 ? 'warning' : 'success');
?>
<div class="row g-4">
  <div class="col-lg-8">
    <div class="card">
      <div class="card-header">
        <h3><i class="bi bi-shield-check text-success"></i> Autenticación en dos pasos</h3>
      </div>
      <div class="card-body">
        <div class="alert alert-success">
          <i class="bi bi-check-circle"></i>
          <strong>2FA está activo en tu cuenta.</strong> Cada vez que inicies sesión, necesitarás un código de tu app autenticadora.
        </div>

        <!-- Estado de códigos de recuperación -->
        <div class="card card-accent" style="margin-top:1.5rem">
          <div class="card-body">
            <div class="d-flex justify-between align-center flex-wrap gap-2">
              <div>
                <h5 style="margin:0 0 .25rem 0"><i class="bi bi-key"></i> Códigos de recuperación</h5>
                <p class="text-muted text-small" style="margin:0">
                  Úsalos si pierdes acceso a tu app autenticadora. Cada código es de un solo uso.
                </p>
              </div>
              <div class="text-center">
                <div style="font-size:2rem;font-weight:700;color:var(--<?= $remainingColor ?>)">
                  <?= $remaining ?>
                </div>
                <div class="text-small text-muted">códigos restantes</div>
              </div>
            </div>

            <?php if ($remaining <= 2): ?>
              <div class="alert alert-danger" style="margin-top:1rem">
                <i class="bi bi-exclamation-octagon"></i>
                <strong>¡Atención!</strong> Te quedan muy pocos códigos. Regenera tus códigos ahora para no perder el acceso a tu cuenta.
              </div>
            <?php endif; ?>

            <div class="d-flex gap-2 flex-wrap" style="margin-top:1rem">
              <form method="post" action="<?= url('admin/2fa/regenerate-codes') ?>" onsubmit="return confirm('¿Regenerar códigos? Los anteriores quedarán invalidados.')">
                <?= csrf_field() ?>
                <input type="hidden" name="confirm" value="1">
                <button type="submit" class="btn btn-warning">
                  <i class="bi bi-arrow-repeat"></i> Regenerar códigos
                </button>
              </form>
            </div>
          </div>
        </div>

        <!-- Desactivar 2FA -->
        <div style="margin-top:2rem;padding-top:1.5rem;border-top:1px solid var(--border)">
          <h5><i class="bi bi-shield-x text-danger"></i> Desactivar 2FA</h5>
          <p class="text-muted text-small">
            Al desactivar 2FA, tu cuenta será menos segura y todos tus códigos de recuperación serán invalidados.
          </p>
          <form method="post" action="<?= url('admin/2fa/disable') ?>" onsubmit="return confirm('¿Seguro que deseas desactivar 2FA? Esta acción reduce la seguridad de tu cuenta.')">
            <?= csrf_field() ?>
            <button type="submit" class="btn btn-danger">
              <i class="bi bi-shield-x"></i> Desactivar 2FA
            </button>
          </form>
        </div>
      </div>
    </div>
  </div>

  <!-- Historial de uso -->
  <div class="col-lg-4">
    <div class="card">
      <div class="card-header">
        <h3><i class="bi bi-clock-history"></i> Historial de uso</h3>
      </div>
      <div class="card-body p-0">
        <?php if (empty($history)): ?>
          <div class="text-center text-muted p-3">
            <i class="bi bi-check-circle" style="font-size:2rem"></i>
            <p class="mt-2 mb-0 text-small">Ningún código de recuperación ha sido usado.</p>
          </div>
        <?php else: ?>
          <div class="timeline" style="padding:1rem">
            <?php foreach ($history as $h): ?>
              <div class="timeline-item">
                <span class="time"><?= e($h['used_at']) ?></span>
                <div class="content">
                  <strong>Código usado</strong>
                  <br><small class="text-muted">
                    <i class="bi bi-geo-alt"></i> <?= e($h['used_ip'] ?? 'IP desconocida') ?>
                  </small>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>
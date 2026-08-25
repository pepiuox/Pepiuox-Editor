<?php $pageTitle = 'Historial de baneos: ' . $user['username']; ?>

<div class="d-flex justify-between align-center mb-3 flex-wrap gap-2">
  <h2 class="mb-0"><i class="bi bi-clock-history text-primary"></i> Historial de <?= e($user['username']) ?></h2>
  <a class="btn btn-ghost" href="<?= url('admin/users/banned') ?>"><i class="bi bi-arrow-left"></i> Volver</a>
</div>

<!-- Info del usuario -->
<div class="card card-accent mb-4">
  <div class="card-body">
    <div class="d-flex align-center gap-3 flex-wrap">
      <div class="avatar" style="width:60px;height:60px;border-radius:50%;background:rgba(0,212,170,0.15);color:var(--accent);display:grid;place-items:center;font-weight:700;font-size:1.5rem">
        <?= e(strtoupper(mb_substr($user['full_name'] ?? $user['username'], 0, 1))) ?>
      </div>
      <div class="flex-grow-1">
        <h4 class="mb-1"><?= e($user['full_name'] ?? $user['username']) ?></h4>
        <div class="d-flex gap-3 flex-wrap text-small text-muted">
          <span><i class="bi bi-at"></i> @<?= e($user['username']) ?></span>
          <span><i class="bi bi-envelope"></i> <?= e($user['email']) ?></span>
          <span><i class="bi bi-shield"></i> <?= e($user['role']) ?></span>
          <span>
            Estado: 
            <?php if ($user['is_banned']): ?>
              <span class="badge badge-danger"><i class="bi bi-person-x"></i> Baneado</span>
            <?php else: ?>
              <span class="badge badge-success"><i class="bi bi-person-check"></i> Activo</span>
            <?php endif; ?>
          </span>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Timeline de historial -->
<div class="card">
  <div class="card-header">
    <h3><i class="bi bi-clock-history"></i> Historial completo</h3>
    <span class="badge badge-info"><?= count($history) ?> registros</span>
  </div>
  <div class="card-body">
    <?php if (empty($history)): ?>
      <div class="text-center text-muted p-4">
        <i class="bi bi-inbox" style="font-size:2.5rem"></i>
        <p class="mt-2">Sin historial de baneos.</p>
      </div>
    <?php else: ?>
      <div class="timeline">
        <?php foreach ($history as $h): 
          $icon = match($h['action']) {
              'banned'   => ['person-x', 'danger'],
              'unbanned' => ['person-check', 'success'],
              'extended' => ['clock', 'warning'],
              'modified' => ['pencil', 'info'],
              default    => ['circle', 'muted'],
          };
        ?>
        <div class="timeline-item">
          <span class="time"><?= e($h['created_at']) ?></span>
          <div class="content">
            <div class="d-flex align-center gap-2 mb-1">
              <span class="badge badge-<?= $icon[1] ?>">
                <i class="bi bi-<?= $icon[0] ?>"></i> <?= strtoupper($h['action']) ?>
              </span>
              <?php if ($h['performed_by_name']): ?>
                <small class="text-muted">por <strong><?= e($h['performed_by_name']) ?></strong></small>
              <?php endif; ?>
              <?php if ($h['ip_address']): ?>
                <small class="text-muted"><i class="bi bi-geo-alt"></i> <?= e($h['ip_address']) ?></small>
              <?php endif; ?>
            </div>
            
            <?php if ($h['reason']): ?>
              <div class="mt-1"><strong>Motivo:</strong> <?= e($h['reason']) ?></div>
            <?php endif; ?>
            
            <?php if ($h['previous_reason']): ?>
              <div class="mt-1 text-small text-muted"><strong>Motivo anterior:</strong> <?= e($h['previous_reason']) ?></div>
            <?php endif; ?>
            
            <?php if ($h['duration_days']): ?>
              <div class="mt-1 text-small">
                <i class="bi bi-clock"></i> Duración: 
                <?= $h['duration_days'] ?> días 
                <?php if ($h['expires_at']): ?>(hasta <?= e(date('d/m/Y H:i', strtotime($h['expires_at']))) ?>)<?php endif; ?>
              </div>
            <?php elseif ($h['action'] === 'banned'): ?>
              <div class="mt-1 text-small text-danger"><i class="bi bi-infinity"></i> Permanente</div>
            <?php endif; ?>
            
            <?php if ($h['notes']): ?>
              <div class="mt-2 p-2 bg-card-2 rounded text-small">
                <i class="bi bi-sticky text-muted"></i> <?= e($h['notes']) ?>
              </div>
            <?php endif; ?>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>
</div>
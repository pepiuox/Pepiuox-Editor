<?php $pageTitle = 'Historial Global de Baneos'; ?>

<div class="d-flex justify-between align-center mb-3 flex-wrap gap-2">
  <h2 class="mb-0"><i class="bi bi-clock-history text-primary"></i> Historial Global de Baneos</h2>
  <a class="btn btn-ghost" href="<?= url('admin/users/banned') ?>"><i class="bi bi-arrow-left"></i> Volver</a>
</div>

<div class="card">
  <div class="card-header">
    <h3><i class="bi bi-journal-text"></i> Todos los baneos y desbaneos</h3>
    <span class="badge badge-info"><?= count($history) ?> registros</span>
  </div>
  <div class="card-body p-0">
    <?php if (empty($history)): ?>
      <div class="text-center text-muted p-5">Sin registros.</div>
    <?php else: ?>
      <div class="table-responsive">
        <table class="table">
          <thead>
            <tr>
              <th>Fecha</th>
              <th>Acción</th>
              <th>Usuario</th>
              <th>Realizado por</th>
              <th>Motivo</th>
              <th>Duración</th>
              <th>IP</th>
            </tr>
          </thead>
          <tbody>
          <?php foreach ($history as $h): 
            $badge = match($h['action']) {
                'banned'   => 'danger',
                'unbanned' => 'success',
                'extended' => 'warning',
                default    => 'info',
            };
          ?>
            <tr>
              <td><small><?= e($h['created_at']) ?></small></td>
              <td><span class="badge badge-<?= $badge ?>"><?= strtoupper($h['action']) ?></span></td>
              <td>
                <strong><?= e($h['user_name'] ?? '—') ?></strong>
                <?php if ($h['user_name']): ?>
                  <br><a class="text-small" href="<?= url("admin/users/banned/{$h['user_id']}/history") ?>">Ver historial</a>
                <?php endif; ?>
              </td>
              <td><small><?= e($h['performed_by_name'] ?? 'Sistema') ?></small></td>
              <td><small><?= e(mb_substr($h['reason'] ?? $h['previous_reason'] ?? '—', 0, 50)) ?></small></td>
              <td>
                <?php if ($h['duration_days']): ?>
                  <small><?= $h['duration_days'] ?> días</small>
                <?php elseif ($h['action'] === 'banned'): ?>
                  <small class="text-danger">∞</small>
                <?php else: ?>
                  <small class="text-muted">—</small>
                <?php endif; ?>
              </td>
              <td><code class="text-small"><?= e($h['ip_address'] ?? '—') ?></code></td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php endif; ?>
  </div>
</div>
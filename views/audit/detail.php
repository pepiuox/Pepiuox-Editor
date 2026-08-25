<?php $pageTitle = 'Detalle de actividad #' . $log['id']; ?>

<div class="d-flex justify-between align-center mb-3">
  <h2><i class="bi bi-journal-text text-primary"></i> Detalle del registro</h2>
  <a class="btn btn-ghost" href="<?= url('admin/audit/activity') ?>"><i class="bi bi-arrow-left"></i> Volver</a>
</div>

<div class="card">
  <div class="card-body">
    <div class="form-grid">
      <div class="form-group">
        <label class="form-label">ID</label>
        <div><strong>#<?= $log['id'] ?></strong></div>
      </div>
      <div class="form-group">
        <label class="form-label">Fecha</label>
        <div><?= e($log['created_at']) ?></div>
      </div>
      <div class="form-group">
        <label class="form-label">Usuario</label>
        <div><?= e($log['username'] ?? 'Sistema') ?></div>
      </div>
      <div class="form-group">
        <label class="form-label">Acción</label>
        <div><span class="badge badge-info"><?= e($log['action']) ?></span></div>
      </div>
      <div class="form-group full-width">
        <label class="form-label">Descripción</label>
        <div><?= e($log['description']) ?></div>
      </div>
      <div class="form-group">
        <label class="form-label">IP</label>
        <div><code><?= e($log['ip_address'] ?? '—') ?></code></div>
      </div>
      <div class="form-group">
        <label class="form-label">Método</label>
        <div><span class="badge badge-ghost"><?= e($log['request_method'] ?? '—') ?></span></div>
      </div>
      <div class="form-group full-width">
        <label class="form-label">URL</label>
        <div><code><?= e($log['request_url'] ?? '—') ?></code></div>
      </div>
      <div class="form-group full-width">
        <label class="form-label">User Agent</label>
        <div class="text-small text-muted"><?= e($log['user_agent'] ?? '—') ?></div>
      </div>
    </div>
    
    <?php if ($oldData || $newData): ?>
    <hr>
    <div class="row g-3">
      <?php if ($oldData): ?>
      <div class="col-md-6">
        <h5><i class="bi bi-dash-circle text-danger"></i> Datos anteriores</h5>
        <div class="code-block">
          <pre><?= e(json_encode($oldData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)) ?></pre>
        </div>
      </div>
      <?php endif; ?>
      <?php if ($newData): ?>
      <div class="col-md-6">
        <h5><i class="bi bi-plus-circle text-success"></i> Datos nuevos</h5>
        <div class="code-block">
          <pre><?= e(json_encode($newData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)) ?></pre>
        </div>
      </div>
      <?php endif; ?>
    </div>
    <?php endif; ?>
  </div>
</div>
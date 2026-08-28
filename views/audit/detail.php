<?php
/**
 * Vista: Detalle de log de auditoría
 */
$log = $log ?? [];
$pageTitle = 'Detalle de auditoría #' . ($log['id'] ?? '');
?>

<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h3 class="mb-0">
            <i class="bi bi-file-earmark-text"></i>
            Detalle del registro #<?= (int)($log['id'] ?? 0) ?>
        </h3>
        <a href="<?= url('admin/audit') ?>" class="btn btn-ghost btn-sm">
            <i class="bi bi-arrow-left"></i> Volver
        </a>
    </div>
    <div class="card-body">
        <?php if (empty($log)): ?>
            <div class="alert alert-warning">
                <i class="bi bi-exclamation-triangle"></i> Registro no encontrado
            </div>
        <?php else: ?>
            <div class="row g-3">
                <div class="col-md-6">
                    <div class="border rounded p-3">
                        <h6 class="text-muted mb-3"><i class="bi bi-info-circle"></i> Información general</h6>
                        <dl class="row mb-0">
                            <dt class="col-sm-4">ID:</dt>
                            <dd class="col-sm-8">#<?= (int)$log['id'] ?></dd>

                            <dt class="col-sm-4">Fecha:</dt>
                            <dd class="col-sm-8"><?= e($log['created_at'] ?? '—') ?></dd>

                            <dt class="col-sm-4">Acción:</dt>
                            <dd class="col-sm-8">
                                <span class="badge bg-primary"><?= e($log['action'] ?? '—') ?></span>
                            </dd>

                            <dt class="col-sm-4">Contexto:</dt>
                            <dd class="col-sm-8"><?= e($log['context'] ?? '—') ?></dd>
                        </dl>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="border rounded p-3">
                        <h6 class="text-muted mb-3"><i class="bi bi-person"></i> Usuario</h6>
                        <dl class="row mb-0">
                            <dt class="col-sm-4">Usuario:</dt>
                            <dd class="col-sm-8">
                                <?php if (!empty($log['username'])): ?>
                                    <strong><?= e($log['username']) ?></strong>
                                    <br><small class="text-muted"><?= e($log['email'] ?? '') ?></small>
                                <?php else: ?>
                                    <span class="text-muted">Sistema</span>
                                <?php endif; ?>
                            </dd>

                            <dt class="col-sm-4">Rol:</dt>
                            <dd class="col-sm-8"><?= e($log['role'] ?? '—') ?></dd>

                            <dt class="col-sm-4">IP:</dt>
                            <dd class="col-sm-8"><code><?= e($log['ip_address'] ?? '—') ?></code></dd>
                        </dl>
                    </div>
                </div>
                <div class="col-12">
                    <div class="border rounded p-3">
                        <h6 class="text-muted mb-3"><i class="bi bi-card-text"></i> Detalles</h6>
                        <pre class="bg-light p-3 rounded mb-0" style="white-space:pre-wrap"><?= e($log['details'] ?? '(sin detalles)') ?></pre>
                    </div>
                </div>
                <div class="col-12">
                    <div class="border rounded p-3">
                        <h6 class="text-muted mb-3"><i class="bi bi-laptop"></i> User-Agent</h6>
                        <pre class="bg-light p-3 rounded mb-0 small" style="white-space:pre-wrap"><?= e($log['user_agent'] ?? '—') ?></pre>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>
<?php
/**
 * Vista: Panel de Auditoría
 */
$pageTitle   = 'Auditoría';
$logs        = $logs ?? [];
$total       = $total ?? 0;
$page        = $page ?? 1;
$limit       = $limit ?? 50;
$filters     = $filters ?? [];
$stats       = $stats ?? ['total' => 0, 'today' => 0, 'this_week' => 0, 'unique_users' => 0];
$users       = $users ?? [];
$actions     = $actions ?? [];
$totalPages  = max(1, (int)ceil($total / $limit));

// ✅ Helper para color de acción
if (!function_exists('_auditActionColor')) {
    function _auditActionColor(string $action): string {
        return match (true) {
            str_contains($action, 'login')    => 'info',
            str_contains($action, 'logout')   => 'secondary',
            str_contains($action, 'created')  => 'success',
            str_contains($action, 'deleted')  => 'danger',
            str_contains($action, 'updated')  => 'warning',
            str_contains($action, 'failed')   => 'danger',
            str_contains($action, 'cleaned')  => 'dark',
            default                           => 'primary',
        };
    }
}
?>

<!-- ═══ ESTADÍSTICAS ═══ -->
<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="stat-card">
            <div class="stat-icon"><i class="bi bi-journal-text"></i></div>
            <div class="stat-value"><?= number_format((int)($stats['total'] ?? 0)) ?></div>
            <div class="stat-label">Total registros</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card">
            <div class="stat-icon"><i class="bi bi-calendar-day"></i></div>
            <div class="stat-value"><?= number_format((int)($stats['today'] ?? 0)) ?></div>
            <div class="stat-label">Hoy</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card">
            <div class="stat-icon"><i class="bi bi-calendar-week"></i></div>
            <div class="stat-value"><?= number_format((int)($stats['this_week'] ?? 0)) ?></div>
            <div class="stat-label">Últimos 7 días</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card">
            <div class="stat-icon"><i class="bi bi-people"></i></div>
            <div class="stat-value"><?= number_format((int)($stats['unique_users'] ?? 0)) ?></div>
            <div class="stat-label">Usuarios únicos (30d)</div>
        </div>
    </div>
</div>

<!-- ═══ CARD PRINCIPAL ═══ -->
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
        <h3 class="mb-0"><i class="bi bi-shield-lock"></i> Registros de auditoría</h3>
        <div class="d-flex gap-2 flex-wrap">
            <a class="btn btn-ghost btn-sm"
               href="<?= url('admin/audit/export?' . http_build_query(array_filter($filters))) ?>">
                <i class="bi bi-download"></i> CSV
            </a>
            <a class="btn btn-ghost btn-sm"
               href="<?= url('admin/audit/export-json?' . http_build_query(array_filter($filters))) ?>">
                <i class="bi bi-filetype-json"></i> JSON
            </a>
            <form method="post" action="<?= url('admin/audit/clean') ?>"
                  onsubmit="return confirm('¿Eliminar registros antiguos? Esta acción no se puede deshacer.')">
                <?= \App\Core\CSRF::field() ?>
                <div class="d-flex gap-1">
                    <select name="days" class="form-select form-select-sm" style="width:auto">
                        <option value="30">30 días</option>
                        <option value="60">60 días</option>
                        <option value="90" selected>90 días</option>
                        <option value="180">180 días</option>
                    </select>
                    <button class="btn btn-warning btn-sm">
                        <i class="bi bi-trash"></i> Limpiar
                    </button>
                </div>
            </form>
        </div>
    </div>

    <div class="card-body">
        <!-- ═══ FILTROS ═══ -->
        <form method="get" action="<?= url('admin/audit') ?>" class="form-grid mb-3">
            <div class="form-group">
                <label class="form-label">Usuario</label>
                <select name="user_id" class="form-select">
                    <option value="">Todos</option>
                    <?php foreach ($users as $u): ?>
                        <option value="<?= (int)$u['id'] ?>"
                            <?= (($filters['user_id'] ?? '') == $u['id']) ? 'selected' : '' ?>>
                            <?= e($u['username']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label class="form-label">Acción</label>
                <select name="action" class="form-select">
                    <option value="">Todas</option>
                    <?php foreach ($actions as $a): ?>
                        <option value="<?= e($a) ?>"
                            <?= (($filters['action'] ?? '') === $a) ? 'selected' : '' ?>>
                            <?= e($a) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label class="form-label">Desde</label>
                <input type="date" name="date_from" class="form-control"
                       value="<?= e($filters['date_from'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label class="form-label">Hasta</label>
                <input type="date" name="date_to" class="form-control"
                       value="<?= e($filters['date_to'] ?? '') ?>">
            </div>
            <div class="form-group full-width">
                <label class="form-label">Buscar</label>
                <div class="d-flex gap-2">
                    <input type="text" name="search" class="form-control"
                           value="<?= e($filters['search'] ?? '') ?>"
                           placeholder="Detalles, IP, contexto...">
                    <button class="btn btn-primary">
                        <i class="bi bi-search"></i> Filtrar
                    </button>
                    <a class="btn btn-ghost" href="<?= url('admin/audit') ?>">
                        <i class="bi bi-x-circle"></i> Limpiar
                    </a>
                </div>
            </div>
        </form>
    </div>

    <!-- ═══ TABLA DE LOGS ═══ -->
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th style="width:60px">#</th>
                        <th>Fecha</th>
                        <th>Usuario</th>
                        <th>Acción</th>
                        <th>Contexto</th>
                        <th>Detalles</th>
                        <th>IP</th>
                        <th style="width:80px"></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($logs)): ?>
                        <tr>
                            <td colspan="8" class="text-center text-muted p-5">
                                <i class="bi bi-inbox display-4"></i>
                                <p class="mt-2 mb-0">No hay registros que coincidan con los filtros</p>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($logs as $l): ?>
                            <tr>
                                <td><small class="text-muted">#<?= (int)$l['id'] ?></small></td>
                                <td>
                                    <small title="<?= e($l['created_at'] ?? '') ?>">
                                        <?= e(!empty($l['created_at']) ? date('d/m/Y H:i', strtotime($l['created_at'])) : '—') ?>
                                    </small>
                                </td>
                                <td>
                                    <?php if (!empty($l['username'])): ?>
                                        <span class="badge bg-<?= ($l['role'] ?? '') === 'Super admin' ? 'danger' : (($l['role'] ?? '') === 'Admin' ? 'primary' : 'secondary') ?>">
                                            <i class="bi bi-person"></i> <?= e($l['username']) ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="text-muted small">
                                            <i class="bi bi-gear"></i> Sistema
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="badge badge-<?= _auditActionColor($l['action'] ?? '') ?>">
                                        <?= e($l['action'] ?? '—') ?>
                                    </span>
                                </td>
                                <td>
                                    <small class="text-muted">
                                        <?= e($l['context'] ?? '—') ?>
                                    </small>
                                </td>
                                <td>
                                    <small class="text-truncate d-inline-block"
                                           style="max-width:250px"
                                           title="<?= e($l['details'] ?? '') ?>">
                                        <?= e(mb_substr($l['details'] ?? '', 0, 60)) ?>
                                    </small>
                                </td>
                                <td>
                                    <code class="small"><?= e($l['ip_address'] ?? '—') ?></code>
                                </td>
                                <td>
                                    <a class="btn btn-ghost btn-sm"
                                       href="<?= url('admin/audit/' . (int)$l['id']) ?>"
                                       title="Ver detalle">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- ═══ PAGINACIÓN ═══ -->
    <?php if ($totalPages > 1): ?>
        <div class="card-footer">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                <small class="text-muted">
                    Mostrando <?= count($logs) ?> de <?= number_format($total) ?> registros
                </small>
                <nav>
                    <ul class="pagination pagination-sm mb-0">
                        <?php if ($page > 1): ?>
                            <li class="page-item">
                                <a class="page-link" href="<?= url('admin/audit?page=' . ($page - 1) . '&' . http_build_query(array_filter($filters))) ?>">
                                    &laquo;
                                </a>
                            </li>
                        <?php endif; ?>

                        <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
                            <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                                <a class="page-link"
                                   href="<?= url('admin/audit?page=' . $i . '&' . http_build_query(array_filter($filters))) ?>">
                                    <?= $i ?>
                                </a>
                            </li>
                        <?php endfor; ?>

                        <?php if ($page < $totalPages): ?>
                            <li class="page-item">
                                <a class="page-link" href="<?= url('admin/audit?page=' . ($page + 1) . '&' . http_build_query(array_filter($filters))) ?>">
                                    &raquo;
                                </a>
                            </li>
                        <?php endif; ?>
                    </ul>
                </nav>
            </div>
        </div>
    <?php endif; ?>
</div>

<!-- ═══ ENLACES RÁPIDOS ═══ -->
<div class="card mt-3">
    <div class="card-body">
        <div class="d-flex gap-3 flex-wrap">
            <a href="<?= url('admin/audit/activity') ?>" class="btn btn-outline-primary btn-sm">
                <i class="bi bi-journal-text"></i> Logs de actividad detallados
            </a>
            <a href="<?= url('admin/security/login-attempts') ?>" class="btn btn-outline-warning btn-sm">
                <i class="bi bi-shield-exclamation"></i> Intentos de login
            </a>
            <a href="<?= url('admin/security/trusted-devices') ?>" class="btn btn-outline-info btn-sm">
                <i class="bi bi-phone"></i> Dispositivos de confianza
            </a>
        </div>
    </div>
</div>
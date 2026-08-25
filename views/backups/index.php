<?php
$pageTitle = 'Backups';
$fmtSize = fn($b) => $b < 1024 ? $b.' B' : ($b < 1048576 ? round($b/1024,1).' KB' : round($b/1048576,2).' MB');
?>

<div class="row g-3 mb-4">
  <div class="col-md-4">
    <div class="stat-card">
      <div class="stat-icon"><i class="bi bi-archive"></i></div>
      <div class="stat-value"><?= $stats['total'] ?></div>
      <div class="stat-label">Backups totales</div>
    </div>
  </div>
  <div class="col-md-4">
    <div class="stat-card">
      <div class="stat-icon"><i class="bi bi-hdd"></i></div>
      <div class="stat-value"><?= $fmtSize($stats['size']) ?></div>
      <div class="stat-label">Espacio utilizado</div>
    </div>
  </div>
  <div class="col-md-4">
    <div class="stat-card">
      <div class="stat-icon"><i class="bi bi-clock-history"></i></div>
      <div class="stat-value" style="font-size:1rem"><?= $stats['last_backup'] ? e($stats['last_backup']) : 'Nunca' ?></div>
      <div class="stat-label">Último backup</div>
    </div>
  </div>
</div>

<div class="row g-3">
  <div class="col-lg-8">
    <div class="card">
      <div class="card-header">
        <h3><i class="bi bi-archive"></i> Backups</h3>
        <?php if (can('backups.manage')): ?>
        <form method="post" action="<?= url('admin/backups/create') ?>" class="d-flex gap-2">
          <?= csrf_field() ?> <!-- HELPER LIMPIO -->
          <select name="type" class="form-select form-select-sm" style="width:auto">
            <option value="full">Completo (BD + archivos)</option>
            <option value="database">Solo BD</option>
            <option value="files">Solo archivos</option>
          </select>
          <button class="btn btn-primary btn-sm"><i class="bi bi-plus"></i> Crear ahora</button>
        </form>
        <?php endif; ?>
      </div>
      <div class="card-body p-0">
        <div class="table-responsive">
          <table class="table">
            <thead>
              <tr>
                <th>Archivo</th>
                <th>Tipo</th>
                <th>Tamaño</th>
                <th>Estado</th>
                <th>Creado por</th>
                <th>Fecha</th>
                <th></th>
              </tr>
            </thead>
            <tbody>
            <?php foreach ($backups as $b): ?>
              <tr>
                <td><code><?= e($b['filename']) ?></code></td>
                <td><span class="badge badge-info"><?= e($b['type']) ?></span></td>
                <td><?= $fmtSize((int)$b['file_size']) ?></td>
                <td>
                  <?php
                  $badge = match($b['status']) {
                    'completed' => 'published',
                    'running'   => 'draft',
                    'failed'    => 'danger',
                    default     => 'archived',
                  };
                  ?>
                  <span class="badge badge-<?= $badge ?>"><?= e($b['status']) ?></span>
                </td>
                <td><?= e($b['created_by_name'] ?? 'Sistema') ?></td>
                <td><?= e($b['created_at']) ?></td>
                <td class="text-end">
                  <?php if ($b['status'] === 'completed'): ?>
                    <a class="btn btn-ghost btn-sm" href="<?= url("admin/backups/{$b['id']}/download") ?>" title="Descargar"><i class="bi bi-download"></i></a>
                    <?php if (can('backups.restore')): ?>
                      <form method="post" action="<?= url("admin/backups/{$b['id']}/restore") ?>" style="display:inline" onsubmit="return confirm('¿Restaurar este backup? Se sobrescribirán los datos actuales.')">
                        <?= csrf_field() ?> <!-- HELPER LIMPIO -->
                        <button class="btn btn-ghost btn-sm text-warning" title="Restaurar"><i class="bi bi-arrow-counterclockwise"></i></button>
                      </form>
                    <?php endif; ?>
                  <?php endif; ?>
                  <?php if (can('backups.manage')): ?>
                    <form method="post" action="<?= url("admin/backups/{$b['id']}/delete") ?>" style="display:inline" onsubmit="return confirm('¿Eliminar?')">
                      <?= csrf_field() ?> <!-- HELPER LIMPIO -->
                      <button class="btn btn-ghost btn-sm text-danger"><i class="bi bi-trash"></i></button>
                    </form>
                  <?php endif; ?>
                </td>
              </tr>
            <?php endforeach; ?>
            <?php if (!$backups): ?>
              <tr><td colspan="7" class="text-center text-muted p-4">No hay backups todavía</td></tr>
            <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>

  <div class="col-lg-4">
    <div class="card">
      <div class="card-header"><h3><i class="bi bi-calendar-check"></i> Programación</h3></div>
      <div class="card-body">
        <p class="text-muted text-small">Configura tus backups automáticos desde el panel de Configuración del Sistema.</p>
        <div class="code-block">
          <div class="code-header"><span>Ejemplo de comando Cron</span></div>
          <pre style="font-size:.7rem;word-break:break-all">0 2 * * * curl -s "<?= e(url('cron/backup?token=' . (require CONFIG_PATH . '/config.php')['cron_token'])) ?>"</pre>
        </div>
      </div>
    </div>
  </div>
</div>
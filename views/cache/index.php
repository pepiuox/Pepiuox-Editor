<?php $pageTitle = 'Cache'; ?>
<div class="row g-3 mb-4">
  <div class="col-md-3"><div class="stat-card"><div class="stat-icon"><i class="bi bi-file-earmark"></i></div><div class="stat-value"><?= $stats['files'] ?></div><div class="stat-label">Archivos</div></div></div>
  <div class="col-md-3"><div class="stat-card"><div class="stat-icon"><i class="bi bi-hdd"></i></div><div class="stat-value"><?= round($stats['size']/1024, 1) ?> KB</div><div class="stat-label">Tamaño</div></div></div>
  <div class="col-md-3"><div class="stat-card"><div class="stat-icon"><i class="bi bi-clock"></i></div><div class="stat-value"><?= $stats['ttl'] ?>s</div><div class="stat-label">TTL</div></div></div>
  <div class="col-md-3"><div class="stat-card"><div class="stat-icon"><i class="bi bi-<?= $stats['enabled']?'check-circle':'x-circle' ?>"></i></div><div class="stat-value"><?= $stats['enabled']?'ON':'OFF' ?></div><div class="stat-label">Estado</div></div></div>
</div>
<div class="card">
  <div class="card-header"><h3><i class="bi bi-hdd"></i> Gestión de cache</h3></div>
  <div class="card-body">
    <form method="post" action="<?= url('admin/cache/clear') ?>" class="d-inline"><?= \App\Core\CSRF::field() ?><button class="btn btn-warning"><i class="bi bi-trash"></i> Limpiar cache</button></form>
    <form method="post" action="<?= url('admin/cache/regenerate') ?>" class="d-inline ms-2"><?= \App\Core\CSRF::field() ?><button class="btn btn-primary"><i class="bi bi-arrow-repeat"></i> Regenerar todo</button></form>
  </div>
</div>
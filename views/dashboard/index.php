<?php $pageTitle = 'Dashboard'; ?>
<div class="container-fluid">
  <div class="row g-3 mb-4">
    <div class="col-md-3"><div class="stat-card"><div class="stat-icon"><i class="bi bi-people"></i></div>
      <div class="stat-value"><?= $stats['users'] ?></div><div class="stat-label">Usuarios</div></div></div>
    <div class="col-md-3"><div class="stat-card"><div class="stat-icon"><i class="bi bi-file-earmark"></i></div>
      <div class="stat-value"><?= $stats['pages'] ?></div><div class="stat-label">Páginas</div></div></div>
    <div class="col-md-3"><div class="stat-card"><div class="stat-icon"><i class="bi bi-check-circle"></i></div>
      <div class="stat-value"><?= $stats['published'] ?></div><div class="stat-label">Publicadas</div></div></div>
    <div class="col-md-3"><div class="stat-card"><div class="stat-icon"><i class="bi bi-pencil-square"></i></div>
      <div class="stat-value"><?= $stats['drafts'] ?></div><div class="stat-label">Borradores</div></div></div>
  </div>

  <div class="row g-3">
    <div class="col-lg-7">
      <div class="card">
        <div class="card-header"><h3><i class="bi bi-clock-history"></i> Páginas recientes</h3></div>
        <div class="card-body p-0">
          <table class="table">
            <thead><tr><th>Título</th><th>Autor</th><th>Estado</th><th>Actualizada</th></tr></thead>
            <tbody>
            <?php foreach ($recentPages as $p): ?>
              <tr>
                <td><?= e($p['title']) ?></td>
                <td><?= e($p['username'] ?? '—') ?></td>
                <td><span class="badge badge-<?= $p['status']==='published'?'published':($p['status']==='draft'?'draft':'archived') ?>"><?= e($p['status']) ?></span></td>
                <td><?= e($p['updated_at']) ?></td>
              </tr>
            <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
    <div class="col-lg-5">
      <div class="card">
        <div class="card-header"><h3><i class="bi bi-activity"></i> Actividad reciente</h3></div>
        <div class="card-body">
          <div class="timeline">
            <?php foreach ($recentActivity as $a): ?>
              <div class="timeline-item">
                <span class="time"><?= e($a['created_at']) ?></span>
                <div class="content"><strong><?= e($a['username'] ?? 'Sistema') ?></strong> · <?= e($a['action']) ?>
                  <br><small><?= e($a['description']) ?></small></div>
              </div>
            <?php endforeach; ?>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
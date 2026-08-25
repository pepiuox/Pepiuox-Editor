<?php 
$pageTitle = 'SEO';
$scoreColor = fn($s) => $s >= 80 ? 'success' : ($s >= 50 ? 'warning' : 'danger');
?>

<div class="row g-3 mb-4">
  <div class="col-md-3">
    <div class="stat-card">
      <div class="stat-icon"><i class="bi bi-file-earmark"></i></div>
      <div class="stat-value"><?= $stats['total_pages'] ?></div>
      <div class="stat-label">Páginas totales</div>
    </div>
  </div>
  <div class="col-md-3">
    <div class="stat-card">
      <div class="stat-icon"><i class="bi bi-type-h1"></i></div>
      <div class="stat-value"><?= $stats['with_title'] ?></div>
      <div class="stat-label">Con meta title</div>
    </div>
  </div>
  <div class="col-md-3">
    <div class="stat-card">
      <div class="stat-icon"><i class="bi bi-card-text"></i></div>
      <div class="stat-value"><?= $stats['with_desc'] ?></div>
      <div class="stat-label">Con meta description</div>
    </div>
  </div>
  <div class="col-md-3">
    <div class="stat-card">
      <div class="stat-icon"><i class="bi bi-signpost-split"></i></div>
      <div class="stat-value"><?= $stats['redirects'] ?></div>
      <div class="stat-label">Redirecciones activas</div>
    </div>
  </div>
</div>

<div class="card mb-4">
  <div class="card-header">
    <h3><i class="bi bi-gear"></i> Herramientas SEO</h3>
    <div class="d-flex gap-2">
      <a class="btn btn-ghost btn-sm" href="<?= url('admin/seo/global') ?>"><i class="bi bi-globe"></i> SEO Global</a>
      <a class="btn btn-ghost btn-sm" href="<?= url('admin/seo/redirects') ?>"><i class="bi bi-signpost-split"></i> Redirecciones</a>
      <form method="post" action="<?= url('admin/seo/sitemap/regenerate') ?>" class="d-inline">
        <?= csrf_field() ?>
        <button class="btn btn-primary btn-sm"><i class="bi bi-arrow-repeat"></i> Regenerar Sitemap</button>
      </form>
    </div>
  </div>
</div>

<div class="card">
  <div class="card-header">
    <h3><i class="bi bi-clipboard-check"></i> Análisis SEO de páginas</h3>
  </div>
  <div class="card-body p-0">
    <div class="table-responsive">
      <table class="table">
        <thead>
          <tr>
            <th>Página</th>
            <th>Meta Title</th>
            <th>Meta Description</th>
            <th>Score</th>
            <th>Estado</th>
            <th></th>
          </tr>
        </thead>
        <tbody>
        <?php foreach ($pages as $p): ?>
          <tr>
            <td>
              <strong><?= e($p['title']) ?></strong>
              <br><small class="text-muted">/<?= e($p['slug']) ?></small>
            </td>
            <td>
              <?php if ($p['meta_title']): ?>
                <span class="text-small"><?= e(mb_substr($p['meta_title'], 0, 50)) ?><?= mb_strlen($p['meta_title'])>50?'...':'' ?></span>
              <?php else: ?>
                <span class="text-muted text-small">Sin título</span>
              <?php endif; ?>
            </td>
            <td>
              <?php if ($p['meta_desc']): ?>
                <span class="text-small"><?= e(mb_substr($p['meta_desc'], 0, 60)) ?><?= mb_strlen($p['meta_desc'])>60?'...':'' ?></span>
              <?php else: ?>
                <span class="text-muted text-small">Sin descripción</span>
              <?php endif; ?>
            </td>
            <td>
              <?php $color = $scoreColor($p['seo_score']); ?>
              <div class="d-flex align-center gap-2">
                <div class="progress" style="width:80px;height:6px">
                  <div class="progress-bar <?= $color === 'success' ? '' : $color ?>" style="width:<?= $p['seo_score'] ?>%"></div>
                </div>
                <strong class="text-<?= $color ?>"><?= $p['seo_score'] ?></strong>
              </div>
            </td>
            <td><span class="badge badge-<?= $p['status'] ?>"><?= e($p['status']) ?></span></td>
            <td class="text-end">
              <a class="btn btn-ghost btn-sm" href="<?= url("admin/seo/{$p['id']}/analyze") ?>" title="Analizar"><i class="bi bi-clipboard-data"></i></a>
              <a class="btn btn-ghost btn-sm" href="<?= url("admin/pages/{$p['id']}/edit") ?>" title="Editar"><i class="bi bi-pencil"></i></a>
            </td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>
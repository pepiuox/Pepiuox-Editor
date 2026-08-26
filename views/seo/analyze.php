<?php
$pageTitle = 'Análisis SEO: ' . $page['title'];
$seo = $seo ?? ['basic'=>[],'og'=>[],'twitter'=>[]];
$score = $result['score'];
$color = $score >= 80 ? 'success' : ($score >= 50 ? 'warning' : 'danger');
$cfg = require CONFIG_PATH . '/config.php';
$baseUrl = rtrim($cfg['app_url'] ?? BASE_URL, '/');
$pageUrl = $baseUrl . '/' . ltrim($page['slug'], '/');

$title = $seo['basic']['title'] ?? $page['title'];
$desc = $seo['basic']['description'] ?? $page['description'] ?? '';
$ogImage = $seo['og']['image'] ?? '';
$siteName = \App\Services\SettingService::get('general', 'site_name', 'PePiuoX Editor');
?>

<div class="row g-4">
  <!-- Columna izquierda: Score y sugerencias -->
  <div class="col-lg-5">
    <div class="card">
      <div class="card-header">
        <h3><i class="bi bi-speedometer2"></i> Score SEO</h3>
      </div>
      <div class="card-body text-center">
        <div style="position:relative;width:180px;height:180px;margin:0 auto 1.5rem">
          <svg viewBox="0 0 100 100" style="transform:rotate(-90deg)">
            <circle cx="50" cy="50" r="45" fill="none" stroke="var(--border)" stroke-width="8"/>
            <circle cx="50" cy="50" r="45" fill="none" stroke="var(--<?= $color ?>)" stroke-width="8"
                    stroke-dasharray="<?= $score * 2.83 ?> 283" stroke-linecap="round"/>
          </svg>
          <div style="position:absolute;top:50%;left:50%;transform:translate(-50%,-50%);text-align:center">
            <div style="font-size:2.5rem;font-weight:700;color:var(--<?= $color ?>)"><?= $score ?></div>
            <div class="text-small text-muted">/ 100</div>
          </div>
        </div>

        <h5 class="text-<?= $color ?> mb-3">
          <?php if ($score >= 80): ?><i class="bi bi-check-circle"></i> Excelente
          <?php elseif ($score >= 50): ?><i class="bi bi-exclamation-circle"></i> Mejorable
          <?php else: ?><i class="bi bi-x-circle"></i> Necesita trabajo
          <?php endif; ?>
        </h5>
      </div>
    </div>

    <div class="card">
      <div class="card-header"><h3><i class="bi bi-list-check"></i> Sugerencias</h3></div>
      <div class="card-body">
        <?php if (empty($result['suggestions'])): ?>
          <div class="alert alert-success"><i class="bi bi-check-circle"></i> ¡Perfecto! No hay sugerencias.</div>
        <?php else: ?>
          <?php foreach ($result['suggestions'] as $s): ?>
            <div class="alert alert-<?= $s['type'] === 'error' ? 'danger' : ($s['type'] === 'warning' ? 'warning' : 'info') ?>">
              <i class="bi bi-<?= $s['type'] === 'error' ? 'x-circle' : ($s['type'] === 'warning' ? 'exclamation-triangle' : 'info-circle') ?>"></i>
              <?= e($s['msg']) ?>
            </div>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>
    </div>

    <div class="card">
      <div class="card-header"><h3><i class="bi bi-bar-chart"></i> Estadísticas</h3></div>
      <div class="card-body">
        <table class="table table-sm">
          <tr><td>Título</td><td class="text-end"><strong><?= $result['stats']['title_length'] ?></strong> / 60</td></tr>
          <tr><td>Descripción</td><td class="text-end"><strong><?= $result['stats']['desc_length'] ?></strong> / 160</td></tr>
          <tr><td>H1</td><td class="text-end"><strong><?= $result['stats']['h1_count'] ?></strong></td></tr>
          <tr><td>Imágenes con alt</td><td class="text-end"><strong><?= $result['stats']['images_with_alt'] ?></strong> / <?= $result['stats']['images_total'] ?></td></tr>
          <tr><td>Contenido</td><td class="text-end"><strong><?= $result['stats']['content_length'] ?></strong> chars</td></tr>
          <tr><td>Enlaces internos</td><td class="text-end"><strong><?= $result['stats']['internal_links'] ?></strong></td></tr>
        </table>
      </div>
    </div>
  </div>

  <!-- Columna derecha: Previews -->
  <div class="col-lg-7">
    <!-- Google SERP -->
    <div class="serp-preview">
      <h3><i class="bi bi-google"></i> Vista previa en Google</h3>
      <div class="google-result">
        <div class="google-url"><?= e($pageUrl) ?></div>
        <div class="google-title" id="previewTitle"><?= e($title) ?></div>
        <div class="google-desc" id="previewDesc"><?= e($desc) ?></div>
      </div>
    </div>

    <!-- Facebook -->
    <div class="serp-preview">
      <h3><i class="bi bi-facebook"></i> Vista previa en Facebook</h3>
      <div class="fb-card">
        <div class="fb-image" style="<?= $ogImage ? "background-image:url('".e($ogImage)."')" : '' ?>"></div>
        <div class="fb-content">
          <div class="fb-domain"><?= e(parse_url($baseUrl, PHP_URL_HOST)) ?></div>
          <div class="fb-title"><?= e($seo['og']['title'] ?? $title) ?></div>
          <div class="fb-desc"><?= e($seo['og']['description'] ?? $desc) ?></div>
        </div>
      </div>
    </div>

    <!-- Twitter -->
    <div class="serp-preview">
      <h3><i class="bi bi-twitter-x"></i> Vista previa en Twitter / X</h3>
      <div class="tw-card">
        <div class="tw-image" style="<?= $ogImage ? "background-image:url('".e($ogImage)."')" : '' ?>"></div>
        <div class="tw-content">
          <div class="tw-title"><?= e($seo['twitter']['title'] ?? $title) ?></div>
          <div class="tw-desc"><?= e($seo['twitter']['description'] ?? $desc) ?></div>
          <div class="tw-domain"><?= e(parse_url($baseUrl, PHP_URL_HOST)) ?></div>
        </div>
      </div>
    </div>

    <div class="d-flex gap-2 mt-3">
      <a class="btn btn-primary" href="<?= url("admin/pages/{$page['id']}/edit") ?>">
        <i class="bi bi-pencil"></i> Editar SEO de esta página
      </a>
      <a class="btn btn-ghost" href="<?= url('admin/seo') ?>">
        <i class="bi bi-arrow-left"></i> Volver
      </a>
    </div>
  </div>
</div>
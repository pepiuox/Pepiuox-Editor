<?php
// 1. Cargar configuración de forma segura con fallback
$configPath = __DIR__ . '/../../config/config.php';
$cfg = is_file($configPath) ? require $configPath : [
    'app_name' => 'PePiuoX Maker',
    'app_version' => '1.0.0',
    'app_url' => 'http://localhost:170/public' // Ajusta esto si tu URL es diferente
];

// 2. Obtener usuario actual con fallback seguro
$current = \App\Core\Auth::user();
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Admin · <?= e($cfg['app_name'] ?? 'PePiuoX Maker') ?></title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
<!-- La función asset() usa app_url + '/assets/' -->
<link rel="stylesheet" href="<?= asset('css/maker.css') ?>">
</head>
<body>
<div class="wrapper">
  <?php 
  $sidebarPath = __DIR__ . '/../partials/sidebar.php';
  echo $sidebarPath;
  if (is_file($sidebarPath)) include $sidebarPath; 
  else echo '<!-- Sidebar no encontrado: ' . $sidebarPath . ' -->';
  ?>
  
  <div class="main">
    <?php 
    $topbarPath = __DIR__ . '/../partials/topbar.php';
    if (is_file($topbarPath)) include $topbarPath;
    else echo '<!-- Topbar no encontrado: ' . $topbarPath . ' -->';
    ?>
    
    <main class="main-content">
      <?php 
      $flashPath = __DIR__ . '/../partials/flash.php';
      if (is_file($flashPath)) include $flashPath;
      ?>
      
      <!-- $content viene de View::render(), usamos ?? '' para evitar warnings -->
      <?= $content ?? '<p class="text-muted">Contenido no disponible</p>' ?>
    </main>
  </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?= asset('js/app.js') ?>"></script>
</body>
</html>
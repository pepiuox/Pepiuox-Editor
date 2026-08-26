<?php
// No necesitas requerir config aquí, las constantes ya están cargadas globalmente
$current = \App\Core\Auth::user() ?? [];
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Admin · PePiuoX Editor</title>
<!-- asset() resuelve automáticamente http://localhost:170/public/assets/... -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
<link rel="stylesheet" href="<?= asset('css/maker.css') ?>">
</head>
<body>
<div class="wrapper">
  <?php include VIEWS_PATH . '/partials/sidebar.php'; ?>
  
  <div class="main">
    <?php include VIEWS_PATH . '/partials/topbar.php'; ?>
    
    <main class="main-content">
      <?php include VIEWS_PATH . '/partials/flash.php'; ?>
      <?= $content ?? '' ?>
    </main>
  </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?= asset('js/app.js') ?>"></script>
</body>
</html>
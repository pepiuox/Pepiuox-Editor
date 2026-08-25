<?php
$cfg = require __DIR__ . '/../../config/config.php';
$current = \App\Core\Auth::user();
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Admin · <?= e($cfg['app_name']) ?></title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">

<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
<link rel="stylesheet" href="<?= asset('css/maker.css') ?>">
</head>
<body>
<div class="wrapper">
  <?php include __DIR__ . '/../partials/sidebar.php'; ?>
  <div class="main">
    <?php include __DIR__ . '/../partials/topbar.php'; ?>
    <main class="main-content">
      <?php include __DIR__ . '/../partials/flash.php'; ?>
      <?= $content ?>
    </main>
  </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js" integrity="sha384-FKyoEForCGlyvwx9Hj09JcYn3nv7wiPVlz7YYwJrWVcXK/BmnVDxM+D2scQbITxI" crossorigin="anonymous"></script>
<script src="<?= asset('js/app.js') ?>"></script>
</body>
</html>
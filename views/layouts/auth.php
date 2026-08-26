<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title><?= e($title ?? 'Iniciar sesión') ?> · <?= e($cfg['app_name'] ?? 'PePiuoX Editor') ?></title>
<link rel="stylesheet" href="<?= asset('css/maker.css') ?>">
</head>
<body>
<?= $content ?>
</body>
</html>
<?php
$cfg = require __DIR__ . '/../../config/config.php';
<?php
declare(strict_types=1);
session_start();
$step = $_GET['step'] ?? '1';
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    try {
        if ($action === 'test_db') {
            $dsn = "mysql:host={$_POST['db_host']};port={$_POST['db_port']};charset=utf8mb4";
            $pdo = new PDO($dsn, $_POST['db_user'], $_POST['db_pass'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
            $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$_POST['db_name']}` CHARACTER SET utf8mb4 COLLATE utf8mb4_uca1400_ai_ci");
            $_SESSION['install'] = $_POST;
            header('Location: install.php?step=2');
            exit;
        }
        if ($action === 'import') {
            $cfg = $_SESSION['install'] ?? null;
            if (!$cfg) throw new Exception('Faltan datos');
            $dsn = "mysql:host={$cfg['db_host']};port={$cfg['db_port']};dbname={$cfg['db_name']};charset=utf8mb4";
            $pdo = new PDO($dsn, $cfg['db_user'], $cfg['db_pass'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
            $sql1 = file_get_contents(__DIR__ . '/../db/db.sql');
            $sql2 = file_get_contents(__DIR__ . '/../db/migrations.sql');
            foreach ([$sql1, $sql2] as $sql) {
                foreach (array_filter(array_map('trim', explode(';', $sql))) as $q) {
                    if ($q) $pdo->exec($q);
                }
            }
            // Crear admin
            $hash = password_hash($_POST['admin_pass'] ?? 'Admin1234*', PASSWORD_DEFAULT, ['cost'=>12]);
            $pdo->prepare("INSERT INTO users (username,email,password_hash,full_name,role,role_id,is_active) VALUES (?,?,?,?,?,?,1)
                           ON DUPLICATE KEY UPDATE password_hash=VALUES(password_hash)")
                ->execute([$_POST['admin_user'] ?? 'admin', $_POST['admin_email'] ?? 'admin@local.test', $hash, 'Administrador', 'Super admin', 1]);
            // Guardar config
            $dbFile = __DIR__ . '/../config/database.php';
            $content = "<?php\nreturn [\n" .
                "  'driver'=>'mysql',\n  'host'=>'{$cfg['db_host']}',\n  'port'=>'{$cfg['db_port']}',\n" .
                "  'dbname'=>'{$cfg['db_name']}',\n  'username'=>'{$cfg['db_user']}',\n  'password'=>'{$cfg['db_pass']}',\n" .
                "  'charset'=>'utf8mb4',\n  'collation'=>'utf8mb4_uca1400_ai_ci',\n" .
                "  'options'=>[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC,PDO::ATTR_EMULATE_PREPARES=>false]\n];\n";
            file_put_contents($dbFile, $content);
            $success = 'Instalación completada. <a href="login.php">Ir al login</a>. Por seguridad, elimina install.php.';
        }
    } catch (Throwable $e) {
        $error = $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="utf-8">
<title>Instalación · PePiuoX Editor</title>
<link rel="stylesheet" href="assets/css/maker.css">
</head>
<body>
<div class="login-container">
  <div class="login-right" style="flex:1">
    <div class="login-form-wrapper">
      <h2 class="form-title">Instalación — Paso <?= e($step) ?></h2>
      <?php if ($error): ?><div class="alert alert-danger"><?= e($error) ?></div><?php endif; ?>
      <?php if ($success): ?><div class="alert alert-success"><?= $success ?></div><?php endif; ?>

      <?php if ($step == '1'): ?>
        <form method="post">
          <input type="hidden" name="action" value="test_db">
          <div class="form-group"><label class="form-label">Host</label><input type="text" name="db_host" class="form-control" value="127.0.0.1" required></div>
          <div class="form-group"><label class="form-label">Puerto</label><input type="text" name="db_port" class="form-control" value="3306" required></div>
          <div class="form-group"><label class="form-label">Base de datos</label><input type="text" name="db_name" class="form-control" value="web_editor_db" required></div>
          <div class="form-group"><label class="form-label">Usuario</label><input type="text" name="db_user" class="form-control" value="root" required></div>
          <div class="form-group"><label class="form-label">Contraseña</label><input type="password" name="db_pass" class="form-control"></div>
          <button class="btn btn-primary btn-block">Probar y continuar</button>
        </form>
      <?php elseif ($step == '2'): ?>
        <form method="post">
          <input type="hidden" name="action" value="import">
          <h4>Administrador</h4>
          <div class="form-group"><label class="form-label">Usuario</label><input type="text" name="admin_user" class="form-control" value="admin" required></div>
          <div class="form-group"><label class="form-label">Email</label><input type="email" name="admin_email" class="form-control" required></div>
          <div class="form-group"><label class="form-label">Contraseña</label><input type="password" name="admin_pass" class="form-control" required minlength="8"></div>
          <button class="btn btn-primary btn-block">Instalar</button>
        </form>
      <?php endif; ?>
    </div>
  </div>
</div>
</body>
</html>
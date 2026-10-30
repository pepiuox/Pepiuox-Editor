<?php
/**
 * Apoyo de las pruebas de la personalización visual pública.
 *
 * Uso:
 *   php tests/public-theme/support.php prep
 *       Prepara archivos de prueba y dos sesiones PHP reales (Super admin y
 *       Manager). Imprime JSON con los ids de sesión y el token CSRF.
 *   php tests/public-theme/support.php get public logo_header
 *       Lee una clave de `settings`.
 *   php tests/public-theme/support.php set public logo_header valor
 *       Escribe una clave de `settings` (sólo para preparar/limpiar escenarios).
 *
 * Los artefactos temporales viven en tests/public-theme/.runtime/, que está en
 * .gitignore. Nada de esto se despliega: es andamiaje de verificación.
 *
 * @package Tests\PublicTheme
 */

declare(strict_types=1);

$root = dirname(__DIR__, 2);
require $root . '/config/env.php';
load_env_file($root . '/01-TOOLS/mysql/.env');

$mode = $argv[1] ?? '';

$pdo = new PDO(
    sprintf(
        'mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
        env('DB_HOST', '127.0.0.1'),
        env('DB_PORT', '3306'),
        env('DB_NAME', '')
    ),
    env('DB_USER', ''),
    env('DB_PASSWORD', ''),
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]
);

if ($mode === 'get') {
    $stmt = $pdo->prepare('SELECT `value` FROM `settings` WHERE `group` = ? AND `key` = ?');
    $stmt->execute([$argv[2] ?? 'public', $argv[3] ?? '']);
    $row = $stmt->fetch();
    echo $row === false ? '<sin-fila>' : (string) $row['value'];
    echo PHP_EOL;
    exit(0);
}

if ($mode === 'set') {
    $stmt = $pdo->prepare('UPDATE `settings` SET `value` = ? WHERE `group` = ? AND `key` = ?');
    $stmt->execute([$argv[4] ?? '', $argv[2] ?? 'public', $argv[3] ?? '']);
    echo 'ok', PHP_EOL;
    exit(0);
}

if ($mode !== 'prep') {
    fwrite(STDERR, "Modo no reconocido. Usa: prep | get | set\n");
    exit(2);
}

// ── Preparación de la verificación HTTP ─────────────────────────
$runtime = __DIR__ . '/.runtime';
$media   = $runtime . '/media';
$sessionDir = $runtime . '/sessions';
foreach ([$runtime, $media, $sessionDir] as $dir) {
    if (!is_dir($dir) && !mkdir($dir, 0775, true) && !is_dir($dir)) {
        fwrite(STDERR, "No se pudo crear {$dir}\n");
        exit(1);
    }
}

$users = $pdo->query('SELECT id, username, role FROM users ORDER BY id')->fetchAll();
$admin = null;
$plain = null;
foreach ($users as $user) {
    if ($admin === null && stripos((string) $user['role'], 'super') !== false) {
        $admin = $user;
    }
    if ($plain === null && stripos((string) $user['role'], 'super') === false) {
        $plain = $user;
    }
}
if ($admin === null) {
    fwrite(STDERR, "No hay ningún usuario Super admin: la prueba autenticada no puede ejecutarse.\n");
    exit(1);
}

// PNG real de 240x60
$png = imagecreatetruecolor(240, 60);
imagefilledrectangle($png, 0, 0, 239, 59, imagecolorallocate($png, 13, 17, 23));
imagefilledellipse($png, 30, 30, 40, 40, imagecolorallocate($png, 0, 212, 170));
imagestring($png, 5, 60, 22, 'TEST LOGO', imagecolorallocate($png, 0, 212, 170));
imagepng($png, $media . '/logo.png');
imagedestroy($png);

$icon = imagecreatetruecolor(32, 32);
imagefilledrectangle($icon, 0, 0, 31, 31, imagecolorallocate($icon, 0, 212, 170));
imagepng($icon, $media . '/icon32.png');
imagedestroy($icon);

// SVG con carga útil: debe salir saneado
file_put_contents(
    $media . '/logo.svg',
    '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 40">'
    . '<script>alert("xss")</script>'
    . '<rect width="100" height="40" fill="#00d4aa" onmouseover="alert(2)"/>'
    . '<text x="5" y="25" fill="#0d1117">LOGO</text>'
    . '</svg>'
);

// PNG falsificado: contenido PHP con extensión de imagen
file_put_contents($media . '/fake.png', "<?php echo 'pwned'; ?>\n");

$csrf = bin2hex(random_bytes(32));

$makeSession = static function (string $dir, string $prefix, array $user, string $csrf): string {
    $sid = $prefix . bin2hex(random_bytes(8));
    session_save_path($dir);
    session_id($sid);
    session_start();
    $_SESSION['user_id']   = (int) $user['id'];
    $_SESSION['user_role'] = (string) $user['role'];
    $_SESSION['_csrf']     = $csrf;
    session_write_close();
    return $sid;
};

$sid  = $makeSession($sessionDir, 'ppftest', $admin, $csrf);
$psid = $plain !== null ? $makeSession($sessionDir, 'ppfplain', $plain, $csrf) : null;

echo json_encode([
    'admin_id'        => (int) $admin['id'],
    'admin_username'  => $admin['username'],
    'admin_role'      => $admin['role'],
    'member_role'     => $plain['role'] ?? null,
    'member_id'       => isset($plain['id']) ? (int) $plain['id'] : null,
    'session_id'      => $sid,
    'member_session'  => $psid,
    'csrf'            => $csrf,
    'media_dir'       => $media,
    'session_dir'     => $sessionDir,
], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES), PHP_EOL;

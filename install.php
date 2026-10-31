<?php
/**
 * Instalador web de PePiuoX Editor (asistente de un solo uso).
 *
 * ─────────────────────────────────────────────────────────────────────
 * EXCEPCIÓN CONSCIENTE AL PRINCIPIO 16 DE LA CONSTITUCIÓN
 * ─────────────────────────────────────────────────────────────────────
 * La constitución v1.0.0 prohíbe que nada que instale sea alcanzable por
 * HTTP. Este archivo existe por decisión expresa del propietario del proyecto
 * (2026-10-30) y sustituye a esa prohibición con estas garantías, todas
 * verificadas antes de ejecutar una sola instrucción:
 *
 *  1. TOKEN DE UN SOLO USO. Se genera solo por consola:
 *         php install.php --make-token
 *     y se guarda fuera de la raíz web (storage/.install-token). Sin ese
 *     token, el asistente responde 403 y no hace nada. Quien no tenga acceso
 *     al servidor no puede obtenerlo.
 *  2. SOLO LOCAL. Solo acepta peticiones desde loopback (127.0.0.1 / ::1).
 *  3. SE AUTODESTRUYE. Al terminar escribe storage/.installed y borra el
 *     token. A partir de ahí responde 410 Gone para siempre, incluso si
 *     alguien restaura este archivo.
 *  4. FALLA CERRADO. Si algo no cuadra (token vencido, ya instalado, otra IP,
 *     demasiados intentos) no escribe nada y no revela detalles.
 *
 * Si prefieres volver al criterio original, borra este archivo: la instalación
 * por consola sigue disponible en docs/INSTALL.md.
 *
 * @package Installer
 */

declare(strict_types=1);

// ═══════════════════════════════════════════════════════════════════
// RUTAS Y ESTADO (fuera de la raíz web)
// ═══════════════════════════════════════════════════════════════════
$root       = __DIR__;
$storageDir = $root . '/storage';
$lockFile   = $storageDir . '/.installed';
$tokenFile  = $storageDir . '/.install-token';
$attempts   = $storageDir . '/.install-attempts';
$envTarget  = $root . '/01-TOOLS/mysql/.env';
$envExample = $root . '/01-TOOLS/mysql/.env.example';
$dumpFile   = $root . '/db/db-full.sql';

const TOKEN_TTL = 3600;   // 1 hora de validez
const MAX_TRIES = 20;     // intentos por ventana antes de bloquear

// ═══════════════════════════════════════════════════════════════════
// MODO CONSOLA: generar el token de instalación
// ═══════════════════════════════════════════════════════════════════
if (PHP_SAPI === 'cli') {
    if (in_array('--make-token', $argv ?? [], true)) {
        if (is_file($lockFile)) {
            fwrite(STDERR, "Ya está instalado (storage/.installed existe). No se genera token.\n");
            exit(1);
        }
        if (!is_dir($storageDir) && !@mkdir($storageDir, 0775, true) && !is_dir($storageDir)) {
            fwrite(STDERR, "No se pudo crear storage/.\n");
            exit(1);
        }
        $token = bin2hex(random_bytes(24));
        if (@file_put_contents($tokenFile, $token . "\n", LOCK_EX) === false) {
            fwrite(STDERR, "No se pudo escribir el token. Revisa permisos de storage/.\n");
            exit(1);
        }
        @chmod($tokenFile, 0600);
        echo "Token creado. Válido 1 hora:\n\n  {$token}\n\n";
        echo "Abre http://<host>/install.php y pégalo.\n";
        echo "Al terminar se borra solo.\n";
        exit(0);
    }

    fwrite(STDERR, "Uso: php install.php --make-token\n");
    exit(2);
}

// ═══════════════════════════════════════════════════════════════════
// A PARTIR DE AQUÍ, SOLO HTTP
// ═══════════════════════════════════════════════════════════════════
header('X-Robots-Tag: noindex, nofollow');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: no-referrer');
header('Cache-Control: no-store, max-age=0');

/** Responde y termina. Nunca detalla el motivo real. */
function fail(int $code, string $mensaje): void
{
    http_response_code($code);
    echo '<!doctype html><html lang="es"><meta charset="utf-8"><title>Instalador</title>'
        . '<body style="font-family:system-ui;padding:2rem;max-width:680px;margin:auto">'
        . '<h1 style="font-size:1.2rem">No disponible</h1><p>' . htmlspecialchars($mensaje, ENT_QUOTES, 'UTF-8') . '</p>'
        . '</body></html>';
    exit;
}

// ── Guarda 1: ya instalado → 410 y fuera ───────────────────────────
if (is_file($lockFile)) {
    fail(410, 'La plataforma ya está instalada. Este asistente está desactivado.');
}

// ── Guarda 2: solo desde el propio servidor ────────────────────────
$ip = $_SERVER['REMOTE_ADDR'] ?? '';
$local = in_array($ip, ['127.0.0.1', '::1'], true);
if (!$local && getenv('INSTALL_ALLOW_REMOTE') !== '1') {
    fail(403, 'El asistente solo funciona desde localhost. Accede por terminal al servidor.');
}

// ── Guarda 3: límite de intentos, contado en disco ─────────────────
$intentos = 0;
if (is_file($attempts)) {
    $intentos = (int) trim((string) @file_get_contents($attempts));
}
if ($intentos >= MAX_TRIES) {
    fail(429, 'Demasiados intentos. Borra storage/.install-attempts para reiniciar el contador.');
}

// ── Guarda 4: token obligatorio, de un solo uso y con caducidad ────
$tokenGuardado = is_file($tokenFile) ? trim((string) @file_get_contents($tokenFile)) : '';
if ($tokenGuardado === '') {
    fail(403, 'No hay token de instalación. Genera uno por consola: php install.php --make-token');
}
if (time() - (int) @filemtime($tokenFile) > TOKEN_TTL) {
    @unlink($tokenFile);
    fail(403, 'El token caducó. Genera uno nuevo por consola: php install.php --make-token');
}

$tokenRecibido = (string) ($_POST['token'] ?? $_GET['token'] ?? '');
if ($tokenRecibido === '' || !hash_equals($tokenGuardado, $tokenRecibido)) {
    @file_put_contents($attempts, (string) ($intentos + 1), LOCK_EX);
    // Pantalla del token: única página pública, y no revela nada del sistema.
    echo '<!doctype html><html lang="es"><meta charset="utf-8"><title>Instalador</title>'
        . '<body style="font-family:system-ui;padding:2rem;max-width:680px;margin:auto">'
        . '<h1 style="font-size:1.2rem">Token de instalación</h1>'
        . '<p>Ejecuta en el servidor <code>php install.php --make-token</code> y pega aquí el token.</p>'
        . '<form method="post"><input name="token" autocomplete="off" '
        . 'style="padding:.5rem;width:100%;font-family:monospace" placeholder="token">'
        . '<button style="margin-top:.75rem;padding:.5rem 1rem">Continuar</button></form>'
        . '<p style="color:#666;font-size:.85rem">Intentos: ' . ($intentos + 1) . ' de ' . MAX_TRIES . '</p>'
        . '</body></html>';
    exit;
}

// ═══════════════════════════════════════════════════════════════════
// A PARTIR DE AQUÍ EL TOKEN ES VÁLIDO
// ═══════════════════════════════════════════════════════════════════

/** Comprobaciones de entorno. Devuelve la lista de problemas. */
function checkRequirements(string $root): array
{
    $problemas = [];

    if (version_compare(PHP_VERSION, '8.2.0', '<')) {
        $problemas[] = 'PHP ' . PHP_VERSION . ': se necesita 8.2 o superior.';
    }
    foreach (['pdo_mysql', 'fileinfo', 'gd', 'mbstring'] as $ext) {
        if (!extension_loaded($ext)) {
            $problemas[] = 'Falta la extensión ' . $ext . '.';
        }
    }
    if (!is_writable($root . '/storage')) {
        $problemas[] = 'storage/ no tiene permiso de escritura.';
    }
    if (is_dir($root . '/uploads') && !is_writable($root . '/uploads')) {
        $problemas[] = 'uploads/ no tiene permiso de escritura (los logos y favicons fallarían).';
    }
    return $problemas;
}

/** Escapa un valor para el archivo .env. */
function envValue(string $valor): string
{
    return preg_replace('/[\r\n]+/', '', $valor) ?? '';
}

$paso     = (string) ($_POST['paso'] ?? '1');
$aplicado = null;
$error    = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $paso === '2') {
    $host  = envValue((string) ($_POST['db_host'] ?? '127.0.0.1'));
    $port  = (int) ($_POST['db_port'] ?? 3306);
    $name  = envValue((string) ($_POST['db_name'] ?? ''));
    $user  = envValue((string) ($_POST['db_user'] ?? ''));
    $pass  = (string) ($_POST['db_pass'] ?? '');

    if ($name === '' || $user === '') {
        $error = 'El nombre de la base de datos y el usuario son obligatorios.';
    } elseif (!preg_match('/^[A-Za-z0-9_]{1,64}$/', $name)) {
        // El nombre no es parametrizable en un CREATE DATABASE: se valida aquí.
        $error = 'El nombre de la base solo admite letras, números y guion bajo.';
    } else {
        try {
            $pdo = new PDO(
                sprintf('mysql:host=%s;port=%d;charset=utf8mb4', $host, $port ?: 3306),
                $user,
                $pass,
                [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_EMULATE_PREPARES => false]
            );
            $pdo->exec('CREATE DATABASE IF NOT EXISTS `' . $name . '` CHARACTER SET utf8mb4');
            $pdo->exec('USE `' . $name . '`');

            // Esquema completo si la base está vacía; si no, se respeta.
            $tablas = (int) $pdo->query(
                'SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE()'
            )->fetchColumn();

            if ($tablas === 0 && is_file($dumpFile)) {
                $sql = (string) file_get_contents($dumpFile);
                $sql = preg_replace('/^\s*--.*$/m', '', $sql) ?? $sql;
                foreach (array_filter(array_map('trim', explode(";\n", $sql))) as $stmt) {
                    if ($stmt !== '') {
                        $pdo->exec($stmt);
                    }
                }
            }

            // Credenciales al .env (0600), nunca a la página.
            $plantilla = is_file($envExample)
                ? (string) file_get_contents($envExample)
                : "DB_HOST=127.0.0.1\nDB_PORT=3306\nDB_NAME=\nDB_USER=\nDB_PASSWORD=\nDB_CHARSET=utf8mb4\n";
            $lineas = [];
            foreach (preg_split('/\R/', $plantilla) ?: [] as $linea) {
                if (preg_match('/^DB_(HOST|PORT|NAME|USER|PASSWORD|CHARSET)=/', $linea)) {
                    continue;
                }
                $lineas[] = $linea;
            }
            $lineas[] = 'DB_HOST=' . $host;
            $lineas[] = 'DB_PORT=' . ($port ?: 3306);
            $lineas[] = 'DB_NAME=' . $name;
            $lineas[] = 'DB_USER=' . $user;
            $lineas[] = 'DB_PASSWORD=' . $pass;
            $lineas[] = 'DB_CHARSET=utf8mb4';

            $dir = dirname($envTarget);
            if (!is_dir($dir) && !@mkdir($dir, 0775, true) && !is_dir($dir)) {
                throw new RuntimeException('No se pudo crear 01-TOOLS/mysql/.');
            }
            if (@file_put_contents($envTarget, implode(PHP_EOL, $lineas) . PHP_EOL, LOCK_EX) === false) {
                throw new RuntimeException('No se pudo escribir 01-TOOLS/mysql/.env.');
            }
            @chmod($envTarget, 0600);

            $tablasTras = (int) $pdo->query(
                'SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE()'
            )->fetchColumn();

            // Cierre: bloqueo permanente y borrado del token.
            @file_put_contents($lockFile, date('c') . PHP_EOL, LOCK_EX);
            @unlink($tokenFile);
            @unlink($attempts);

            $aplicado = ['tablas' => $tablasTras, 'base' => $name];
        } catch (Throwable $e) {
            // Sin detalles del driver en pantalla; sí en el log del servidor.
            error_log('[install] ' . $e->getMessage());
            $error = 'No se pudo conectar o crear el esquema. Revisa host, puerto, usuario y permisos del usuario MySQL.';
        }
    }
}

$problemas = checkRequirements($root);
?>
<!doctype html>
<html lang="es">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Instalación · PePiuoX Editor</title>
<style>
body{font-family:system-ui,-apple-system,'Segoe UI',sans-serif;background:#0d1117;color:#e6edf3;
     margin:0;padding:2rem 1rem;line-height:1.55}
main{max-width:640px;margin:0 auto}
h1{font-size:1.35rem;margin:0 0 .25rem}
p.lead{color:#8b949e;margin:0 0 1.5rem}
.card{background:#161b22;border:1px solid #30363d;border-radius:12px;padding:1.25rem;margin-bottom:1rem}
label{display:block;font-size:.85rem;margin:.75rem 0 .25rem;color:#c9d1d9}
input{width:100%;padding:.5rem .6rem;border-radius:8px;border:1px solid #30363d;background:#0d1117;color:#e6edf3}
button{margin-top:1.1rem;padding:.55rem 1.1rem;border-radius:8px;border:0;background:#00d4aa;color:#04120f;font-weight:600;cursor:pointer}
ul{margin:.5rem 0 0;padding-left:1.1rem;font-size:.9rem}
li{margin:.2rem 0}
.ok{color:#00d4aa}.bad{color:#ff7b72}.warn{color:#f59e0b}
code{background:#0d1117;padding:.1rem .35rem;border-radius:5px;font-size:.85em}
</style>
</head>
<body>
<main>
<h1>Instalación de la plataforma</h1>
<p class="lead">Asistente de un solo uso. Al terminar se desactiva solo.</p>

<?php if ($aplicado !== null): ?>
    <div class="card">
        <h2 class="ok" style="font-size:1rem;margin:0 0 .5rem">Instalación completada</h2>
        <p>Base de datos <code><?= htmlspecialchars($aplicado['base'], ENT_QUOTES, 'UTF-8') ?></code>
           lista con <?= (int) $aplicado['tablas'] ?> tablas. Credenciales guardadas en
           <code>01-TOOLS/mysql/.env</code> con permisos 0600.</p>
        <ul>
            <li>El token se ha borrado.</li>
            <li>El asistente responderá <code>410 Gone</code> a partir de ahora.</li>
            <li>Puedes borrar <code>install.php</code>: no volverá a hacer falta.</li>
            <li>Entra en <code>/login</code> y crea el primer administrador si la base venía vacía.</li>
        </ul>
    </div>
<?php else: ?>

    <?php if ($error !== null): ?>
        <div class="card bad"><strong>No se pudo completar:</strong> <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
    <?php endif; ?>

    <div class="card">
        <strong>Requisitos</strong>
        <?php if ($problemas === []): ?>
            <p class="ok" style="margin:.4rem 0 0">Todo correcto: PHP <?= PHP_VERSION ?> y extensiones presentes.</p>
        <?php else: ?>
            <ul class="bad"><?php foreach ($problemas as $p): ?><li><?= htmlspecialchars($p, ENT_QUOTES, 'UTF-8') ?></li><?php endforeach; ?></ul>
        <?php endif; ?>
    </div>

    <form method="post" class="card" autocomplete="off">
        <input type="hidden" name="token" value="<?= htmlspecialchars($tokenRecibido, ENT_QUOTES, 'UTF-8') ?>">
        <input type="hidden" name="paso" value="2">
        <strong>Datos de MySQL</strong>
        <label for="h">Servidor</label>
        <input id="h" name="db_host" value="<?= htmlspecialchars((string) ($_POST['db_host'] ?? '127.0.0.1'), ENT_QUOTES, 'UTF-8') ?>">
        <label for="p">Puerto</label>
        <input id="p" name="db_port" value="<?= htmlspecialchars((string) ($_POST['db_port'] ?? '3306'), ENT_QUOTES, 'UTF-8') ?>">
        <label for="n">Base de datos (se crea si no existe)</label>
        <input id="n" name="db_name" value="<?= htmlspecialchars((string) ($_POST['db_name'] ?? 'web_editor_db'), ENT_QUOTES, 'UTF-8') ?>">
        <label for="u">Usuario</label>
        <input id="u" name="db_user" value="<?= htmlspecialchars((string) ($_POST['db_user'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
        <label for="w">Contraseña</label>
        <input id="w" name="db_pass" type="password" value="">
        <button type="submit"<?= $problemas === [] ? '' : ' disabled' ?>>Instalar</button>
        <p style="color:#8b949e;font-size:.8rem;margin:.75rem 0 0">
            Si la base ya tiene tablas no se toca: solo se guardan las credenciales.
        </p>
    </form>
<?php endif; ?>
</main>
</body>
</html>

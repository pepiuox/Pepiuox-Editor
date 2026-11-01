<?php
/**
 * Crea la cuenta Super Admin de una instalación nueva.
 *
 * ── Por qué existe ──────────────────────────────────────────────────
 * `db/install/initial_database.sql` no incluye ninguna cuenta ni ninguna
 * contraseña: un hash de contraseña versionado es crackeable con tiempo, y
 * un hash de ejemplo compartido por todas las instalaciones es peor aún.
 * La cuenta se crea aquí, en la terminal del servidor, con la contraseña
 * escrita por quien instala.
 *
 * Constitución del proyecto: principio 15 (ningún secreto en el repositorio)
 * y principio 16 (nada que instale o diagnostique es alcanzable por web;
 * por eso este archivo vive fuera de `public/` y aborta si se sirve por HTTP).
 *
 * ── Uso ─────────────────────────────────────────────────────────────
 *   php 01-TOOLS/bootstrap/create-admin.php --username=admin --email=tu@dominio.com
 *   php 01-TOOLS/bootstrap/create-admin.php --username=admin --email=tu@dominio.com --generate
 *   ADMIN_PASSWORD='...' php 01-TOOLS/bootstrap/create-admin.php --username=admin --email=tu@dominio.com
 *   php 01-TOOLS/bootstrap/create-admin.php --force --username=admin --email=tu@dominio.com
 *
 * Opciones:
 *   --username=<nombre>   Obligatorio. 3 a 50 caracteres: letras, números, . _ -
 *   --email=<correo>      Obligatorio. Correo válido.
 *   --generate            Genera una contraseña aleatoria y la muestra UNA vez.
 *   --force               Si ya existe un Super Admin, reescribe su contraseña.
 *   --yes                 Confirma el destino sin preguntar (para automatizar).
 *                         Sin este indicador, el script imprime la base de datos
 *                         y pide escribir su nombre: escribir en la base
 *                         equivocada es un error con consecuencias.
 *   --help                Muestra esta ayuda.
 *
 * Contraseña, en este orden: la variable de entorno ADMIN_PASSWORD (>12
 * caracteres), o una generada. No se acepta por línea de comandos: quedaría
 * en el historial del shell y en la lista de procesos.
 *
 * @package PePiuoX\Editor\Tools
 * @since   2026-10-31
 */

declare(strict_types=1);

// Principio 16: si alguien alcanza este archivo por HTTP, no hace nada.
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit(1);
}

const MIN_PASS = 12;
const MAX_PASS = 4096;

/** Imprime una línea por la salida estándar. */
function say(string $line = ''): void
{
    fwrite(STDOUT, $line . PHP_EOL);
}

/** Imprime una línea de error por la salida de error. */
function oops(string $line): void
{
    fwrite(STDERR, $line . PHP_EOL);
}

/** Muestra la ayuda. */
function help(): void
{
    say('Crea la cuenta Super Admin de una instalación nueva.');
    say('');
    say('  php 01-TOOLS/bootstrap/create-admin.php --username=<nombre> --email=<correo> [--generate] [--force] [--yes]');
    say('');
    say('  --generate   genera una contraseña aleatoria y la muestra una sola vez');
    say('  --force      reescribe la contraseña si ya existe un Super Admin');
    say('  --yes        confirma el destino sin preguntar (el script imprime la base afectada)');
    say('  --help       esta ayuda');
    say('');
    say('Alternativa a --generate: exportar ADMIN_PASSWORD antes de ejecutar.');
}

/**
 * Lee los argumentos `--clave=valor` y los indicadores sueltos.
 *
 * @param list<string> $argv Argumentos del proceso.
 * @return array{opts: array<string,string>, flags: list<string>}
 */
function parseArgs(array $argv): array
{
    $opts = [];
    $flags = [];

    foreach (array_slice($argv, 1) as $arg) {
        if (!str_starts_with($arg, '--')) {
            continue;
        }
        $arg = substr($arg, 2);
        if (str_contains($arg, '=')) {
            [$k, $v] = explode('=', $arg, 2);
            $opts[strtolower(trim($k))] = trim($v);
        } else {
            $flags[] = strtolower($arg);
        }
    }

    return ['opts' => $opts, 'flags' => $flags];
}

/**
 * Genera una contraseña aleatoria legible y sin caracteres ambiguos.
 *
 * @param int $length Longitud deseada.
 * @return string Contraseña generada.
 * @throws RuntimeException Si el CSPRNG falla o no hay caracteres disponibles.
 */
function generatePassword(int $length = 20): string
{
    // Sin 0/O, 1/l/I ni símbolos que se rompen al copiar y pegar.
    $alphabet = 'abcdefghijkmnpqrstuvwxyzABCDEFGHJKLMNPQRSTUVWXYZ23456789'
              . '!@#$%&*+-=?';
    $max = strlen($alphabet) - 1;

    $out = '';
    for ($i = 0; $i < $length; $i++) {
        $out .= $alphabet[random_int(0, $max)];
    }

    if ($out === '') {
        throw new RuntimeException('No se pudo generar la contraseña.');
    }

    return $out;
}

// ── 1. Argumentos ────────────────────────────────────────────────────
$parsed = parseArgs($argv ?? []);
$opts   = $parsed['opts'];
$flags  = $parsed['flags'];

if (in_array('help', $flags, true)) {
    help();
    exit(0);
}

$username = $opts['username'] ?? '';
$email    = $opts['email'] ?? '';
$force    = in_array('force', $flags, true);
$generate = in_array('generate', $flags, true);

if ($username === '' || $email === '') {
    oops('Faltan --username y --email. Prueba con --help.');
    exit(2);
}

if (!preg_match('/^[A-Za-z0-9._-]{3,50}$/', $username)) {
    oops('El nombre de usuario debe tener entre 3 y 50 caracteres: letras, números, punto, guion o guion bajo.');
    exit(2);
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    oops('El correo no es válido.');
    exit(2);
}

// ── 2. Contraseña ────────────────────────────────────────────────────
$fromEnv      = getenv('ADMIN_PASSWORD');
$password     = '';
$mostrarClave = false;

if (is_string($fromEnv) && $fromEnv !== '') {
    $password = $fromEnv;
    if (strlen($password) < MIN_PASS) {
        oops('ADMIN_PASSWORD tiene menos de ' . MIN_PASS . ' caracteres. No se crea la cuenta.');
        exit(2);
    }
    if (strlen($password) > MAX_PASS) {
        oops('ADMIN_PASSWORD es demasiado larga.');
        exit(2);
    }
    say('Contraseña tomada de la variable de entorno ADMIN_PASSWORD.');
} else {
    $password     = generatePassword(20);
    $mostrarClave = true;
    say('No se indicó contraseña: se generó una aleatoria de 20 caracteres.');
}

// ── 3. Conexión y configuración ──────────────────────────────────────
try {
    $cfg = require __DIR__ . '/../../config/config.php';
    require_once __DIR__ . '/../../app/Core/Database.php';
    $pdo = App\Core\Database::connection();
} catch (Throwable $e) {
    oops('No se pudo conectar a la base de datos: ' . $e->getMessage());
    oops('Revisa 01-TOOLS/mysql/.env (DB_NAME, DB_USER, DB_PASSWORD).');
    exit(1);
}

// ── 3.b ¿Contra qué base se va a escribir? ───────────────────────────
//
// Esta parada existe por un incidente real del 2026-10-31: el script se
// ejecutó sin DB_NAME en el entorno, cayó al .env y escribió en la base de
// producción en lugar de la de pruebas. Se reparó desde el respaldo, pero la
// lección es que el destino hay que verlo y confirmarlo, no suponerlo.
$dbName = (string) $pdo->query('SELECT DATABASE()')->fetchColumn();
$dbHost = (string) (getenv('DB_HOST') ?: '127.0.0.1');

say('Base de datos destino: «' . $dbName . '» en ' . $dbHost);

if (!in_array('yes', $flags, true)) {
    if (!stream_isatty(STDIN)) {
        oops('Sin terminal interactiva no se puede confirmar el destino.');
        oops('Añade --yes si de verdad quieres escribir en «' . $dbName . '».');
        exit(2);
    }
    fwrite(STDOUT, 'Para continuar, escribe el nombre exacto de la base («' . $dbName . '»): ');
    $respuesta = trim((string) fgets(STDIN));
    if ($respuesta !== $dbName) {
        oops('El nombre no coincide. No se creó ni se modificó nada.');
        exit(1);
    }
}

$hashOptions = $cfg['password']['options'] ?? ['cost' => 12];
$superAdmin  = (string) ($cfg['roles']['SUPER_ADMIN'] ?? 'Super admin');

// ── 4. ¿Ya existe un Super Admin? ────────────────────────────────────
$existing = $pdo->prepare('SELECT id, username FROM users WHERE role = ? ORDER BY id ASC LIMIT 1');
$existing->execute([$superAdmin]);
$current = $existing->fetch(PDO::FETCH_ASSOC) ?: null;

if ($current !== null && !$force) {
    oops('Ya existe un Super Admin: «' . $current['username'] . '» (id ' . $current['id'] . ').');
    oops('Para reescribir su contraseña añade --force.');
    exit(1);
}

// ── 5. Rol en el catálogo ────────────────────────────────────────────
$roleStmt = $pdo->prepare('SELECT idRol FROM users_roles WHERE name = ? LIMIT 1');
$roleStmt->execute(['Super Admin']);
$roleId = $roleStmt->fetchColumn();

if ($roleId === false) {
    oops('La tabla users_roles no tiene el rol «Super Admin». ¿Importaste db/install/initial_database.sql?');
    exit(1);
}

// ── 6. Escritura ─────────────────────────────────────────────────────
$hash = password_hash($password, PASSWORD_DEFAULT, $hashOptions);

try {
    if ($current !== null) {
        $upd = $pdo->prepare('UPDATE users SET password_hash = ?, is_active = 1, updated_at = NOW() WHERE id = ?');
        $upd->execute([$hash, (int) $current['id']]);
        say('Contraseña reescrita para «' . $current['username'] . '» (id ' . $current['id'] . ').');
        $finalUser = (string) $current['username'];
    } else {
        $ins = $pdo->prepare(
            'INSERT INTO users (username, email, password_hash, full_name, locale, theme, timezone,
                                is_active, role, role_id, login_count)
             VALUES (?, ?, ?, ?, ?, ?, ?, 1, ?, ?, 0)'
        );
        $ins->execute([
            $username,
            $email,
            $hash,
            'Administrador',
            (string) (substr((string) ($cfg['locale'] ?? 'es'), 0, 2) ?: 'es'),
            'dark',
            (string) ($cfg['timezone'] ?? 'UTC'),
            $superAdmin,
            (int) $roleId,
        ]);
        $finalUser = $username;
        say('Cuenta creada: «' . $username . '» con rol ' . $superAdmin . '.');
    }
} catch (Throwable $e) {
    oops('No se pudo guardar la cuenta: ' . $e->getMessage());
    exit(1);
}

// ── 7. Resumen ───────────────────────────────────────────────────────
say('');
say('  Usuario : ' . $finalUser);
say('  Correo  : ' . $email);
say('  Rol     : ' . $superAdmin);
say('');

if ($mostrarClave) {
    say('  Contraseña: ' . $password);
    say('');
    say('  Cópiala ahora. No se vuelve a mostrar y no queda guardada en ningún archivo.');
    say('  Cámbiala en el primer acceso desde Perfil.');
}

say('Entra por /login. Si la instalación es nueva, la portada aún no tiene contenido:');
say('crea tu primera página en /admin/pages.');
exit(0);

<?php

/**
 * Comprueba y prepara la instalación para publicar.
 *
 *   php 01-TOOLS/despliegue/verificar.php
 *   php 01-TOOLS/despliegue/verificar.php --url=https://mi-dominio.com --fix
 *   php 01-TOOLS/despliegue/verificar.php --url=https://mi-dominio.com --fix --yes
 *
 * ── Qué hace ────────────────────────────────────────────────────────
 * Recorre las mismas comprobaciones que `docs/CHECKLIST_PUBLICACION.md`, en este
 * orden: entorno, archivos, configuración, base de datos, seguridad y cron.
 * Cada una imprime el resultado y, cuando falla, **la orden exacta** que lo
 * arregla.
 *
 * Sin argumentos **no escribe nada**: solo informa. Es el modo seguro.
 *
 * ── Qué arregla con --fix ───────────────────────────────────────────
 * Cosas idempotentes y reversibles, ninguna destructiva:
 *   - genera `CRON_TOKEN` si falta (32 bytes aleatorios)
 *   - añade `APP_URL`, `APP_DEBUG=false` y `SESSION_SECURE=true` si faltan
 *   - crea los directorios de `storage/` que falten
 *
 * **No** importa la base de datos, **no** crea usuarios y **no** aplica
 * migraciones sin permiso explícito: son operaciones con consecuencias y se
 * piden aparte (`--apply-migrations --yes`).
 *
 * ── Código de salida ────────────────────────────────────────────────
 *   0  todo correcto
 *   1  hay fallos (no está listo para publicar)
 *   2  falta un requisito para poder comprobar (por ejemplo, sin base de datos)
 *
 * @package PePiuoX\Editor\Tools
 * @since   2026-11-01
 */

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit(1);
}

$raiz = dirname(__DIR__, 2);
require $raiz . '/config/env.php';
require $raiz . '/config/paths.php';

// Autocargador PSR-4. Sin él, `App\Core\Database` no existe y la comprobación de
// base de datos fallaba con «Class not found» en lugar de comprobar algo real.
spl_autoload_register(static function (string $class) use ($raiz): void {
    if (str_starts_with($class, 'App\\')) {
        $path = $raiz . '/app/' . str_replace('\\', '/', substr($class, 4)) . '.php';
        if (is_file($path)) {
            require_once $path;
        }
    }
});

$args = array_slice($argv ?? [], 1);
$opciones = [
    'fix' => in_array('--fix', $args, true),
    'yes' => in_array('--yes', $args, true),
    'apply_migrations' => in_array('--apply-migrations', $args, true),
    'url' => '',
];
foreach ($args as $a) {
    if (str_starts_with($a, '--url=')) {
        $opciones['url'] = rtrim(substr($a, 6), '/');
    }
}

$fallos = 0;
$avisos = 0;
$arreglado = 0;

function titulo(string $t): void
{
    echo PHP_EOL . '── ' . $t . ' ' . str_repeat('─', max(0, 58 - mb_strlen($t))) . PHP_EOL;
}

function ok(string $d, string $detalle = ''): void
{
    echo '  ✔ ' . $d . ($detalle !== '' ? '  (' . $detalle . ')' : '') . PHP_EOL;
}

function fallo(string $d, string $como): void
{
    global $fallos;
    $fallos++;
    echo '  ✘ ' . $d . PHP_EOL;
    echo '      → ' . $como . PHP_EOL;
}

function aviso(string $d, string $nota = ''): void
{
    global $avisos;
    $avisos++;
    echo '  ! ' . $d . ($nota !== '' ? '  (' . $nota . ')' : '') . PHP_EOL;
}

function hecho(string $d): void
{
    global $arreglado;
    $arreglado++;
    echo '  ⚙ ' . $d . '  [arreglado]' . PHP_EOL;
}

echo '╔══════════════════════════════════════════════════════════════╗' . PHP_EOL;
echo '║  Verificación de publicación · PePiuoX Editor                ║' . PHP_EOL;
echo '╚══════════════════════════════════════════════════════════════╝' . PHP_EOL;
echo 'Modo: ' . ($opciones['fix'] ? 'comprobar y ARREGLAR' : 'solo comprobar (nada se escribe)') . PHP_EOL;

// ═══════════════════════════════════════════════════════════════════
titulo('1. Entorno');
// ═══════════════════════════════════════════════════════════════════

version_compare(PHP_VERSION, '8.2.0', '>=')
    ? ok('PHP', PHP_VERSION)
    : fallo('PHP ' . PHP_VERSION . ' es inferior a 8.2', 'Actualiza PHP antes de publicar.');

$necesarias = ['pdo_mysql', 'mbstring', 'json', 'openssl', 'fileinfo', 'zip', 'gd', 'curl'];
$faltan = array_values(array_filter($necesarias, static fn (string $e): bool => !extension_loaded($e)));
$faltan === []
    ? ok('Extensiones PHP', implode(', ', $necesarias))
    : fallo('Faltan extensiones: ' . implode(', ', $faltan), 'Instálalas y reinicia PHP-FPM/Apache.');

class_exists('ZipArchive')
    ? ok('ZipArchive disponible (copias y exportaciones)')
    : aviso('ZipArchive no disponible', 'las copias completas se guardarán sin comprimir en un ZIP único');

// ═══════════════════════════════════════════════════════════════════
titulo('2. Archivos y permisos');
// ═══════════════════════════════════════════════════════════════════

$envPath = $raiz . '/01-TOOLS/mysql/.env';

if (!is_file($envPath)) {
    fallo('No existe 01-TOOLS/mysql/.env', 'Cópialo de .env.example y rellena los valores.');
} else {
    ok('Existe 01-TOOLS/mysql/.env');
}

foreach (['storage/cache', 'storage/logs', 'storage/backups', 'storage/versions', 'uploads'] as $rel) {
    $dir = $raiz . '/' . $rel;

    if (!is_dir($dir)) {
        if ($opciones['fix'] && @mkdir($dir, 0775, true)) {
            hecho('Creado ' . $rel);
        } else {
            fallo('Falta el directorio ' . $rel, 'mkdir -p ' . $rel . ' && chmod 775 ' . $rel);
            continue;
        }
    }

    // Prueba de escritura REAL, no `is_writable()`: con ACL heredadas miente en
    // los dos sentidos. Escribir un temporal y borrarlo dice la verdad.
    $prueba = $dir . '/.escritura-' . bin2hex(random_bytes(4)) . '.tmp';
    $escribible = @file_put_contents($prueba, 'x') !== false;

    if ($escribible) {
        @unlink($prueba);
        ok('Escribible: ' . $rel);
    } else {
        fallo('Sin permiso de escritura: ' . $rel, 'chown -R www-data:www-data ' . $rel . ' && chmod -R 775 ' . $rel);
    }
}

// El .env no puede estar versionado.
$gitignore = is_file($raiz . '/.gitignore') ? (string) file_get_contents($raiz . '/.gitignore') : '';
$envIgnorado = preg_match('/^\s*01-TOOLS\/mysql\/\.env\s*$/m', $gitignore) === 1
    || preg_match('/^\s*\.env\s*$/m', $gitignore) === 1;

$envIgnorado
    ? ok('El .env está ignorado por git')
    : fallo('El .env no está en .gitignore', 'Añade 01-TOOLS/mysql/.env a .gitignore: es un secreto.');

// ═══════════════════════════════════════════════════════════════════
titulo('3. Configuración de producción');
// ═══════════════════════════════════════════════════════════════════

load_env_file($envPath);

/**
 * Añade una variable al .env si no existe. Nunca toca una que ya esté.
 */
function asegurar_env(string $ruta, string $clave, string $valor, string $comentario): bool
{
    $contenido = is_file($ruta) ? (string) file_get_contents($ruta) : '';

    if (preg_match('/^\s*' . preg_quote($clave, '/') . '\s*=/m', $contenido) === 1) {
        return false;
    }

    $bloque = PHP_EOL . '# ' . $comentario . PHP_EOL . $clave . '=' . $valor . PHP_EOL;
    file_put_contents($ruta, rtrim($contenido) . $bloque);

    return true;
}

$cfg = require $raiz . '/config/config.php';

// APP_URL
$urlEnv = (string) (env('APP_URL') ?? '');
if ($urlEnv === '' && $opciones['url'] !== '') {
    if ($opciones['fix'] && asegurar_env($envPath, 'APP_URL', $opciones['url'], 'URL pública del sitio (sin barra final ni /public).')) {
        hecho('APP_URL definida en .env: ' . $opciones['url']);
        $urlEnv = $opciones['url'];
        $cfg['app_url'] = $opciones['url'];
    }
}

$urlFinal = $cfg['app_url'];
if (preg_match('#^https?://(localhost|127\.0\.0\.1|\[::1\])(:|/|$)#i', (string) $urlFinal) === 1) {
    fallo('La URL pública apunta a localhost (' . $urlFinal . ')', 'Pasa --url=https://tu-dominio.com --fix, o define APP_URL en el .env.');
} elseif (!preg_match('#^https?://#i', (string) $urlFinal)) {
    fallo('La URL pública no es válida: ' . $urlFinal, 'Debe empezar por http:// o https://');
} else {
    ok('URL pública', (string) $urlFinal);
}

// Depuración
if ($cfg['debug'] === true) {
    if ($opciones['fix'] && asegurar_env($envPath, 'APP_DEBUG', 'false', 'false en produccion: con true se muestran trazas al visitante.')) {
        hecho('APP_DEBUG=false añadido al .env');
        ok('debug desactivado');
    } else {
        fallo('debug está activo: las trazas se muestran al visitante', 'Añade APP_DEBUG=false al .env');
    }
} else {
    ok('debug desactivado');
}

// Cookie de sesión
if ($cfg['session']['secure'] === true) {
    ok('Cookie de sesión Secure activa');
} elseif ($opciones['fix'] && asegurar_env($envPath, 'SESSION_SECURE', 'true', 'Cookie de sesion solo por HTTPS. Obligatoria en produccion.')) {
    hecho('SESSION_SECURE=true añadido al .env');
    ok('Cookie de sesión Secure activa');
} else {
    aviso('Cookie de sesión no marcada como Secure', 'Con HTTPS se activa sola; si vas a servir por HTTPS y no lo detecta, define SESSION_SECURE=true');
}

// Token de cron
$token = (string) ($cfg['cron_token'] ?? '');
if ($token !== '' && strlen($token) >= 32) {
    ok('CRON_TOKEN definido', strlen($token) . ' caracteres');
} else {
    $nuevo = bin2hex(random_bytes(32));
    if ($opciones['fix'] && asegurar_env($envPath, 'CRON_TOKEN', $nuevo, 'Token de las tareas programadas (/cron/*). Sin él, los endpoints responden 401.')) {
        hecho('CRON_TOKEN generado (64 caracteres). Guárdalo también en el crontab.');
        ok('CRON_TOKEN definido', '64 caracteres');
    } else {
        fallo('Falta CRON_TOKEN', 'php -r "echo bin2hex(random_bytes(32)), PHP_EOL;" y pégalo en el .env');
    }
}

// ═══════════════════════════════════════════════════════════════════
titulo('4. Base de datos');
// ═══════════════════════════════════════════════════════════════════

$pdo = null;
try {
    $pdo = App\Core\Database::connection();
    ok('Conexión correcta', (string) $pdo->query('SELECT DATABASE()')->fetchColumn());
} catch (Throwable $e) {
    fallo('No se puede conectar a la base de datos', 'Revisa DB_* en el .env. Detalle: ' . $e->getMessage());
}

if ($pdo instanceof PDO) {
    // Privilegios: la cuenta de la aplicación NO debe poder crear ni borrar tablas.
    $puedeCrear = false;
    try {
        $pdo->exec('CREATE TEMPORARY TABLE _perm_probe (id INT)');
        $pdo->exec('DROP TEMPORARY TABLE _perm_probe');
        $puedeCrear = true;
    } catch (Throwable $e) {
        $puedeCrear = false;
    }

    !$puedeCrear
        ? ok('La cuenta de la aplicación no puede crear tablas (mínimo privilegio)')
        : aviso('La cuenta de la aplicación puede crear tablas', 'Dale solo SELECT, INSERT, UPDATE y DELETE.');

    // Tablas imprescindibles
    $tablas = $pdo->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN);
    $imprescindibles = ['users', 'settings', 'pages', 'menus'];
    $ausentes = array_values(array_filter($imprescindibles, static fn (string $t): bool => !in_array($t, $tablas, true)));

    $ausentes === []
        ? ok('Tablas del núcleo presentes', count($tablas) . ' tablas')
        : fallo('Faltan tablas: ' . implode(', ', $ausentes), 'Importa db/install/initial_database.sql');

    // Migraciones conocidas
    $migraciones = [
        'footer_designs'     => '2026_11_01_footer_designs.up.sql',
        'landing (settings)' => null,
    ];
    in_array('footer_designs', $tablas, true)
        ? ok('Migración del catálogo de diseños aplicada')
        : fallo('Falta la tabla footer_designs', 'php db/migrations/apply.php --admin 2026_11_01_footer_designs.up.sql');

    // Super Admin
    try {
        $admins = (int) $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'Super admin' AND is_active = 1")->fetchColumn();
        $admins > 0
            ? ok('Hay ' . $admins . ' Super Admin activo(s)')
            : fallo('No hay ningún Super Admin activo', 'php 01-TOOLS/bootstrap/create-admin.php --generate');
    } catch (Throwable $e) {
        aviso('No se pudo comprobar el Super Admin', $e->getMessage());
    }
}

// ═══════════════════════════════════════════════════════════════════
titulo('5. Tareas programadas');
// ═══════════════════════════════════════════════════════════════════

$endpoints = [
    'backup'                    => 'copia de seguridad completa',
    'process-expired-bans'      => 'levantar baneos caducados',
    'cleanup-rate-limits'       => 'limpiar límites de peticiones',
    'security-digest'           => 'resumen de seguridad',
    'cleanup-trusted-devices'   => 'limpiar dispositivos de confianza',
    'cleanup-collaboration'     => 'limpiar sesiones de colaboración',
];

$base = rtrim((string) $cfg['app_url'], '/');
$probado = false;

if ($token !== '' && preg_match('#^https?://#i', $base) === 1) {
    $ctx = stream_context_create(['http' => ['method' => 'GET', 'timeout' => 5, 'ignore_errors' => true]]);
    $cuerpo = @file_get_contents($base . '/cron/backup?token=' . urlencode($token), false, $ctx);

    if ($cuerpo === false) {
        aviso('No se pudo alcanzar ' . $base . ' desde este equipo', 'normal si el servidor no es accesible desde aquí; compruébalo con curl');
    } else {
        $probado = true;
        $codigo = 0;
        if (isset($http_response_header[0]) && preg_match('/\s(\d{3})\s/', $http_response_header[0], $m) === 1) {
            $codigo = (int) $m[1];
        }
        $codigo === 200
            ? ok('Los endpoints de cron responden con el token')
            : fallo('El endpoint de cron respondió ' . $codigo, 'Comprueba que CRON_TOKEN coincide en .env y crontab');
    }
}

if (!$probado) {
    aviso('Endpoints de cron no probados por HTTP', 'ejecuta el bucle de curl de docs/CHECKLIST_PUBLICACION.md §5 en el servidor');
}

echo PHP_EOL . '  Ejemplo de crontab (sustituye el token y el dominio):' . PHP_EOL;
echo '    15 3 * * *  curl -fsS "' . $base . '/cron/' . 'backup' . '?token=<CRON_TOKEN>" > /dev/null' . PHP_EOL;

// ═══════════════════════════════════════════════════════════════════
titulo('Resumen');
// ═══════════════════════════════════════════════════════════════════

if ($arreglado > 0) {
    echo '  Arreglado: ' . $arreglado . PHP_EOL;
}
echo '  Avisos:    ' . $avisos . PHP_EOL;
echo '  Fallos:    ' . $fallos . PHP_EOL;
echo PHP_EOL;

if ($fallos === 0) {
    echo '  ✔ Listo para publicar.' . ($avisos > 0 ? ' Revisa los avisos.' : '') . PHP_EOL;
    exit(0);
}

echo '  ✘ Todavía no está listo. Cada fallo lleva arriba la orden que lo resuelve.' . PHP_EOL;
exit(1);

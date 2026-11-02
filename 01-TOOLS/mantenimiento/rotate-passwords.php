<?php

/**
 * Rota las contraseñas de las cuentas del panel.
 *
 *   php 01-TOOLS/mantenimiento/rotate-passwords.php --list
 *   php 01-TOOLS/mantenimiento/rotate-passwords.php --user=pepiuox --dry-run
 *   php 01-TOOLS/mantenimiento/rotate-passwords.php --all --yes
 *
 * ── Por qué existe ──────────────────────────────────────────────────
 * Los hashes de las cuentas sembradas estuvieron publicados en el repositorio
 * (deuda 7). Un hash bcrypt se puede atacar sin prisa y sin tocar el servidor:
 * cambiarlos en el volcado **no** invalida las contraseñas reales. Lo que las
 * neutraliza es cambiarlas, y eso es lo que hace este script.
 *
 * ── Qué hace ────────────────────────────────────────────────────────
 *  - Genera una contraseña aleatoria de 20 caracteres por cuenta.
 *  - La guarda con `password_hash` (ARGON2ID si el servidor lo soporta).
 *  - Invalida `remember_token`, para que no quede una sesión recordada abierta.
 *  - Escribe las contraseñas nuevas en un archivo FUERA del repositorio
 *    (`storage/backups/`) y avisa de su ruta. No se imprimen por pantalla: un
 *    secreto en la salida de un terminal acaba en el historial.
 *
 * **No toca la base sin `--yes`.** Con `--dry-run` solo informa.
 *
 * ── Seguridad ───────────────────────────────────────────────────────
 *  - El archivo de contraseñas queda en una carpeta ignorada por git.
 *  - El script borra ese archivo si el usuario lo pide con `--cleanup`.
 *  - Cambiar la contraseña cierra las sesiones de esa cuenta en el siguiente
 *    inicio de sesión (los tokens de recuerdo se anulan aquí).
 *
 * @package PePiuoX\Editor\Tools
 * @since   2026-11-02
 */

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit(1);
}

$raiz = dirname(__DIR__, 2);

$_SERVER['SERVER_PORT'] ??= '80';
$_SERVER['HTTP_HOST'] ??= 'localhost';
$_SERVER['SCRIPT_NAME'] ??= '/public/index.php';

require $raiz . '/config/paths.php';

spl_autoload_register(static function (string $class) use ($raiz): void {
    if (str_starts_with($class, 'App\\')) {
        $path = $raiz . '/app/' . str_replace('\\', '/', substr($class, 4)) . '.php';
        if (is_file($path)) {
            require_once $path;
        }
    }
});

require_once $raiz . '/app/Helpers/functions.php';

function say(string $l = ''): void { fwrite(STDOUT, $l . PHP_EOL); }
function oops(string $l): void { fwrite(STDERR, $l . PHP_EOL); }

$args = array_slice($argv ?? [], 1);
$dryRun  = in_array('--dry-run', $args, true);
$si      = in_array('--yes', $args, true);
$listar  = in_array('--list', $args, true);
$todas   = in_array('--all', $args, true);
$limpiar = in_array('--cleanup', $args, true);

$usuario = '';
foreach ($args as $a) {
    if (str_starts_with($a, '--user=')) {
        $usuario = substr($a, 7);
    }
}

$carpeta = $raiz . '/storage/backups';

if ($limpiar) {
    $borrados = 0;
    foreach (glob($carpeta . '/passwords-*.txt') ?: [] as $f) {
        if (@unlink($f)) {
            $borrados++;
        }
    }
    say('Archivos de contraseñas borrados: ' . $borrados);
    exit(0);
}

try {
    $pdo = App\Core\Database::connection();
} catch (Throwable $e) {
    oops('No se pudo conectar: ' . $e->getMessage());
    exit(1);
}

$db = (string) $pdo->query('SELECT DATABASE()')->fetchColumn();
say('Base de datos destino: «' . $db . '»');
say();

$cuentas = $pdo->query(
    'SELECT id, username, email, role, is_active, LEFT(password_hash, 4) AS prefijo
       FROM users ORDER BY id'
)->fetchAll(PDO::FETCH_ASSOC);

if ($listar) {
    say('Cuentas (' . count($cuentas) . '):');
    say();
    foreach ($cuentas as $c) {
        printf(
            "  #%d  %-16s %-26s %-12s %s  (%s)\n",
            $c['id'],
            $c['username'],
            $c['email'],
            $c['role'],
            $c['is_active'] ? 'activa' : 'inactiva',
            $c['prefijo'] === '$2y$' ? 'bcrypt' : ($c['prefijo'] === '$arg' ? 'argon2' : 'otro')
        );
    }
    exit(0);
}

// ── Selección de cuentas ────────────────────────────────────────────
if ($todas) {
    $objetivo = $cuentas;
} elseif ($usuario !== '') {
    $objetivo = array_values(array_filter(
        $cuentas,
        static fn (array $c): bool => $c['username'] === $usuario
    ));
    if ($objetivo === []) {
        oops('No existe la cuenta «' . $usuario . '». Usa --list para verlas.');
        exit(2);
    }
} else {
    oops('Indica qué cuentas: --user=<usuario> o --all. Usa --list para verlas.');
    exit(2);
}

say('Cuentas a rotar: ' . count($objetivo));
foreach ($objetivo as $c) {
    say('  · ' . $c['username'] . ' (' . $c['role'] . ')');
}
say();

if ($dryRun) {
    say('MODO SIMULACIÓN: no se cambió ninguna contraseña.');
    exit(0);
}

if (!$si) {
    oops('Sin --yes no se cambia nada. Añádelo para rotar, o usa --dry-run para verlo.');
    exit(2);
}

// ── Contraseñas ─────────────────────────────────────────────────────
// Alfabeto sin caracteres ambiguos (0/O, 1/l/I) para poder dictarlas sin errores.
const ALFABETO = 'ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnopqrstuvwxyz23456789!@#$%&*?';

/**
 * Contraseña aleatoria criptográficamente segura.
 */
function nuevaPassword(int $largo = 20): string
{
    $max = strlen(ALFABETO) - 1;
    $out = '';
    for ($i = 0; $i < $largo; $i++) {
        $out .= ALFABETO[random_int(0, $max)];
    }
    return $out;
}

$algo = defined('PASSWORD_ARGON2ID') ? PASSWORD_ARGON2ID : PASSWORD_BCRYPT;

if (!is_dir($carpeta) && !@mkdir($carpeta, 0775, true) && !is_dir($carpeta)) {
    oops('No se pudo crear el directorio de respaldos.');
    exit(1);
}

$archivo = $carpeta . '/passwords-' . date('Ymd-His') . '.txt';
$lineas = [
    'Contraseñas rotadas el ' . date('Y-m-d H:i:s'),
    'Base de datos: ' . $db,
    'Algoritmo: ' . ($algo === PASSWORD_ARGON2ID ? 'argon2id' : 'bcrypt'),
    '',
    'Anota estas contraseñas y BORRA ESTE ARCHIVO después:',
    '  php 01-TOOLS/mantenimiento/rotate-passwords.php --cleanup',
    '',
];

$actualizadas = 0;

$pdo->beginTransaction();
try {
    $upd = $pdo->prepare(
        'UPDATE users SET password_hash = ?, remember_token = NULL, remember_expires = NULL, updated_at = NOW()
          WHERE id = ?'
    );

    foreach ($objetivo as $c) {
        $plano = nuevaPassword();
        $upd->execute([password_hash($plano, $algo), (int) $c['id']]);
        $actualizadas++;

        $lineas[] = sprintf('  %-16s %s', $c['username'], $plano);
    }

    $pdo->commit();
} catch (Throwable $e) {
    $pdo->rollBack();
    oops('No se pudo rotar: ' . $e->getMessage());
    exit(1);
}

$lineas[] = '';
$lineas[] = 'Las sesiones «recordarme» de esas cuentas quedan invalidadas.';

file_put_contents($archivo, implode(PHP_EOL, $lineas) . PHP_EOL);
@chmod($archivo, 0600);

say('Contraseñas rotadas: ' . $actualizadas);
say('Tokens de recuerdo anulados en las cuentas afectadas.');
say();
say('Las contraseñas nuevas están en:');
say('  ' . str_replace($raiz . '/', '', $archivo));
say();
say('Ábrelo, anótalas y bórralo:');
say('  php 01-TOOLS/mantenimiento/rotate-passwords.php --cleanup');
say();
say('No se imprimen por pantalla a propósito: la salida de un terminal queda en el historial.');

exit(0);

<?php

/**
 * Rota los tokens de las aplicaciones de API.
 *
 *   php 01-TOOLS/mantenimiento/rotate-api-tokens.php --dry-run
 *   php 01-TOOLS/mantenimiento/rotate-api-tokens.php --yes
 *
 * ── Por qué existe ──────────────────────────────────────────────────
 * `api_tokens.token` y `refresh_token` se guardan en hexadecimal plano y se usan
 * como credencial Bearer: quien los tenga, entra. Estuvieron publicados en los
 * volcados versionados del repositorio, así que hay que darlos por filtrados.
 *
 * Retirarlos del archivo **no los invalida**. Lo que los neutraliza es cambiarlos
 * en la base de datos, y eso es lo que hace este script.
 *
 * ── Qué rota y qué no ───────────────────────────────────────────────
 *  - **Rota** `token` y `refresh_token` de cada fila de `api_tokens`, con el
 *    mismo formato que usa la aplicación (`bin2hex(random_bytes(32))`).
 *  - **No rota** `app_secret` ni `api_secret` de `api_applications`. No hace
 *    falta: `App\Core\Security` genera una clave de cifrado nueva por instancia,
 *    así que esos valores se cifraron con una clave que ya no existe y **no se
 *    pueden descifrar**. Son texto cifrado inservible. Rotarlos produciría otro
 *    texto cifrado igual de inservible. Es el hallazgo R-13 de las
 *    especificaciones técnicas, y arreglarlo exige una clave estable en el
 *    entorno: otro cambio, no este.
 *  - **No rota** `app_key`, que es un identificador, no un secreto.
 *
 * ── Consecuencia ────────────────────────────────────────────────────
 * Cualquier integración que estuviera usando un token de API deja de funcionar y
 * tendrá que generar uno nuevo desde el panel (`/admin/api`). Eso es lo que se
 * busca: un token filtrado no debe seguir sirviendo.
 *
 * @package PePiuoX\Editor\Tools
 * @since   2026-10-31
 */

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit(1);
}

$_SERVER['SERVER_PORT'] ??= '80';
$_SERVER['HTTP_HOST']   ??= 'localhost';
$_SERVER['REQUEST_URI'] ??= '/';

require __DIR__ . '/../../config/paths.php';

spl_autoload_register(static function (string $class): void {
    if (str_starts_with($class, 'App\\')) {
        $path = APP_PATH . '/' . str_replace('\\', '/', substr($class, 4)) . '.php';
        if (is_file($path)) {
            require_once $path;
        }
    }
});

require_once APP_PATH . '/Helpers/functions.php';

function say(string $l = ''): void { fwrite(STDOUT, $l . PHP_EOL); }
function oops(string $l): void { fwrite(STDERR, $l . PHP_EOL); }

$flags = array_map(
    static fn (string $a): string => ltrim($a, '-'),
    array_filter(array_slice($argv ?? [], 1), static fn (string $a): bool => str_starts_with($a, '--'))
);

$dryRun = in_array('dry-run', $flags, true);
$si     = in_array('yes', $flags, true);

try {
    $pdo = App\Core\Database::connection();
} catch (Throwable $e) {
    oops('No se pudo conectar: ' . $e->getMessage());
    exit(1);
}

$dbName = (string) $pdo->query('SELECT DATABASE()')->fetchColumn();
say('Base de datos destino: «' . $dbName . '» en ' . (getenv('DB_HOST') ?: '127.0.0.1'));

$total = (int) $pdo->query('SELECT COUNT(*) FROM api_tokens')->fetchColumn();
say('Tokens en la tabla: ' . $total);

if ($total === 0) {
    say('No hay tokens que rotar. Nada que hacer.');
    exit(0);
}

if (!$dryRun && !$si) {
    if (!stream_isatty(STDIN)) {
        oops('Sin terminal interactiva no se puede confirmar. Añade --yes o usa --dry-run.');
        exit(2);
    }
    fwrite(STDOUT, 'Escribe el nombre exacto de la base («' . $dbName . '») para rotar: ');
    if (trim((string) fgets(STDIN)) !== $dbName) {
        oops('El nombre no coincide. No se cambió nada.');
        exit(1);
    }
}

// Huellas antes, para poder demostrar después que cambiaron sin imprimir valores.
$antes = [];
foreach ($pdo->query('SELECT id, token, refresh_token FROM api_tokens')->fetchAll() as $fila) {
    $antes[(int) $fila['id']] = hash('sha256', (string) $fila['token']);
}

if ($dryRun) {
    say('MODO SIMULACIÓN: se rotarían ' . $total . ' token(s). No se escribió nada.');
    exit(0);
}

$rotados = 0;
$pdo->beginTransaction();
try {
    $upd = $pdo->prepare('UPDATE api_tokens SET token = ?, refresh_token = ? WHERE id = ?');
    foreach ($pdo->query('SELECT id FROM api_tokens')->fetchAll() as $fila) {
        $nuevo      = bin2hex(random_bytes(32));
        $renovacion = bin2hex(random_bytes(32));
        $upd->execute([$nuevo, $renovacion, (int) $fila['id']]);
        $rotados++;
    }
    $pdo->commit();
} catch (Throwable $e) {
    $pdo->rollBack();
    oops('No se pudo rotar: ' . $e->getMessage());
    exit(1);
}

// Verificación: mismos registros, valores distintos.
$despues = $pdo->query('SELECT id, token FROM api_tokens')->fetchAll();
$sinCambiar = 0;
foreach ($despues as $fila) {
    $id = (int) $fila['id'];
    if (isset($antes[$id]) && hash('sha256', (string) $fila['token']) === $antes[$id]) {
        $sinCambiar++;
    }
}

say('Rotados: ' . $rotados . ' · sin cambiar: ' . $sinCambiar);
say($sinCambiar === 0
    ? 'Todos los tokens son nuevos: los valores filtrados ya no sirven.'
    : 'ATENCIÓN: alguno no cambió. Revísalo.');

say('');
say('Quien estuviera usando un token de API tendrá que generar uno nuevo en /admin/api.');

exit($sinCambiar === 0 ? 0 : 1);

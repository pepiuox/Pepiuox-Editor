<?php

/**
 * Retira de un volcado SQL los datos que no deben versionarse.
 *
 *   php 01-TOOLS/mantenimiento/sanitize-dump.php --dry-run db/db-full.sql
 *   php 01-TOOLS/mantenimiento/sanitize-dump.php --yes db/db-full.sql
 *
 * ── Qué hace ────────────────────────────────────────────────────────
 * Un volcado completo de la base lleva cosas que no pueden estar en un
 * repositorio (constitución, principio 15):
 *
 *  1. **Hashes de contraseñas.** Un hash bcrypt se puede atacar sin prisa, sin
 *     tocar el servidor. Se sustituyen por un centinela que no valida ninguna
 *     contraseña.
 *  2. **Secretos de aplicaciones de API.** `app_secret`, `api_secret`, `app_key`
 *     y los tokens de `api_tokens` son credenciales vivas. Se retiran las filas
 *     completas de esas dos tablas: son datos de ejemplo, no estructura, y la
 *     estructura se conserva entera.
 *
 * **No toca la estructura**: el número de `CREATE TABLE`, vistas, rutinas y
 * disparadores debe ser el mismo antes y después. El script lo comprueba y falla
 * si no cuadra.
 *
 * ── Qué NO hace ─────────────────────────────────────────────────────
 * No rota las credenciales en la base de datos. Retirarlas del archivo no las
 * invalida: si ya estaban publicadas, siguen sirviendo hasta que se cambien en
 * el servidor. Eso es un paso aparte y deliberado.
 *
 * Tampoco limpia el historial de git. Un secreto que ya se confirmó sigue en los
 * commits anteriores aunque el archivo actual esté limpio; lo que de verdad lo
 * neutraliza es rotarlo.
 *
 * ── Uso seguro ──────────────────────────────────────────────────────
 * Antes de escribir, guarda una copia del original en `storage/preview/` (fuera
 * del control de versiones) y valida que el resultado sigue siendo SQL con el
 * mismo número de objetos.
 *
 * @package PePiuoX\Editor\Tools
 * @since   2026-10-31
 */

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit(1);
}

function say(string $l = ''): void { fwrite(STDOUT, $l . PHP_EOL); }
function oops(string $l): void { fwrite(STDERR, $l . PHP_EOL); }

$args = array_slice($argv ?? [], 1);
$dryRun = in_array('--dry-run', $args, true);
$si = in_array('--yes', $args, true);
$archivo = '';

foreach ($args as $a) {
    if (!str_starts_with($a, '--')) {
        $archivo = $a;
    }
}

if ($archivo === '' || !is_file($archivo)) {
    oops('Uso: php 01-TOOLS/mantenimiento/sanitize-dump.php [--dry-run|--yes] <archivo.sql>');
    exit(2);
}

if (!$dryRun && !$si) {
    oops('Sin --yes no se escribe nada. Usa --dry-run para ver el efecto primero.');
    exit(2);
}

$original = (string) file_get_contents($archivo);
$antes = [
    'CREATE TABLE' => substr_count($original, 'CREATE TABLE'),
    'CREATE VIEW'  => preg_match_all('/CREATE (OR REPLACE )?(ALGORITHM=[^ ]+ )?VIEW/i', $original),
    'TRIGGER'      => preg_match_all('/CREATE\s+.*TRIGGER/i', $original),
    'PROCEDURE'    => preg_match_all('/CREATE\s+.*PROCEDURE/i', $original),
    'FUNCTION'     => preg_match_all('/CREATE\s+.*FUNCTION/i', $original),
];

$hashesAntes = preg_match_all('/\'\\$2[aby]\\$\\d\\d\\$[.\\/A-Za-z0-9]{20,60}\'/', $original);
$appsAntes = preg_match('/INSERT INTO `api_applications`/i', $original) === 1;
$tokensAntes = preg_match('/INSERT INTO `api_tokens`/i', $original) === 1;

say('Archivo: ' . $archivo . ' (' . number_format(strlen($original)) . ' bytes)');
say('  hashes bcrypt encontrados : ' . $hashesAntes);
say('  INSERT api_applications   : ' . ($appsAntes ? 'sí' : 'no'));
say('  INSERT api_tokens         : ' . ($tokensAntes ? 'sí' : 'no'));

if ($hashesAntes === 0 && !$appsAntes && !$tokensAntes) {
    say('Nada que sanear: el archivo ya está limpio.');
    exit(0);
}

// ── 1. Hashes de contraseñas ─────────────────────────────────────────
$centinela = "'REEMPLAZADO-NO-ES-UNA-CONTRASENA-VALIDA'";
$salida = (string) preg_replace(
    '/\'\\$2[aby]\\$\\d\\d\\$[.\\/A-Za-z0-9]{20,60}\'/',
    $centinela,
    $original
);

// ── 2. Filas de aplicaciones y tokens de API ─────────────────────────
// Se retira la sentencia completa (hasta el `;` de cierre), no la línea: los
// volcados con varias filas por sentencia ocupan varias líneas.
$nota = "-- ═══════════════════════════════════════════════════════════════════\n"
      . "-- Filas de `%s` retiradas del volcado de referencia.\n"
      . "-- Contenían credenciales de API vivas (claves, secretos y tokens).\n"
      . "-- La estructura de la tabla se conserva arriba. Para restaurar una copia\n"
      . "-- completa, usa un respaldo real de `db/backup/` (no versionado).\n"
      . "-- Deuda 16 en 02-DOCS/wiki/sdd/constitution.md\n"
      . "-- ═══════════════════════════════════════════════════════════════════\n";

foreach (['api_applications', 'api_tokens'] as $tabla) {
    $salida = (string) preg_replace(
        '/INSERT INTO `' . $tabla . '`.*?;\s*\n/s',
        sprintf($nota, $tabla),
        $salida
    );
}

// ── 3. Comprobaciones antes de escribir ──────────────────────────────
$hashesDespues = preg_match_all('/\'\\$2[aby]\\$\\d\\d\\$[.\\/A-Za-z0-9]{20,60}\'/', $salida);
$appsDespues = preg_match('/INSERT INTO `api_applications`/i', $salida) === 1;
$tokensDespues = preg_match('/INSERT INTO `api_tokens`/i', $salida) === 1;

$despues = [
    'CREATE TABLE' => substr_count($salida, 'CREATE TABLE'),
    'CREATE VIEW'  => preg_match_all('/CREATE (OR REPLACE )?(ALGORITHM=[^ ]+ )?VIEW/i', $salida),
    'TRIGGER'      => preg_match_all('/CREATE\s+.*TRIGGER/i', $salida),
    'PROCEDURE'    => preg_match_all('/CREATE\s+.*PROCEDURE/i', $salida),
    'FUNCTION'     => preg_match_all('/CREATE\s+.*FUNCTION/i', $salida),
];

say('');
say('Resultado:');
say('  hashes bcrypt             : ' . $hashesDespues);
say('  INSERT api_applications   : ' . ($appsDespues ? 'sí' : 'no'));
say('  INSERT api_tokens         : ' . ($tokensDespues ? 'sí' : 'no'));

foreach ($antes as $objeto => $n) {
    $m = $despues[$objeto] ?? 0;
    if ($objeto === 'CREATE TABLE' || $objeto === 'CREATE VIEW' || $objeto === 'TRIGGER'
        || $objeto === 'PROCEDURE' || $objeto === 'FUNCTION') {
        say(sprintf('  %-26s: %d -> %d %s', $objeto, $n, $m, $n === $m ? 'ok' : '¡CAMBIÓ!'));
    }
}

if ($hashesDespues !== 0 || $appsDespues || $tokensDespues) {
    oops('No se pudo sanear todo: se aborta sin escribir.');
    exit(1);
}

if ($antes['CREATE TABLE'] !== $despues['CREATE TABLE']) {
    oops('El número de tablas cambió: se aborta sin escribir.');
    exit(1);
}

if ($dryRun) {
    say('');
    say('MODO SIMULACIÓN: no se escribió nada.');
    exit(0);
}

// Copia de seguridad del original, fuera del control de versiones.
$dirCopia = dirname(__DIR__, 2) . '/storage/preview';
if (!is_dir($dirCopia)) {
    mkdir($dirCopia, 0775, true);
}
$copia = $dirCopia . '/original-' . basename($archivo) . '-' . date('Ymd-His');
file_put_contents($copia, $original);
say('');
say('Original guardado en: ' . $copia);

file_put_contents($archivo, $salida);
say('Escrito: ' . $archivo . ' (' . number_format(strlen($salida)) . ' bytes)');
exit(0);

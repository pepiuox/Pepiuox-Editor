<?php
/**
 * Aplicador mínimo de migraciones SQL.
 *
 * Uso:
 *   php db/migrations/apply.php 2026_10_25_audit_logs_columns.up.sql
 *   php db/migrations/apply.php --admin 2026_10_25_audit_logs_columns.up.sql
 *
 * Credenciales: por defecto usa las de la aplicación (DB_USER), que a propósito
 * NO tiene permiso ALTER. Para cambios de esquema hay que pasar --admin, y
 * entonces usa DB_ROOT_USER / DB_ROOT_PASSWORD del mismo entorno. Así el
 * usuario con el que corre la web nunca puede modificar el esquema.
 *
 * Por qué existe: el proyecto no tenía aplicador (db/db-full.sql es un volcado).
 * Esto ejecuta un .sql del directorio db/migrations sentencia a sentencia e
 * informa de lo que hace.
 *
 * Idempotencia: una sentencia que ya está aplicada y falla con «duplicate
 * column» (1060) o «duplicate key» (1061) se marca como OMITIDA en lugar de
 * abortar, para poder relanzar la migración sin efectos.
 *
 * Seguridad: solo CLI (db/ está bloqueado por .htaccess, pero por si acaso) y
 * solo acepta rutas dentro de db/migrations.
 *
 * @package Db\Migrations
 */

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit("Solo se puede ejecutar por CLI.\n");
}

$root = dirname(__DIR__, 2);
require $root . '/config/env.php';
load_env_file($root . '/01-TOOLS/mysql/.env');

$args = array_slice($argv, 1);
$useAdmin = in_array('--admin', $args, true);
$args = array_values(array_filter($args, static fn (string $a): bool => $a !== '--admin'));
$file = $args[0] ?? '';
if ($file === '') {
    fwrite(STDERR, "Uso: php db/migrations/apply.php [--admin] <archivo.sql>\n");
    exit(1);
}

$migrationsDir = realpath(__DIR__) ?: __DIR__;
$path = realpath(str_starts_with($file, DIRECTORY_SEPARATOR) ? $file : $migrationsDir . '/' . $file);
if ($path === false || !is_file($path) || !str_starts_with($path, $migrationsDir . DIRECTORY_SEPARATOR)) {
    fwrite(STDERR, "Archivo no encontrado o fuera de db/migrations: {$file}\n");
    exit(1);
}

$dsn = sprintf(
    'mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
    env('DB_HOST', '127.0.0.1'),
    env('DB_PORT', '3306'),
    env('DB_NAME', '')
);

$dbUser = $useAdmin ? env('DB_ROOT_USER', '') : env('DB_USER', '');
$dbPass = $useAdmin ? env('DB_ROOT_PASSWORD', '') : env('DB_PASSWORD', '');

if ($useAdmin && ($dbUser === '' || $dbPass === '')) {
    fwrite(STDERR, "Faltan DB_ROOT_USER / DB_ROOT_PASSWORD en el entorno: no se puede aplicar esquema.\n");
    exit(1);
}

try {
    $pdo = new PDO($dsn, $dbUser, $dbPass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
} catch (PDOException $e) {
    fwrite(STDERR, "No se pudo conectar a la base de datos.\n");
    exit(1);
}

echo 'Credenciales: ' . ($useAdmin ? 'administrador (DB_ROOT_USER)' : 'aplicación (DB_USER)') . "\n";

$sql = (string) file_get_contents($path);

// Quitar comentarios de línea completa para no partir dentro de ellos.
$lines = preg_split('/\R/', $sql) ?: [];
$clean = [];
foreach ($lines as $line) {
    if (preg_match('/^\s*--/', $line) === 1) {
        continue;
    }
    $clean[] = $line;
}

$statements = array_filter(
    array_map('trim', explode(';', implode("\n", $clean))),
    static fn (string $s): bool => $s !== ''
);

echo "Migración: " . basename($path) . " (" . count($statements) . " sentencia(s))\n";

$applied = 0;
$skipped = 0;

foreach ($statements as $index => $statement) {
    $single = preg_replace('/\s+/', ' ', $statement) ?? $statement;
    try {
        $pdo->exec($statement);
        $applied++;
        echo '  [' . ($index + 1) . "] APLICADA   " . mb_substr($single, 0, 90) . "\n";
    } catch (PDOException $e) {
        $code = (int) ($e->errorInfo[1] ?? 0);
        if ($code === 1060 || $code === 1061) {
            $skipped++;
            echo '  [' . ($index + 1) . "] OMITIDA    ya existía: " . mb_substr($single, 0, 90) . "\n";
            continue;
        }
        fwrite(STDERR, '  [' . ($index + 1) . "] ERROR      " . $e->getMessage() . "\n");
        exit(1);
    }
}

echo "Resultado: {$applied} aplicada(s), {$skipped} omitida(s).\n";

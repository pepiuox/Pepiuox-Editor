<?php
/**
 * test_connection.php — prueba que la conexión a MySQL funciona de verdad.
 *
 * Convención de 01-TOOLS: cada carpeta de herramienta trae una prueba que demuestra
 * que la conexión está viva. Esto no escribe nada: solo lee.
 *
 * Uso:  php 01-TOOLS/mysql/test_connection.php
 */

declare(strict_types=1);

$configFile = __DIR__ . '/../../config/database.php';

if (!is_file($configFile)) {
    fwrite(STDERR, "No existe config/database.php\n");
    exit(1);
}

try {
    $cfg = require $configFile;
} catch (Throwable $e) {
    fwrite(STDERR, 'Configuración incompleta: ' . $e->getMessage() . "\n");
    exit(1);
}

$dsn = sprintf(
    '%s:host=%s;port=%s;dbname=%s;charset=%s',
    $cfg['driver'],
    $cfg['host'],
    $cfg['port'],
    $cfg['dbname'],
    $cfg['charset']
);

try {
    $pdo = new PDO($dsn, $cfg['username'], $cfg['password'], $cfg['options']);
    $tables = (int) $pdo->query(
        'SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE()'
    )->fetchColumn();

    printf(
        "OK: conecta a %s@%s:%s/%s. Tablas visibles: %d\n",
        $cfg['username'],
        $cfg['host'],
        $cfg['port'],
        $cfg['dbname'],
        $tables
    );
    exit(0);
} catch (Throwable $e) {
    fwrite(STDERR, 'FALLO: ' . $e->getMessage() . "\n");
    exit(1);
}

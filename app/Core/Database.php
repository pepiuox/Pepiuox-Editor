<?php
namespace App\Core;

use PDO;
use PDOException;
use RuntimeException;

class Database
{
    private static ?PDO $instance = null;

    public static function connection(): PDO
    {
        if (self::$instance === null) {
            $cfg = require __DIR__ . '/../../config/database.php';

            $dsn = sprintf(
                '%s:host=%s;port=%s;dbname=%s;charset=%s',
                $cfg['driver'], $cfg['host'], $cfg['port'], $cfg['dbname'], $cfg['charset']
            );

            try {
                self::$instance = new PDO($dsn, $cfg['username'], $cfg['password'], $cfg['options']);
            } catch (PDOException $e) {
                error_log('[DB] ' . $e->getMessage());
                throw new RuntimeException(
                    'No se pudo conectar a la base de datos. Revisa 01-TOOLS/mysql/.env '
                    . 'o abre /install.php para configurarla desde el navegador.'
                );
            }
        }
        return self::$instance;
    }

    /** Consulta con prepared statements. Devuelve array de filas. */
    public static function fetchAll(string $sql, array $params = []): array
    {
        $stmt = self::connection()->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /** Devuelve una sola fila o null. */
    public static function fetch(string $sql, array $params = []): ?array
    {
        $stmt = self::connection()->prepare($sql);
        $stmt->execute($params);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    /** INSERT / UPDATE / DELETE. Devuelve número de filas afectadas. */
    public static function execute(string $sql, array $params = []): int
    {
        $stmt = self::connection()->prepare($sql);
        $stmt->execute($params);
        return $stmt->rowCount();
    }

    /** INSERT y devuelve el último ID insertado. */
    public static function insert(string $sql, array $params = []): int
    {
        $pdo = self::connection();
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return (int) $pdo->lastInsertId();
    }

    public static function begin(): void   { self::connection()->beginTransaction(); }
    public static function commit(): void  { self::connection()->commit(); }
    public static function rollback(): void{ self::connection()->rollBack(); }

    /**
     * Indica si la conexión tiene una transacción abierta.
     *
     * Lo necesitan los servicios que pueden ser llamados desde dentro de la
     * transacción de otro: quien abre una transacción es quien debe cerrarla, y
     * un `begin()` sobre otra ya abierta no se anida: PDO responde
     * «There is already an active transaction» (SQLSTATE 25001).
     */
    public static function inTransaction(): bool
    {
        return self::connection()->inTransaction();
    }

    /**
     * Revierte solo si hay una transacción abierta.
     *
     * Sirve para los `catch` de bloques que validan antes de abrir la
     * transacción: llamar a rollback() sin transacción lanza
     * «There is no active transaction» y oculta el error real.
     */
    public static function rollbackIfActive(): void
    {
        if (self::inTransaction()) {
            self::rollback();
        }
    }
}
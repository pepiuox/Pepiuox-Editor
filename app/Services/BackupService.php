<?php
namespace App\Services;
use App\Core\{Database, Auth};

class BackupService
{
    private static function backupsPath(): string
    {
        // Usa la constante global definida en config/paths.php
        $path = ROOT_PATH . '/storage/backups';
        if (!is_dir($path)) {
            @mkdir($path, 0775, true);
        }
        return $path;
    }

    public static function create(string $type = 'full', bool $manual = true, ?int $userId = null): array
    {
        // Corrección: parámetros correctamente vinculados
        $id = Database::insert(
            "INSERT INTO backups (filename, file_path, type, compression, status, is_manual, created_by, started_at)
             VALUES ('', '', ?, 'gzip', 'running', ?, ?, NOW())",
            [$type, $manual ? 1 : 0, $userId ?? Auth::id()]
        );

        try {
            $filename = 'backup_' . date('Ymd_His') . '_' . bin2hex(random_bytes(4));
            $fullPath = self::backupsPath() . '/' . $filename . '.sql.gz';
            $ext = 'sql.gz';

            if ($type === 'files') {
                $fullPath = self::backupFiles($filename);
                $ext = 'zip';
            } elseif ($type === 'database') {
                self::backupDatabase($fullPath);
            } else {
                // full: BD + archivos
                $dbPath = self::backupsPath() . '/' . $filename . '_db.sql.gz';
                self::backupDatabase($dbPath);
                $filesZip = self::backupFiles($filename . '_files');
                
                // Combinar en un único zip si ZipArchive está disponible
                if (class_exists('ZipArchive')) {
                    $zip = new \ZipArchive();
                    $combined = self::backupsPath() . '/' . $filename . '.zip';
                    if ($zip->open($combined, \ZipArchive::CREATE) === true) {
                        $zip->addFile($dbPath, 'database.sql.gz');
                        $zip->addFile($filesZip, 'files.zip');
                        $zip->close();
                        @unlink($dbPath);
                        @unlink($filesZip);
                        $fullPath = $combined;
                        $ext = 'zip';
                    } else {
                        $fullPath = $dbPath; // Fallback si falla el zip
                    }
                } else {
                    $fullPath = $dbPath;
                }
            }

            $size = filesize($fullPath);
            // Ruta relativa para guardar en BD (desde ROOT_PATH)
            $relPath = str_replace(ROOT_PATH . '/', '', $fullPath);

            Database::execute(
                "UPDATE backups SET filename = ?, file_path = ?, file_size = ?, status = 'completed', completed_at = NOW() WHERE id = ?",
                [$filename . '.' . $ext, $relPath, $size, $id]
            );

            return ['id' => $id, 'path' => $relPath, 'size' => $size];
        } catch (\Throwable $e) {
            Database::execute(
                "UPDATE backups SET status = 'failed', error_message = ?, completed_at = NOW() WHERE id = ?",
                [$e->getMessage(), $id]
            );
            throw $e;
        }
    }

    private static function backupDatabase(string $path): void
    {
        // Usa la constante CONFIG_PATH
        $cfg = require CONFIG_PATH . '/database.php';
        $pdo = Database::connection();

        $sql = "-- PePiuoX Editor Backup\n";
        $sql .= "-- Generated: " . date('Y-m-d H:i:s') . "\n\n";
        $sql .= "SET NAMES utf8mb4;\nSET FOREIGN_KEY_CHECKS=0;\n\n";

        $tables = $pdo->query("SHOW TABLES")->fetchAll(\PDO::FETCH_COLUMN);
        foreach ($tables as $table) {
            $create = $pdo->query("SHOW CREATE TABLE `$table`")->fetch(\PDO::FETCH_ASSOC);
            $sql .= "DROP TABLE IF EXISTS `$table`;\n";
            $sql .= $create['Create Table'] . ";\n\n";

            $rows = $pdo->query("SELECT * FROM `$table`")->fetchAll(\PDO::FETCH_ASSOC);
            if ($rows) {
                foreach ($rows as $row) {
                    $values = [];
                    foreach ($row as $v) {
                        $values[] = $v === null ? 'NULL' : $pdo->quote((string)$v);
                    }
                    $sql .= "INSERT INTO `$table` VALUES (" . implode(',', $values) . ");\n";
                }
                $sql .= "\n";
            }
        }
        $sql .= "SET FOREIGN_KEY_CHECKS=1;\n";

        $gz = gzopen($path, 'wb9');
        gzwrite($gz, $sql);
        gzclose($gz);
    }

    private static function backupFiles(string $filename): string
    {
        $zipPath = self::backupsPath() . '/' . $filename . '.zip';
        if (!class_exists('ZipArchive')) {
            throw new \RuntimeException('La extensión ZipArchive no está disponible en PHP');
        }

        $zip = new \ZipArchive();
        if ($zip->open($zipPath, \ZipArchive::CREATE) !== true) {
            throw new \RuntimeException('No se pudo crear el archivo ZIP de backup');
        }

        $uploadsPath = ROOT_PATH . '/uploads';
        if (is_dir($uploadsPath)) {
            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($uploadsPath, \RecursiveDirectoryIterator::SKIP_DOTS),
                \RecursiveIteratorIterator::SELF_FIRST
            );
            foreach ($iterator as $item) {
                if ($item->isDir()) continue;
                $relPath = str_replace($uploadsPath . '/', '', $item->getPathname());
                $zip->addFile($item->getPathname(), 'uploads/' . $relPath);
            }
        }
        $zip->close();
        return $zipPath;
    }

    public static function all(): array
    {
        return Database::fetchAll(
            "SELECT b.*, u.username AS created_by_name
               FROM backups b LEFT JOIN users u ON u.id = b.created_by
              ORDER BY b.created_at DESC"
        );
    }

    public static function find(int $id): ?array
    {
        return Database::fetch("SELECT * FROM backups WHERE id = ?", [$id]);
    }

    public static function delete(int $id): bool
    {
        $b = self::find($id);
        if (!$b) return false;
        $fullPath = ROOT_PATH . '/' . $b['file_path'];
        if (is_file($fullPath)) @unlink($fullPath);
        Database::execute("DELETE FROM backups WHERE id = ?", [$id]);
        return true;
    }

    public static function download(int $id): void
    {
        $b = self::find($id);
        if (!$b) return;
        $fullPath = ROOT_PATH . '/' . $b['file_path'];
        if (!is_file($fullPath)) return;

        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . $b['filename'] . '"');
        header('Content-Length: ' . filesize($fullPath));
        readfile($fullPath);
        exit;
    }

    public static function restore(int $backupId): bool
    {
        $backup = Database::fetch("SELECT * FROM backups WHERE id = ? AND status = 'completed'", [$backupId]);
        if (!$backup) return false;

        $fullPath = ROOT_PATH . '/' . $backup['file_path'];
        if (!is_file($fullPath)) return false;

        $sql = '';
        if (str_ends_with($fullPath, '.sql.gz')) {
            $sql = gzdecode(file_get_contents($fullPath));
        } elseif (str_ends_with($fullPath, '.zip')) {
            $zip = new \ZipArchive();
            if ($zip->open($fullPath) === true) {
                $dbContent = $zip->getFromName('database.sql.gz');
                $zip->close();
                if ($dbContent) $sql = gzdecode($dbContent);
            }
        } else {
            $sql = file_get_contents($fullPath);
        }

        if (!$sql) return false;

        $pdo = Database::connection();
        $pdo->beginTransaction();
        try {
            $statements = array_filter(array_map('trim', explode(";\n", $sql)));
            foreach ($statements as $stmt) {
                if ($stmt && !str_starts_with($stmt, '--') && !str_starts_with($stmt, 'SET ')) {
                    $pdo->exec($stmt);
                }
            }
            $pdo->commit();
            return true;
        } catch (\Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    public static function stats(): array
    {
        $total = (int)Database::fetch("SELECT COUNT(*) AS c FROM backups")['c'];
        $size = (int)Database::fetch("SELECT COALESCE(SUM(file_size),0) AS s FROM backups WHERE status='completed'")['s'];
        $last = Database::fetch("SELECT created_at FROM backups WHERE status='completed' ORDER BY created_at DESC LIMIT 1");
        return [
            'total' => $total,
            'size' => $size,
            'last_backup' => $last['created_at'] ?? null,
        ];
    }
}
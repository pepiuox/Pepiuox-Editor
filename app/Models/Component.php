<?php
namespace App\Models;
use App\Core\Database;

class Component
{
    public static function all(?string $category = null): array
    {
        $sql = "SELECT * FROM components WHERE is_active = 1";
        $params = [];
        if ($category) { $sql .= " AND category = ?"; $params[] = $category; }
        $sql .= " ORDER BY category, name";
        return Database::fetchAll($sql, $params);
    }

    public static function find(int $id): ?array
    {
        return Database::fetch("SELECT * FROM components WHERE id = ?", [$id]);
    }

    public static function create(array $data): int
    {
        return Database::insert(
            "INSERT INTO components (name, category, html, css, thumbnail, is_active, created_by) VALUES (?,?,?,?,?,?,?)",
            [
                $data['name'],
                $data['category'] ?? 'general',
                $data['html'] ?? '',
                $data['css'] ?? '',
                $data['thumbnail'] ?? null,
                $data['is_active'] ?? 1,
                $data['created_by'] ?? null,
            ]
        );
    }

    public static function update(int $id, array $data): int
    {
        $fields = []; $params = [];
        foreach (['name','category','html','css','thumbnail','is_active'] as $f) {
            if (array_key_exists($f, $data)) { $fields[] = "`$f` = ?"; $params[] = $data[$f]; }
        }
        if (!$fields) return 0;
        $params[] = $id;
        return Database::execute("UPDATE components SET " . implode(',', $fields) . " WHERE id = ?", $params);
    }

    public static function delete(int $id): int
    {
        return Database::execute("DELETE FROM components WHERE id = ?", [$id]);
    }

    public static function categories(): array
    {
        $rows = Database::fetchAll("SELECT DISTINCT category FROM components WHERE is_active = 1 ORDER BY category");
        return array_column($rows, 'category');
    }
}
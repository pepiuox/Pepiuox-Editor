<?php
namespace App\Models;
use App\Core\Database;

class Template
{
    public static function all(): array
    {
        return Database::fetchAll("SELECT * FROM templates ORDER BY name ASC");
    }
    public static function allActive(): array
    {
        return Database::fetchAll("SELECT * FROM templates WHERE is_active = 1 ORDER BY name ASC");
    }
    public static function find(int $id): ?array
    {
        return Database::fetch("SELECT * FROM templates WHERE id = ?", [$id]);
    }
    public static function create(array $data): int
    {
        if (!empty($data['is_default'])) {
            Database::execute("UPDATE templates SET is_default = 0");
        }
        return Database::insert(
            "INSERT INTO templates (name, description, content_html, content_css, content_js, content_php, grapesjs_data, is_system_template, is_active, is_default, created_by)
             VALUES (?,?,?,?,?,?,?,?,?,?,?)",
            [
                $data['name'], $data['description'] ?? '',
                $data['content_html'] ?? '', $data['content_css'] ?? '',
                $data['content_js'] ?? '', $data['content_php'] ?? '',
                $data['grapesjs_data'] ?? null,
                $data['is_system_template'] ?? 0,
                $data['is_active'] ?? 1,
                $data['is_default'] ?? 0,
                $data['created_by'] ?? null,
            ]
        );
    }
    public static function update(int $id, array $data): int
    {
        if (!empty($data['is_default'])) {
            Database::execute("UPDATE templates SET is_default = 0");
        }
        $fields = []; $params = [];
        foreach (['name','description','content_html','content_css','content_js','content_php','grapesjs_data','is_system_template','is_active','is_default'] as $f) {
            if (array_key_exists($f, $data)) { $fields[] = "`$f` = ?"; $params[] = $data[$f]; }
        }
        if (!$fields) return 0;
        $params[] = $id;
        return Database::execute("UPDATE templates SET " . implode(',', $fields) . " WHERE id = ?", $params);
    }
    public static function delete(int $id): int
    {
        return Database::execute("DELETE FROM templates WHERE id = ? AND is_system_template = 0", [$id]);
    }
    public static function duplicate(int $id): ?int
    {
        $t = self::find($id);
        if (!$t) return null;
        return self::create([
            'name' => $t['name'] . ' (copia)',
            'description' => $t['description'],
            'content_html' => $t['content_html'],
            'content_css' => $t['content_css'],
            'content_js' => $t['content_js'],
            'content_php' => $t['content_php'],
            'grapesjs_data' => $t['grapesjs_data'],
            'is_active' => 0,
        ]);
    }
}
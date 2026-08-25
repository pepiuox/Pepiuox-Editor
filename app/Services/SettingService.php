<?php
namespace App\Services;
use App\Core\Database;

class SettingService
{
    private static array $cache = [];

    public static function get(string $group, string $key, $default = null)
    {
        $k = "$group.$key";
        if (!isset(self::$cache[$k])) {
            $row = Database::fetch(
                "SELECT value, type FROM settings WHERE `group` = ? AND `key` = ?",
                [$group, $key]
            );
            self::$cache[$k] = $row ? self::cast($row['value'], $row['type']) : $default;
        }
        return self::$cache[$k];
    }

    public static function set(string $group, string $key, $value, string $type = 'string'): void
    {
        $v = is_array($value) ? json_encode($value) : (string)$value;
        Database::execute(
            "INSERT INTO settings (`group`, `key`, value, type) VALUES (?,?,?,?)
             ON DUPLICATE KEY UPDATE value = VALUES(value), type = VALUES(type)",
            [$group, $key, $v, $type]
        );
        self::$cache["$group.$key"] = self::cast($v, $type);
    }

    public static function all(): array
    {
        $rows = Database::fetchAll("SELECT * FROM settings ORDER BY `group`, `key`");
        $out = [];
        foreach ($rows as $r) $out[$r['group']][$r['key']] = self::cast($r['value'], $r['type']);
        return $out;
    }

    private static function cast($value, string $type)
    {
        return match ($type) {
            'int'  => (int)$value,
            'bool' => (bool)$value,
            'json' => json_decode($value, true),
            default => $value,
        };
    }
}
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

    /**
     * Vacía la caché de valores de esta petición.
     *
     * Necesario después de escribir: sin esto, el resto de la misma petición
     * seguiría leyendo el valor viejo. Lo llama `SettingsController` tras
     * guardar, y también `PublicThemeService` cuando cambia el grupo `public`.
     *
     * @since 2026-10-26
     */
    public static function clearCache(): void
    {
        self::$cache = [];
    }

    /**
     * Claves que se guardan aquí pero **no** se cargan en bloque.
     *
     * Son los HTML del constructor del pie: pueden pesar decenas de kilobytes y
     * `all()` se llama en cada petición pública (identidad del sitio, tema). Se
     * leen solo cuando hacen falta, con `get()`, que consulta por clave.
     */
    private const CLAVES_PESADAS = [
        'footer.builder_html',
        'footer.builder_css',
        'footer.builder_js',
        'footer.builder_data',
    ];

    public static function all(): array
    {
        $rows = Database::fetchAll("SELECT * FROM settings ORDER BY `group`, `key`");
        $out = [];
        foreach ($rows as $r) {
            if (in_array($r['group'] . '.' . $r['key'], self::CLAVES_PESADAS, true)) {
                continue;
            }
            $out[$r['group']][$r['key']] = self::cast($r['value'], $r['type']);
        }
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
<?php
namespace App\Services;
use App\Core\{Database, Session};

class TranslationService
{
    private static array $cache = [];
    private static ?string $currentLang = null;

    public static function setLang(string $code): void
    {
        self::$currentLang = $code;
        Session::set('user_locale', $code);
        self::$cache = [];
    }

    public static function getLang(): string
    {
        if (self::$currentLang) return self::$currentLang;
        $user = \App\Core\Auth::user();
        self::$currentLang = Session::get('user_locale', $user['locale'] ?? 'es');
        return self::$currentLang;
    }

    public static function get(string $key, array $params = [], ?string $lang = null): string
    {
        $lang = $lang ?? self::getLang();
        $parts = explode('.', $key, 2);
        $group = count($parts) === 2 ? $parts[0] : 'app';
        $k = count($parts) === 2 ? $parts[1] : $key;

        $cacheKey = "$lang.$group";
        if (!isset(self::$cache[$cacheKey])) {
            $rows = Database::fetchAll(
                "SELECT `key`, value FROM translations WHERE lang_code = ? AND `group` = ?",
                [$lang, $group]
            );
            self::$cache[$cacheKey] = [];
            foreach ($rows as $r) self::$cache[$cacheKey][$r['key']] = $r['value'];
        }

        $value = self::$cache[$cacheKey][$k] ?? $key;

        // Reemplazar placeholders :name
        foreach ($params as $p => $v) {
            $value = str_replace(':' . $p, (string)$v, $value);
        }
        return $value;
    }

    public static function all(string $group = 'app', ?string $lang = null): array
    {
        $lang = $lang ?? self::getLang();
        $rows = Database::fetchAll(
            "SELECT `key`, value FROM translations WHERE lang_code = ? AND `group` = ?",
            [$lang, $group]
        );
        $out = [];
        foreach ($rows as $r) $out[$r['key']] = $r['value'];
        return $out;
    }

    public static function set(string $key, string $value, ?string $lang = null, string $group = 'app'): void
    {
        $lang = $lang ?? self::getLang();
        Database::execute(
            "INSERT INTO translations (lang_code, `group`, `key`, value) VALUES (?,?,?,?)
             ON DUPLICATE KEY UPDATE value = VALUES(value)",
            [$lang, $group, $key, $value]
        );
        unset(self::$cache["$lang.$group"]);
    }

    public static function languages(): array
    {
        return Database::fetchAll("SELECT * FROM languages WHERE is_active = 1 ORDER BY sort_order");
    }
}
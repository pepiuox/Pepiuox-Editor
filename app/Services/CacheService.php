<?php
namespace App\Services;
use App\Core\Database;

class CacheService
{
    private static function path(int $pageId): string
    {
        $cfg = require dirname(__DIR__, 2) . '/config/config.php';
        return $cfg['cache']['path'] . '/pages/' . $pageId . '.html';
    }

    public static function getPage(int $pageId): ?string
    {
        if (!SettingService::get('cache', 'enabled', true)) return null;
        $file = self::path($pageId);
        if (!is_file($file)) return null;
        $ttl = (int)SettingService::get('cache', 'ttl', 3600);
        if ((time() - filemtime($file)) > $ttl) {
            @unlink($file);
            return null;
        }
        return file_get_contents($file);
    }

    public static function putPage(int $pageId, string $html): void
    {
        $dir = dirname(self::path($pageId));
        if (!is_dir($dir)) @mkdir($dir, 0775, true);
        @file_put_contents(self::path($pageId), $html);
        // También en BD (respaldo)
        try {
            Database::execute(
                "INSERT INTO page_cache (page_id, cached_content, hash, expires_at)
                 VALUES (?,?,?,DATE_ADD(NOW(), INTERVAL ? SECOND))
                 ON DUPLICATE KEY UPDATE cached_content=VALUES(cached_content), hash=VALUES(hash), expires_at=VALUES(expires_at)",
                [$pageId, $html, md5($html), (int)SettingService::get('cache','ttl',3600)]
            );
        } catch (\Throwable $e) {}
    }

    public static function invalidatePage(int $pageId): void
    {
        $file = self::path($pageId);
        if (is_file($file)) @unlink($file);
        try { Database::execute("DELETE FROM page_cache WHERE page_id = ?", [$pageId]); } catch (\Throwable $e) {}
    }

    public static function invalidateTemplatePages(int $templateId): void
    {
        $pages = \App\Models\Page::usingTemplate($templateId);
        foreach ($pages as $p) self::invalidatePage((int)$p['id']);
    }

    public static function invalidateAllPages(): void
    {
        $cfg = require dirname(__DIR__, 2) . '/config/config.php';
        $dir = $cfg['cache']['path'] . '/pages';
        if (is_dir($dir)) {
            foreach (glob("$dir/*.html") as $f) @unlink($f);
        }
        try { Database::execute("DELETE FROM page_cache"); } catch (\Throwable $e) {}
    }

    public static function regenerateAll(): int
    {
        self::invalidateAllPages();
        $pages = Database::fetchAll("SELECT * FROM pages WHERE status='published' AND is_published=1");
        $n = 0;
        foreach ($pages as $p) {
            $html = PageService::render($p);
            self::putPage((int)$p['id'], $html);
            $n++;
        }
        return $n;
    }

    public static function stats(): array
    {
        $cfg = require dirname(__DIR__, 2) . '/config/config.php';
        $dir = $cfg['cache']['path'] . '/pages';
        $files = is_dir($dir) ? glob("$dir/*.html") : [];
        $size = 0;
        foreach ($files as $f) $size += filesize($f);
        return [
            'files' => count($files),
            'size' => $size,
            'enabled' => (bool)SettingService::get('cache','enabled',true),
            'ttl' => (int)SettingService::get('cache','ttl',3600),
        ];
    }
}
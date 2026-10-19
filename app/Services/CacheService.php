<?php
namespace App\Services;

use App\Core\Database;
use App\Models\Page;
use Throwable;

/**
 * Servicio de caché para páginas y datos
 *
 * Combina funcionalidades de caché en archivos y base de datos
 *
 * @package App\Services
 */
class CacheService
{
    private Database $db;
    private bool $enabled = true;
    private int $defaultDuration = 3600;
    private string $cachePath;
    private string $pageCacheDir = 'pages/';

    /**
     * Constructor con inyección de Database
     */
    public function __construct(Database $db)
    {
        $this->db = $db;
        $this->initializeCachePath();
        $this->loadConfiguration();
    }

    // ═══════════════════════════════════════════════════════════════
    //  MÉTODOS ESTÁTICOS (compatibilidad con llamadas legacy)
    // ═══════════════════════════════════════════════════════════════

    /**
     * Instancia singleton para llamadas estáticas
     */
    private static ?self $instance = null;

    /**
     * Obtiene la instancia singleton (requiere que se haya construido antes)
     */
    public static function getInstance(): ?self
    {
        return self::$instance;
    }

    /**
     * Registra la instancia para uso estático
     */
    public static function setInstance(self $instance): void
    {
        self::$instance = $instance;
    }

    /**
     * ✅ MÉTODO ESTÁTICO: Invalida todas las páginas
     * Permite llamadas como CacheService::invalidateAllPages()
     */
    public static function invalidateAllPages(): void
    {
        $instance = self::getInstance();
        if ($instance === null) {
            error_log('[CacheService] No hay instancia registrada para invalidateAllPages()');
            return;
        }
        $instance->doInvalidateAllPages();
    }

    // ═══════════════════════════════════════════════════════════════
    //  INICIALIZACIÓN
    // ═══════════════════════════════════════════════════════════════

    private function initializeCachePath(): void
    {
        $cfg = $this->loadConfig();
        $this->cachePath = $cfg['cache']['path']
            ?? (defined('STORAGE_PATH') ? STORAGE_PATH . '/cache' : sys_get_temp_dir() . '/app-cache');

        if (!is_dir($this->cachePath)) {
            @mkdir($this->cachePath, 0775, true);
        }

        $pageDir = $this->cachePath . '/' . $this->pageCacheDir;
        if (!is_dir($pageDir)) {
            @mkdir($pageDir, 0775, true);
        }
    }

    private function loadConfiguration(): void
    {
        // ✅ Fallback seguro si SettingService no existe
        try {
            if (class_exists(SettingService::class)) {
                $this->enabled = (bool) SettingService::get('cache', 'enabled', true);
                $this->defaultDuration = (int) SettingService::get('cache', 'ttl', 3600);
                return;
            }
        } catch (Throwable $e) {
            // Silencioso
        }

        // Valores por defecto
        $this->enabled = true;
        $this->defaultDuration = 3600;
    }

    private function loadConfig(): array
    {
        $configPath = defined('CONFIG_PATH')
            ? CONFIG_PATH . '/config.php'
            : dirname(__DIR__, 2) . '/config/config.php';

        if (is_file($configPath)) {
            return require $configPath;
        }
        return ['cache' => ['path' => $this->cachePath ?? sys_get_temp_dir() . '/app-cache']];
    }

    // ═══════════════════════════════════════════════════════════════
    //  CACHÉ DE PÁGINAS
    // ═══════════════════════════════════════════════════════════════

    private function getPageCachePath(int $pageId): string
    {
        return $this->cachePath . '/' . $this->pageCacheDir . $pageId . '.html';
    }

    public function getPage(int $pageId): ?string
    {
        if (!$this->enabled) return null;

        $filePath = $this->getPageCachePath($pageId);
        if (is_file($filePath)) {
            if ((time() - filemtime($filePath)) > $this->defaultDuration) {
                @unlink($filePath);
                return null;
            }
            return file_get_contents($filePath);
        }

        try {
            $result = $this->db->fetch(
                "SELECT cached_content FROM page_cache
                 WHERE page_id = ? AND expires_at > NOW()
                 ORDER BY created_at DESC LIMIT 1",
                [$pageId]
            );
            if ($result) {
                $this->saveToFile($pageId, $result['cached_content']);
                return $result['cached_content'];
            }
        } catch (Throwable $e) {
            error_log('[CacheService] Error getPage: ' . $e->getMessage());
        }
        return null;
    }

    public function setPage(int $pageId, string $content, ?int $duration = null): bool
    {
        if (!$this->enabled) return false;

        $duration = $duration ?? $this->defaultDuration;
        $hash = hash('sha256', $content);
        $expiresAt = date('Y-m-d H:i:s', time() + $duration);

        try {
            $this->saveToFile($pageId, $content);

            $this->db->execute(
                "INSERT INTO page_cache (page_id, cached_content, hash, expires_at, created_at)
                 VALUES (?, ?, ?, ?, NOW())
                 ON DUPLICATE KEY UPDATE
                    cached_content = VALUES(cached_content),
                    hash = VALUES(hash),
                    expires_at = VALUES(expires_at),
                    updated_at = NOW()",
                [$pageId, $content, $hash, $expiresAt]
            );

            $this->clearOldVersions($pageId);
            return true;
        } catch (Throwable $e) {
            error_log('[CacheService] Error setPage: ' . $e->getMessage());
            return false;
        }
    }

    private function saveToFile(int $pageId, string $content): void
    {
        $filePath = $this->getPageCachePath($pageId);
        $dir = dirname($filePath);
        if (!is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }
        @file_put_contents($filePath, $content);
        @chmod($filePath, 0644);
    }

    private function clearOldVersions(int $pageId): void
    {
        try {
            $this->db->execute(
                "DELETE FROM page_cache WHERE page_id = ? AND id NOT IN (
                    SELECT id FROM (
                        SELECT id FROM page_cache
                        WHERE page_id = ?
                        ORDER BY created_at DESC LIMIT 5
                    ) as temp
                )",
                [$pageId, $pageId]
            );
        } catch (Throwable $e) {
            // Silencioso
        }
    }

    public function clearPageCache(int $pageId): bool
    {
        try {
            $filePath = $this->getPageCachePath($pageId);
            if (is_file($filePath)) {
                @unlink($filePath);
            }
            $this->db->execute("DELETE FROM page_cache WHERE page_id = ?", [$pageId]);
            return true;
        } catch (Throwable $e) {
            error_log('[CacheService] Error clearPageCache: ' . $e->getMessage());
            return false;
        }
    }

    // ═══════════════════════════════════════════════════════════════
    //  INVALIDACIÓN (instancias y estático)
    // ═══════════════════════════════════════════════════════════════

    /**
     * Implementación real de invalidación (instancia)
     */
    public function doInvalidateAllPages(): void
    {
        $pageDir = $this->cachePath . '/' . $this->pageCacheDir;
        if (is_dir($pageDir)) {
            $files = glob($pageDir . '/*.html') ?: [];
            foreach ($files as $file) {
                @unlink($file);
            }
        }

        try {
            $this->db->execute("DELETE FROM page_cache");
        } catch (Throwable $e) {
            error_log('[CacheService] Error doInvalidateAllPages: ' . $e->getMessage());
        }
    }

    /**
     * Devuelve la instancia registrada o crea una si aún no existe.
     */
    private static function instanceOrCreate(): ?self
    {
        if (self::$instance !== null) {
            return self::$instance;
        }

        try {
            self::$instance = new self(new Database());
        } catch (Throwable $e) {
            error_log('[CacheService] No se pudo crear la instancia: ' . $e->getMessage());
        }

        return self::$instance;
    }

    /**
     * ✅ MÉTODO ESTÁTICO: Invalida la caché de una página concreta.
     * Permite llamadas como CacheService::invalidatePage($id), que es como lo
     * usan PageService, PagesController, PageController, SeoController y
     * VersionsController. Antes no existía y lanzaba "Call to undefined method".
     */
    public static function invalidatePage(int $pageId): void
    {
        $instance = self::instanceOrCreate();
        if ($instance === null) {
            return;
        }
        $instance->clearPageCache($pageId);
    }

    /**
     * ✅ MÉTODO ESTÁTICO: Invalida las páginas que usan una plantilla.
     */
    public static function invalidateTemplatePages(int $templateId): void
    {
        $instance = self::instanceOrCreate();
        if ($instance === null) {
            return;
        }
        $instance->doInvalidateTemplatePages($templateId);
    }

    /**
     * ✅ MÉTODO ESTÁTICO: Guarda el HTML renderizado de una página en caché.
     */
    public static function putPage(int $pageId, string $content, ?int $duration = null): bool
    {
        $instance = self::instanceOrCreate();
        if ($instance === null) {
            return false;
        }
        return $instance->setPage($pageId, $content, $duration);
    }

    public function invalidateRelatedPages(string $slug): void
    {
        try {
            $pages = $this->db->fetchAll(
                "SELECT id FROM pages WHERE slug LIKE ? OR content_html LIKE ?",
                ["%{$slug}%", "%{$slug}%"]
            );
            foreach ($pages as $page) {
                $this->clearPageCache((int)$page['id']);
            }
        } catch (Throwable $e) {
            error_log('[CacheService] Error invalidateRelatedPages: ' . $e->getMessage());
        }
    }

    /**
     * Implementación real de invalidación por plantilla (instancia)
     */
    public function doInvalidateTemplatePages(int $templateId): void
    {
        try {
            if (!class_exists(Page::class)) return;
            $pages = Page::usingTemplate($templateId);
            foreach ($pages as $page) {
                $this->clearPageCache((int)$page['id']);
            }
        } catch (Throwable $e) {
            error_log('[CacheService] Error invalidateTemplatePages: ' . $e->getMessage());
        }
    }

    // ═══════════════════════════════════════════════════════════════
    //  CACHÉ KEY-VALUE
    // ═══════════════════════════════════════════════════════════════

    public function get(string $key)
    {
        $fileKey = md5($key);
        $filePath = $this->cachePath . '/data/' . $fileKey . '.json';

        if (is_file($filePath)) {
            if ((time() - filemtime($filePath)) <= $this->defaultDuration) {
                $data = json_decode(file_get_contents($filePath), true);
                if ($data !== null) return $data;
            }
            @unlink($filePath);
        }

        try {
            $result = $this->db->fetch(
                "SELECT cache_value FROM cache_settings
                 WHERE cache_key = ? AND (expires_at IS NULL OR expires_at > NOW())",
                [$key]
            );
            if ($result) {
                $data = json_decode($result['cache_value'], true);
                $this->saveDataToFile($key, $data);
                return $data;
            }
        } catch (Throwable $e) {
            error_log('[CacheService] Error get: ' . $e->getMessage());
        }
        return null;
    }

    public function set(string $key, $value, ?int $duration = null): bool
    {
        $duration = $duration ?? $this->defaultDuration;
        $expiresAt = $duration ? date('Y-m-d H:i:s', time() + $duration) : null;
        $jsonValue = json_encode($value);

        try {
            $this->saveDataToFile($key, $value);
            return (bool)$this->db->execute(
                "INSERT INTO cache_settings (cache_key, cache_value, expires_at, updated_at)
                 VALUES (?, ?, ?, NOW())
                 ON DUPLICATE KEY UPDATE
                    cache_value = VALUES(cache_value),
                    expires_at = VALUES(expires_at),
                    updated_at = NOW()",
                [$key, $jsonValue, $expiresAt]
            );
        } catch (Throwable $e) {
            error_log('[CacheService] Error set: ' . $e->getMessage());
            return false;
        }
    }

    public function delete(string $key): bool
    {
        try {
            $fileKey = md5($key);
            $filePath = $this->cachePath . '/data/' . $fileKey . '.json';
            if (is_file($filePath)) @unlink($filePath);

            return (bool)$this->db->execute(
                "DELETE FROM cache_settings WHERE cache_key = ?",
                [$key]
            );
        } catch (Throwable $e) {
            error_log('[CacheService] Error delete: ' . $e->getMessage());
            return false;
        }
    }

    private function saveDataToFile(string $key, $value): void
    {
        $fileKey = md5($key);
        $dir = $this->cachePath . '/data/';
        if (!is_dir($dir)) @mkdir($dir, 0775, true);
        @file_put_contents($dir . $fileKey . '.json', json_encode($value));
        @chmod($dir . $fileKey . '.json', 0644);
    }

    // ═══════════════════════════════════════════════════════════════
    //  MANTENIMIENTO
    // ═══════════════════════════════════════════════════════════════

    public function clearExpiredCache(): int
    {
        $count = 0;
        $pageDir = $this->cachePath . '/' . $this->pageCacheDir;

        if (is_dir($pageDir)) {
            $files = glob($pageDir . '/*.html') ?: [];
            foreach ($files as $file) {
                if ((time() - filemtime($file)) > $this->defaultDuration) {
                    @unlink($file);
                    $count++;
                }
            }
        }

        try {
            $this->db->execute("DELETE FROM page_cache WHERE expires_at <= NOW()");
        } catch (Throwable $e) {
            // Silencioso
        }
        return $count;
    }

    public function clearAllCache(): bool
    {
        try {
            $pageDir = $this->cachePath . '/' . $this->pageCacheDir;
            if (is_dir($pageDir)) {
                $files = glob($pageDir . '/*.html') ?: [];
                foreach ($files as $file) @unlink($file);
            }
            $this->db->execute("DELETE FROM page_cache");
            $this->db->execute("DELETE FROM cache_settings");
            return true;
        } catch (Throwable $e) {
            error_log('[CacheService] Error clearAllCache: ' . $e->getMessage());
            return false;
        }
    }

    public function getCacheStats(): array
    {
        $stats = [
            'files' => 0,
            'size' => 0,
            'enabled' => $this->enabled,
            'ttl' => $this->defaultDuration,
            'database' => ['total' => 0, 'active' => 0, 'expired' => 0],
        ];

        $pageDir = $this->cachePath . '/' . $this->pageCacheDir;
        if (is_dir($pageDir)) {
            $files = glob($pageDir . '/*.html') ?: [];
            $stats['files'] = count($files);
            foreach ($files as $file) $stats['size'] += filesize($file);
        }

        try {
            $result = $this->db->fetch(
                "SELECT
                    COUNT(*) as total,
                    COUNT(CASE WHEN expires_at > NOW() THEN 1 END) as active,
                    COUNT(CASE WHEN expires_at <= NOW() THEN 1 END) as expired
                 FROM page_cache"
            );
            if ($result) {
                $stats['database']['total']   = (int)($result['total'] ?? 0);
                $stats['database']['active']  = (int)($result['active'] ?? 0);
                $stats['database']['expired'] = (int)($result['expired'] ?? 0);
            }
        } catch (Throwable $e) {
            // Silencioso
        }
        return $stats;
    }

    public function enable(bool $enabled = true): void
    {
        $this->enabled = $enabled;
        try {
            if (class_exists(SettingService::class)) {
                SettingService::set('cache', 'enabled', $enabled);
            }
        } catch (Throwable $e) {
            // Silencioso
        }
    }

    public function setDefaultDuration(int $seconds): void
    {
        $this->defaultDuration = $seconds;
        try {
            if (class_exists(SettingService::class)) {
                SettingService::set('cache', 'ttl', $seconds);
            }
        } catch (Throwable $e) {
            // Silencioso
        }
    }
}
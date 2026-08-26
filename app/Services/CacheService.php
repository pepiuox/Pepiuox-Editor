<?php
namespace App\Services;

use App\Core\Database;
use App\Core\CacheManager;
use App\Models\Page;
use PDO;
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
    private int $defaultDuration = 3600; // 1 hora
    private string $cachePath;
    private string $pageCacheDir = 'pages/';

    public function __construct(Database $db)
    {
        $this->db = $db;
        $this->initializeCachePath();
        $this->loadConfiguration();
    }

    /**
     * Inicializa la ruta de caché
     */
    private function initializeCachePath(): void
    {
        $cfg = $this->loadConfig();
        $this->cachePath = $cfg['cache']['path'] ?? STORAGE_PATH . '/cache';
        
        // Crear directorio si no existe
        if (!is_dir($this->cachePath)) {
            mkdir($this->cachePath, 0775, true);
        }

        // Crear directorio para páginas
        $pageDir = $this->cachePath . '/' . $this->pageCacheDir;
        if (!is_dir($pageDir)) {
            mkdir($pageDir, 0775, true);
        }
    }

    /**
     * Carga configuración
     */
    private function loadConfiguration(): void
    {
        try {
            $this->enabled = (bool)SettingService::get('cache', 'enabled', true);
            $this->defaultDuration = (int)SettingService::get('cache', 'ttl', 3600);
        } catch (Throwable $e) {
            // Si SettingService no está disponible, usar valores por defecto
            $this->enabled = true;
            $this->defaultDuration = 3600;
        }
    }

    /**
     * Carga archivo de configuración
     */
    private function loadConfig(): array
    {
        $configPath = dirname(__DIR__, 2) . '/config/config.php';
        if (is_file($configPath)) {
            return require $configPath;
        }
        return ['cache' => ['path' => STORAGE_PATH . '/cache', 'enabled' => true, 'ttl' => 3600]];
    }

    /**
     * Obtiene la ruta del archivo de caché de una página
     */
    private function getPageCachePath(int $pageId): string
    {
        return $this->cachePath . '/' . $this->pageCacheDir . $pageId . '.html';
    }

    /**
     * Obtiene página cacheada (primero de archivo, luego de BD)
     */
    public function getPage(int $pageId): ?string
    {
        if (!$this->enabled) {
            return null;
        }

        // 1. Intentar obtener de archivo
        $filePath = $this->getPageCachePath($pageId);
        if (is_file($filePath)) {
            $ttl = $this->defaultDuration;
            if ((time() - filemtime($filePath)) > $ttl) {
                @unlink($filePath);
                return null;
            }
            return file_get_contents($filePath);
        }

        // 2. Intentar obtener de base de datos (respaldo)
        $result = $this->db->fetch(
            "SELECT cached_content FROM page_cache 
             WHERE page_id = ? AND expires_at > NOW() 
             ORDER BY created_at DESC LIMIT 1",
            [$pageId]
        );

        if ($result) {
            // Guardar en archivo para futuras consultas
            $this->saveToFile($pageId, $result['cached_content']);
            return $result['cached_content'];
        }

        return null;
    }

    /**
     * Guarda página en caché (archivo y BD)
     */
    public function setPage(int $pageId, string $content, ?int $duration = null): bool
    {
        if (!$this->enabled) {
            return false;
        }

        $duration = $duration ?? $this->defaultDuration;
        $hash = hash('sha256', $content);
        $expiresAt = date('Y-m-d H:i:s', time() + $duration);

        try {
            // 1. Guardar en archivo
            $this->saveToFile($pageId, $content);

            // 2. Guardar en base de datos (respaldo)
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

            // 3. Limpiar versiones antiguas (mantener solo 5)
            $this->clearOldVersions($pageId);

            return true;

        } catch (Throwable $e) {
            error_log('[CacheService] Error al guardar cache: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Guarda contenido en archivo
     */
    private function saveToFile(int $pageId, string $content): void
    {
        $filePath = $this->getPageCachePath($pageId);
        $dir = dirname($filePath);
        if (!is_dir($dir)) {
            mkdir($dir, 0775, true);
        }
        file_put_contents($filePath, $content);
        chmod($filePath, 0644);
    }

    /**
     * Limpia versiones antiguas de caché
     */
    private function clearOldVersions(int $pageId): void
    {
        // Mantener solo las 5 últimas versiones en BD
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
    }

    /**
     * Limpia caché de una página
     */
    public function clearPageCache(int $pageId): bool
    {
        try {
            // 1. Eliminar archivo
            $filePath = $this->getPageCachePath($pageId);
            if (is_file($filePath)) {
                @unlink($filePath);
            }

            // 2. Eliminar de BD
            $this->db->execute(
                "DELETE FROM page_cache WHERE page_id = ?",
                [$pageId]
            );

            return true;

        } catch (Throwable $e) {
            error_log('[CacheService] Error al limpiar cache: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Limpia caché expirado
     */
    public function clearExpiredCache(): int
    {
        $count = 0;

        // 1. Limpiar archivos expirados
        $ttl = $this->defaultDuration;
        $pageDir = $this->cachePath . '/' . $this->pageCacheDir;
        if (is_dir($pageDir)) {
            $files = glob($pageDir . '/*.html');
            foreach ($files as $file) {
                if ((time() - filemtime($file)) > $ttl) {
                    @unlink($file);
                    $count++;
                }
            }
        }

        // 2. Limpiar BD expirada
        $deleted = $this->db->execute(
            "DELETE FROM page_cache WHERE expires_at <= NOW()"
        );

        return $count + ($deleted ? 1 : 0);
    }

    /**
     * Limpia todo el caché
     */
    public function clearAllCache(): bool
    {
        try {
            // 1. Limpiar archivos
            $pageDir = $this->cachePath . '/' . $this->pageCacheDir;
            if (is_dir($pageDir)) {
                $files = glob($pageDir . '/*.html');
                foreach ($files as $file) {
                    @unlink($file);
                }
            }

            // 2. Limpiar BD
            $this->db->execute("DELETE FROM page_cache");

            // 3. Limpiar cache general
            $this->db->execute("DELETE FROM cache_settings");

            return true;

        } catch (Throwable $e) {
            error_log('[CacheService] Error al limpiar todo el cache: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Obtiene estadísticas de caché
     */
    public function getCacheStats(): array
    {
        $stats = [
            'files' => 0,
            'size' => 0,
            'enabled' => $this->enabled,
            'ttl' => $this->defaultDuration,
            'database' => [
                'total' => 0,
                'active' => 0,
                'expired' => 0
            ]
        ];

        // 1. Estadísticas de archivos
        $pageDir = $this->cachePath . '/' . $this->pageCacheDir;
        if (is_dir($pageDir)) {
            $files = glob($pageDir . '/*.html');
            $stats['files'] = count($files);
            foreach ($files as $file) {
                $stats['size'] += filesize($file);
            }
        }

        // 2. Estadísticas de BD
        $result = $this->db->fetch(
            "SELECT 
                COUNT(*) as total,
                COUNT(CASE WHEN expires_at > NOW() THEN 1 END) as active,
                COUNT(CASE WHEN expires_at <= NOW() THEN 1 END) as expired
             FROM page_cache"
        );

        if ($result) {
            $stats['database']['total'] = (int)($result['total'] ?? 0);
            $stats['database']['active'] = (int)($result['active'] ?? 0);
            $stats['database']['expired'] = (int)($result['expired'] ?? 0);
        }

        return $stats;
    }

    /**
     * Cache general (key-value) - MEJORADO
     */
    public function get(string $key)
    {
        // 1. Intentar de archivo (memoria cache)
        $fileKey = md5($key);
        $filePath = $this->cachePath . '/data/' . $fileKey . '.json';
        if (is_file($filePath)) {
            $ttl = $this->defaultDuration;
            if ((time() - filemtime($filePath)) <= $ttl) {
                $data = json_decode(file_get_contents($filePath), true);
                if ($data !== null) {
                    return $data;
                }
            }
            @unlink($filePath);
        }

        // 2. Intentar de BD
        $result = $this->db->fetch(
            "SELECT cache_value FROM cache_settings 
             WHERE cache_key = ? AND (expires_at IS NULL OR expires_at > NOW())",
            [$key]
        );

        if ($result) {
            $data = json_decode($result['cache_value'], true);
            // Guardar en archivo para futuras consultas
            $this->saveDataToFile($key, $data);
            return $data;
        }

        return null;
    }

    /**
     * Guarda cache general
     */
    public function set(string $key, $value, ?int $duration = null): bool
    {
        $duration = $duration ?? $this->defaultDuration;
        $expiresAt = $duration ? date('Y-m-d H:i:s', time() + $duration) : null;
        $jsonValue = json_encode($value);

        try {
            // 1. Guardar en archivo
            $this->saveDataToFile($key, $value);

            // 2. Guardar en BD
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
            error_log('[CacheService] Error al guardar cache: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Guarda datos en archivo
     */
    private function saveDataToFile(string $key, $value): void
    {
        $fileKey = md5($key);
        $dir = $this->cachePath . '/data/';
        if (!is_dir($dir)) {
            mkdir($dir, 0775, true);
        }
        $filePath = $dir . $fileKey . '.json';
        file_put_contents($filePath, json_encode($value));
        chmod($filePath, 0644);
    }

    /**
     * Elimina cache general
     */
    public function delete(string $key): bool
    {
        try {
            // 1. Eliminar archivo
            $fileKey = md5($key);
            $filePath = $this->cachePath . '/data/' . $fileKey . '.json';
            if (is_file($filePath)) {
                @unlink($filePath);
            }

            // 2. Eliminar de BD
            return $this->db->execute(
                "DELETE FROM cache_settings WHERE cache_key = ?",
                [$key]
            );

        } catch (Throwable $e) {
            error_log('[CacheService] Error al eliminar cache: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Invalida caché de páginas relacionadas con un slug
     */
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
            error_log('[CacheService] Error al invalidar páginas relacionadas: ' . $e->getMessage());
        }
    }

    /**
     * Invalida páginas de un template específico
     */
    public function invalidateTemplatePages(int $templateId): void
    {
        try {
            $pages = Page::usingTemplate($templateId);
            foreach ($pages as $page) {
                $this->clearPageCache((int)$page['id']);
            }
        } catch (Throwable $e) {
            error_log('[CacheService] Error al invalidar páginas del template: ' . $e->getMessage());
        }
    }

    /**
     * Invalida todas las páginas
     */
    public function invalidateAllPages(): void
    {
        // 1. Eliminar archivos
        $pageDir = $this->cachePath . '/' . $this->pageCacheDir;
        if (is_dir($pageDir)) {
            $files = glob($pageDir . '/*.html');
            foreach ($files as $file) {
                @unlink($file);
            }
        }

        // 2. Eliminar de BD
        try {
            $this->db->execute("DELETE FROM page_cache");
        } catch (Throwable $e) {
            error_log('[CacheService] Error al invalidar todas las páginas: ' . $e->getMessage());
        }
    }

    /**
     * Regenera todas las páginas en caché
     */
    public function regenerateAll(callable $renderFunction = null): int
    {
        $this->invalidateAllPages();

        try {
            $pages = $this->db->fetchAll(
                "SELECT * FROM pages WHERE status = 'published' AND is_published = 1"
            );
        } catch (Throwable $e) {
            error_log('[CacheService] Error al obtener páginas: ' . $e->getMessage());
            return 0;
        }

        $count = 0;
        foreach ($pages as $page) {
            try {
                if ($renderFunction !== null) {
                    $html = $renderFunction($page);
                } else {
                    // Usar PageService si está disponible
                    if (class_exists('App\Services\PageService')) {
                        $html = \App\Services\PageService::render($page);
                    } else {
                        // Fallback básico
                        $html = $this->generateBasicPageHtml($page);
                    }
                }
                $this->setPage((int)$page['id'], $html);
                $count++;
            } catch (Throwable $e) {
                error_log('[CacheService] Error al regenerar página ' . $page['id'] . ': ' . $e->getMessage());
            }
        }

        return $count;
    }

    /**
     * Genera HTML básico para una página (fallback)
     */
    private function generateBasicPageHtml(array $page): string
    {
        ob_start();
        ?>
        <!DOCTYPE html>
        <html lang="es">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title><?= htmlspecialchars($page['title'] ?? '') ?></title>
            <?php if (!empty($page['content_css'])): ?>
            <style><?= $page['content_css'] ?></style>
            <?php endif; ?>
        </head>
        <body>
            <article>
                <?= $page['content_html'] ?? '' ?>
            </article>
            <?php if (!empty($page['content_js'])): ?>
            <script><?= $page['content_js'] ?></script>
            <?php endif; ?>
        </body>
        </html>
        <?php
        return ob_get_clean();
    }

    /**
     * Obtiene contenido cacheado de página con metadatos
     */
    public function getPageWithMetadata(int $pageId): ?array
    {
        $result = $this->db->fetch(
            "SELECT * FROM page_cache 
             WHERE page_id = ? AND expires_at > NOW() 
             ORDER BY created_at DESC LIMIT 1",
            [$pageId]
        );

        if ($result) {
            $result['from_file'] = is_file($this->getPageCachePath($pageId));
        }

        return $result ?: null;
    }

    /**
     * Verifica si una página tiene caché válido
     */
    public function hasValidCache(int $pageId): bool
    {
        // 1. Verificar archivo
        $filePath = $this->getPageCachePath($pageId);
        if (is_file($filePath)) {
            $ttl = $this->defaultDuration;
            if ((time() - filemtime($filePath)) <= $ttl) {
                return true;
            }
        }

        // 2. Verificar BD
        $result = $this->db->fetch(
            "SELECT id FROM page_cache 
             WHERE page_id = ? AND expires_at > NOW() 
             LIMIT 1",
            [$pageId]
        );

        return $result !== null;
    }

    /**
     * Activa/desactiva caché
     */
    public function enable(bool $enabled = true): void
    {
        $this->enabled = $enabled;
        try {
            SettingService::set('cache', 'enabled', $enabled);
        } catch (Throwable $e) {
            // Silencioso
        }
    }

    /**
     * Establece duración por defecto
     */
    public function setDefaultDuration(int $seconds): void
    {
        $this->defaultDuration = $seconds;
        try {
            SettingService::set('cache', 'ttl', $seconds);
        } catch (Throwable $e) {
            // Silencioso
        }
    }

    /**
     * Limpia caché de archivos expirados (mantenimiento)
     */
    public function cleanExpiredFiles(): int
    {
        $count = 0;
        $ttl = $this->defaultDuration;

        // Limpiar caché de páginas
        $pageDir = $this->cachePath . '/' . $this->pageCacheDir;
        if (is_dir($pageDir)) {
            $files = glob($pageDir . '/*.html');
            foreach ($files as $file) {
                if ((time() - filemtime($file)) > $ttl) {
                    @unlink($file);
                    $count++;
                }
            }
        }

        // Limpiar caché de datos
        $dataDir = $this->cachePath . '/data/';
        if (is_dir($dataDir)) {
            $files = glob($dataDir . '/*.json');
            foreach ($files as $file) {
                if ((time() - filemtime($file)) > $ttl) {
                    @unlink($file);
                    $count++;
                }
            }
        }

        return $count;
    }

    /**
     * Obtiene el tamaño total del caché en disco
     */
    public function getTotalSize(): int
    {
        $size = 0;
        $directories = [
            $this->cachePath . '/' . $this->pageCacheDir,
            $this->cachePath . '/data/'
        ];

        foreach ($directories as $dir) {
            if (is_dir($dir)) {
                $files = glob($dir . '*');
                foreach ($files as $file) {
                    if (is_file($file)) {
                        $size += filesize($file);
                    }
                }
            }
        }

        return $size;
    }
}
<?php
namespace App\Core;

use App\Services\CacheService;
use ZipArchive;
use RecursiveIteratorIterator;
use RecursiveDirectoryIterator;
use Throwable;

/**
 * Gestor de caché de archivos
 * 
 * @package App\Core
 */
class CacheManager
{
    private CacheService $cacheService;
    private string $baseCacheDir = 'cache/pages/';

    public function __construct(CacheService $cacheService)
    {
        $this->cacheService = $cacheService;
        $this->ensureCacheDirectory();
    }

    /**
     * Asegura que el directorio de caché existe
     */
    private function ensureCacheDirectory(): void
    {
        $fullPath = ROOT_PATH . '/' . $this->baseCacheDir;
        if (!is_dir($fullPath)) {
            mkdir($fullPath, 0755, true);
        }
    }

    /**
     * Limpia caché de una página específica
     */
    public function clearPageCache(int $pageId): void
    {
        $this->cacheService->clearPageCache($pageId);
        $this->deleteCacheDirectory($pageId);
    }

    /**
     * Limpia todo el caché
     */
    public function clearAllCache(): void
    {
        $this->cacheService->clearAllCache();
        $this->deleteCacheDirectory();
    }

    /**
     * Elimina directorio de caché
     */
    private function deleteCacheDirectory(?int $pageId = null): void
    {
        $path = ROOT_PATH . '/' . $this->baseCacheDir;
        if ($pageId !== null) {
            $path .= $pageId . '/';
        }

        if (is_dir($path)) {
            $this->removeDirectory($path);
        }

        if ($pageId === null) {
            $this->ensureCacheDirectory();
        }
    }

    /**
     * Elimina directorio recursivamente
     */
    private function removeDirectory(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }

        $files = array_diff(scandir($dir), ['.', '..']);
        foreach ($files as $file) {
            $path = $dir . '/' . $file;
            if (is_dir($path)) {
                $this->removeDirectory($path);
            } else {
                unlink($path);
            }
        }
        rmdir($dir);
    }

    /**
     * Regenera todas las páginas en caché
     */
    public function regenerateAllCache(callable $pageLoader): int
    {
        $pages = $pageLoader();
        $count = 0;

        foreach ($pages as $page) {
            if (!empty($page['id'])) {
                $this->cacheService->clearPageCache($page['id']);
                $count++;
            }
        }

        return $count;
    }

    /**
     * Obtiene estadísticas del caché
     */
    public function getCacheStats(): array
    {
        $stats = [
            'total_pages' => 0,
            'total_size' => 0,
            'pages' => []
        ];

        $path = ROOT_PATH . '/' . $this->baseCacheDir;
        if (!is_dir($path)) {
            return $stats;
        }

        $directories = array_diff(scandir($path), ['.', '..']);

        foreach ($directories as $dir) {
            $dirPath = $path . $dir . '/';
            if (is_dir($dirPath)) {
                $pageStats = $this->getPageCacheStats($dirPath);
                if ($pageStats) {
                    $stats['total_pages']++;
                    $stats['total_size'] += $pageStats['size'];
                    $stats['pages'][$dir] = $pageStats;
                }
            }
        }

        return $stats;
    }

    /**
     * Obtiene estadísticas de una página específica
     */
    private function getPageCacheStats(string $dir): ?array
    {
        if (!is_dir($dir)) {
            return null;
        }

        $files = array_diff(scandir($dir), ['.', '..']);
        $size = 0;
        $fileCount = 0;
        $fileTypes = [];

        foreach ($files as $file) {
            $path = $dir . $file;
            if (is_file($path)) {
                $size += filesize($path);
                $fileCount++;
                $ext = pathinfo($file, PATHINFO_EXTENSION);
                if (!isset($fileTypes[$ext])) {
                    $fileTypes[$ext] = 0;
                }
                $fileTypes[$ext]++;
            }
        }

        // Leer metadatos si existen
        $metadata = null;
        $metadataFile = $dir . 'metadata.json';
        if (is_file($metadataFile)) {
            $metadata = json_decode(file_get_contents($metadataFile), true);
        }

        return [
            'size' => $size,
            'file_count' => $fileCount,
            'file_types' => $fileTypes,
            'metadata' => $metadata,
            'last_modified' => filemtime($dir)
        ];
    }

    /**
     * Obtiene todas las páginas cacheadas
     */
    public function getCachedPages(): array
    {
        $pages = [];
        $path = ROOT_PATH . '/' . $this->baseCacheDir;

        if (!is_dir($path)) {
            return $pages;
        }

        $directories = array_diff(scandir($path), ['.', '..']);

        foreach ($directories as $dir) {
            $dirPath = $path . $dir . '/';
            if (is_dir($dirPath) && is_file($dirPath . 'html.html')) {
                $pages[] = $dir;
            }
        }

        return $pages;
    }

    /**
     * Obtiene contenido específico de una página
     */
    public function getPageContent(int $pageId, string $type = 'html'): ?string
    {
        $filePath = ROOT_PATH . '/' . $this->baseCacheDir . $pageId . '/' . $type . '.html';
        if (is_file($filePath)) {
            return file_get_contents($filePath);
        }
        return null;
    }

    /**
     * Exporta página caché a un archivo ZIP
     */
    public function exportPageCache(int $pageId): ?string
    {
        $dir = ROOT_PATH . '/' . $this->baseCacheDir . $pageId . '/';
        if (!is_dir($dir)) {
            return null;
        }

        $zipFile = ROOT_PATH . '/storage/exports/cache_export_' . $pageId . '_' . date('Y-m-d') . '.zip';
        $zipDir = dirname($zipFile);
        if (!is_dir($zipDir)) {
            mkdir($zipDir, 0755, true);
        }

        $zip = new ZipArchive();
        if ($zip->open($zipFile, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            return null;
        }

        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($dir),
            RecursiveIteratorIterator::LEAVES_ONLY
        );

        foreach ($files as $name => $file) {
            if (!$file->isDir()) {
                $filePath = $file->getRealPath();
                $relativePath = substr($filePath, strlen($dir));
                $zip->addFile($filePath, $relativePath);
            }
        }

        $zip->close();
        return $zipFile;
    }

    /**
     * Limpia caché expirado
     */
    public function cleanExpiredCache(): int
    {
        $count = 0;
        $path = ROOT_PATH . '/' . $this->baseCacheDir;

        if (!is_dir($path)) {
            return 0;
        }

        $directories = array_diff(scandir($path), ['.', '..']);

        foreach ($directories as $dir) {
            $dirPath = $path . $dir . '/';
            if (is_dir($dirPath)) {
                $metadataFile = $dirPath . 'metadata.json';
                if (is_file($metadataFile)) {
                    $metadata = json_decode(file_get_contents($metadataFile), true);
                    if (isset($metadata['expires_at']) && strtotime($metadata['expires_at']) < time()) {
                        $this->removeDirectory($dirPath);
                        $count++;
                    }
                }
            }
        }

        return $count;
    }
}
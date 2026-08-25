<?php
namespace App\Services;
use App\Models\Page;

class SitemapService
{
    public static function generate(): string
    {
        $xml = self::buildXml();
        $path = dirname(__DIR__, 2) . '/public/sitemap.xml';
        file_put_contents($path, $xml);
        return $xml;
    }

    public static function getXml(): string
    {
        $path = dirname(__DIR__, 2) . '/public/sitemap.xml';
        if (!is_file($path) || (time() - filemtime($path)) > 3600) {
            return self::generate();
        }
        return file_get_contents($path);
    }

    private static function buildXml(): string
{
    $cfg = require CONFIG_PATH . '/config.php';
    $base = rtrim($cfg['app_url'] ?? BASE_URL, '/');
    $pages = \App\Models\Page::publishedForSitemap();

    $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
    $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
    
    // Página de inicio siempre con prioridad máxima
    $home = \App\Models\Page::home();
    if ($home) {
        $lastmod = $home['updated_at'] ? date('Y-m-d', strtotime($home['updated_at'])) : date('Y-m-d');
        $xml .= "  <url>\n";
        $xml .= "    <loc>$base/</loc>\n";
        $xml .= "    <lastmod>$lastmod</lastmod>\n";
        $xml .= "    <changefreq>daily</changefreq>\n";
        $xml .= "    <priority>1.0</priority>\n";
        $xml .= "  </url>\n";
    }

    foreach ($pages as $p) {
        if ($home && $p['slug'] === $home['slug']) continue; // Ya incluida
        
        $loc = $base . '/' . ltrim($p['slug'], '/');
        $lastmod = $p['updated_at'] ? date('Y-m-d', strtotime($p['updated_at'])) : date('Y-m-d');
        
        // Frecuencia y prioridad según antigüedad
        $daysOld = (time() - strtotime($p['updated_at'] ?? 'now')) / 86400;
        $freq = $daysOld < 7 ? 'daily' : ($daysOld < 30 ? 'weekly' : 'monthly');
        $priority = $daysOld < 7 ? '0.9' : ($daysOld < 30 ? '0.7' : '0.5');
        
        $xml .= "  <url>\n";
        $xml .= "    <loc>" . htmlspecialchars($loc) . "</loc>\n";
        $xml .= "    <lastmod>$lastmod</lastmod>\n";
        $xml .= "    <changefreq>$freq</changefreq>\n";
        $xml .= "    <priority>$priority</priority>\n";
        $xml .= "  </url>\n";
    }
    $xml .= '</urlset>';
    return $xml;
}
}
<?php
namespace App\Services;
use App\Core\Database;

class SeoService
{
    /** Calcula el score SEO (0-100) de una página */
    public static function score(array $page, array $seo): array
    {
        $score = 0;
        $suggestions = [];
        $title = $seo['basic']['title'] ?? '';
        $desc = $seo['basic']['description'] ?? '';
        $html = $page['content_html'] ?? '';
        $h1 = $page['h1'] ?? '';

        // 1. Meta title (15 pts)
        $titleLen = mb_strlen($title);
        if ($titleLen >= 30 && $titleLen <= 60) {
            $score += 15;
        } elseif ($titleLen > 0) {
            $score += 8;
            $suggestions[] = ['type'=>'warning', 'msg'=>"El título tiene $titleLen caracteres. Lo ideal es 30-60."];
        } else {
            $suggestions[] = ['type'=>'error', 'msg'=>'Falta el meta título.'];
        }

        // 2. Meta description (15 pts)
        $descLen = mb_strlen($desc);
        if ($descLen >= 120 && $descLen <= 160) {
            $score += 15;
        } elseif ($descLen > 0) {
            $score += 8;
            $suggestions[] = ['type'=>'warning', 'msg'=>"La descripción tiene $descLen caracteres. Lo ideal es 120-160."];
        } else {
            $suggestions[] = ['type'=>'error', 'msg'=>'Falta la meta descripción.'];
        }

        // 3. H1 único (15 pts)
        $h1Count = preg_match_all('/<h1\b/i', $html);
        if ($h1Count === 1) {
            $score += 15;
        } elseif ($h1Count > 1) {
            $score += 5;
            $suggestions[] = ['type'=>'warning', 'msg'=>'Hay múltiples H1. Debería haber solo uno.'];
        } else {
            $suggestions[] = ['type'=>'error', 'msg'=>'Falta el H1 en el contenido.'];
        }

        // 4. Imágenes con alt (10 pts)
        preg_match_all('/<img\b[^>]*>/i', $html, $imgs);
        $totalImgs = count($imgs[0]);
        $imgsWithAlt = 0;
        foreach ($imgs[0] as $img) {
            if (preg_match('/alt\s*=\s*["\'][^"\']+["\']/i', $img)) $imgsWithAlt++;
        }
        if ($totalImgs === 0) {
            $score += 10;
        } elseif ($imgsWithAlt === $totalImgs) {
            $score += 10;
        } else {
            $score += (int)round(10 * $imgsWithAlt / $totalImgs);
            $suggestions[] = ['type'=>'warning', 'msg'=>($totalImgs-$imgsWithAlt).' imágenes sin atributo alt.'];
        }

        // 5. URL amigable (slug) (10 pts)
        if (!empty($page['slug']) && preg_match('/^[a-z0-9-]+$/', $page['slug'])) {
            $score += 10;
        } else {
            $suggestions[] = ['type'=>'warning', 'msg'=>'El slug debería ser corto, en minúsculas y con guiones.'];
        }

        // 6. Open Graph (10 pts)
        if (!empty($seo['og']['title']) && !empty($seo['og']['description']) && !empty($seo['og']['image'])) {
            $score += 10;
        } else {
            $suggestions[] = ['type'=>'info', 'msg'=>'Configura Open Graph (title, description, image) para redes sociales.'];
        }

        // 7. Twitter Card (5 pts)
        if (!empty($seo['twitter']['title']) && !empty($seo['twitter']['card'])) {
            $score += 5;
        } else {
            $suggestions[] = ['type'=>'info', 'msg'=>'Configura Twitter Card para mejorar la vista en X/Twitter.'];
        }

        // 8. Contenido mínimo (10 pts)
        $textLen = mb_strlen(strip_tags($html));
        if ($textLen >= 300) {
            $score += 10;
        } elseif ($textLen >= 100) {
            $score += 5;
            $suggestions[] = ['type'=>'warning', 'msg'=>'El contenido es corto. Lo ideal es >300 palabras.'];
        } else {
            $suggestions[] = ['type'=>'warning', 'msg'=>'Contenido muy corto. Añade más texto relevante.'];
        }

        // 9. Canonical URL (5 pts)
        if (!empty($seo['basic']['canonical'])) {
            $score += 5;
        }

        // 10. Enlaces internos (5 pts)
        preg_match_all('/<a\b[^>]*href=["\']\/[^"\']*["\']/i', $html, $links);
        if (count($links[0]) > 0) {
            $score += 5;
        } else {
            $suggestions[] = ['type'=>'info', 'msg'=>'Añade enlaces internos a otras páginas del sitio.'];
        }

        return [
            'score' => min(100, $score),
            'suggestions' => $suggestions,
            'stats' => [
                'title_length' => $titleLen,
                'desc_length' => $descLen,
                'h1_count' => $h1Count,
                'images_total' => $totalImgs,
                'images_with_alt' => $imgsWithAlt,
                'content_length' => $textLen,
                'internal_links' => count($links[0]),
            ]
        ];
    }

    /** Genera JSON-LD Schema.org para la página */
    public static function jsonLd(array $page, array $seo): string
    {
        $cfg = require CONFIG_PATH . '/config.php';
        $siteName = SettingService::get('general', 'site_name', 'PePiuoX Maker');
        $baseUrl = rtrim($cfg['app_url'] ?? BASE_URL, '/');

        $data = [
            '@context' => 'https://schema.org',
            '@type' => 'WebPage',
            'name' => $seo['basic']['title'] ?? $page['title'],
            'headline' => $page['h1'] ?? $page['title'],
            'description' => $seo['basic']['description'] ?? $page['description'] ?? '',
            'url' => $baseUrl . '/' . ltrim($page['slug'], '/'),
            'datePublished' => $page['created_at'] ?? date('c'),
            'dateModified' => $page['updated_at'] ?? date('c'),
            'publisher' => [
                '@type' => 'Organization',
                'name' => $siteName,
            ],
        ];

        if (!empty($seo['og']['image'])) {
            $data['image'] = $seo['og']['image'];
        }

        return '<script type="application/ld+json">' . json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) . '</script>';
    }

    /** Genera robots.txt dinámico */
    public static function robotsTxt(): string
    {
        $cfg = require CONFIG_PATH . '/config.php';
        $baseUrl = rtrim($cfg['app_url'] ?? BASE_URL, '/');
        
        $txt = "User-agent: *\n";
        $txt .= "Allow: /\n";
        $txt .= "Disallow: /admin/\n";
        $txt .= "Disallow: /login\n";
        $txt .= "Disallow: /forgot\n";
        $txt .= "Disallow: /reset\n";
        $txt .= "Disallow: /api/\n";
        $txt .= "Disallow: /cron/\n\n";
        $txt .= "Sitemap: $baseUrl/sitemap.xml\n";
        
        return $txt;
    }

    /** Historial de cambios SEO */
    public static function logChange(?int $pageId, string $action, ?int $scoreBefore, ?int $scoreAfter, array $changes = []): void
    {
        try {
            Database::insert(
                "INSERT INTO seo_audit (page_id, user_id, action, score_before, score_after, changes) VALUES (?,?,?,?,?,?)",
                [
                    $pageId,
                    \App\Core\Auth::id(),
                    $action,
                    $scoreBefore,
                    $scoreAfter,
                    json_encode($changes, JSON_UNESCAPED_UNICODE),
                ]
            );
        } catch (\Throwable $e) {}
    }
}
<?php
namespace App\Services;

class SeoCompetitorService
{
    /**
     * Analiza la página y la compara con patrones de páginas top de Google.
     * Genera un "perfil competitivo" con score y recomendaciones.
     */
    public static function analyze(array $page, array $seo): array
    {
        $html = $page['content_html'] ?? '';
        $text = strip_tags($html);
        $words = str_word_count($text, 1);
        $wordCount = count($words);
        
        // ═══ ANÁLISIS ESTRUCTURAL ═══
        $structure = self::analyzeStructure($html);
        
        // ═══ ANÁLISIS DE CONTENIDO ═══
        $content = self::analyzeContent($text, $words, $wordCount);
        
        // ═══ ANÁLISIS TÉCNICO ═══
        $technical = self::analyzeTechnical($page, $seo);
        
        // ═══ ANÁLISIS DE KEYWORDS ═══
        $keywords = self::analyzeKeywords($text, $words, $wordCount);
        
        // ═══ ANÁLISIS DE ENLACES ═══
        $links = self::analyzeLinks($html);
        
        // ═══ ANÁLISIS DE MEDIOS ═══
        $media = self::analyzeMedia($html);
        
        // ═══ SCORE COMPETITIVO (0-100) ═══
        $competitiveScore = self::calculateCompetitiveScore(
            $structure, $content, $technical, $keywords, $links, $media
        );
        
        // ═══ COMPARATIVA CON TOP 10 DE GOOGLE ═══
        $benchmark = self::getBenchmark();
        $comparison = self::compareWithBenchmark($competitiveScore, $structure, $content, $keywords, $links, $benchmark);
        
        // ═══ RECOMENDACIONES PRIORIZADAS ═══
        $recommendations = self::generateRecommendations(
            $structure, $content, $technical, $keywords, $links, $media, $comparison
        );
        
        return [
            'competitive_score' => $competitiveScore,
            'structure' => $structure,
            'content' => $content,
            'technical' => $technical,
            'keywords' => $keywords,
            'links' => $links,
            'media' => $media,
            'benchmark' => $benchmark,
            'comparison' => $comparison,
            'recommendations' => $recommendations,
        ];
    }

    /** Analiza la estructura HTML (headings, secciones) */
    private static function analyzeStructure(string $html): array
    {
        $h1 = preg_match_all('/<h1\b[^>]*>(.*?)<\/h1>/is', $html, $m);
        $h2 = preg_match_all('/<h2\b[^>]*>(.*?)<\/h2>/is', $html);
        $h3 = preg_match_all('/<h3\b[^>]*>(.*?)<\/h3>/is', $html);
        $h4 = preg_match_all('/<h4\b[^>]*>(.*?)<\/h4>/is', $html);
        $sections = preg_match_all('/<(section|article|div)\b[^>]*class=["\'][^"\']*(section|container|row|col)/i', $html);
        $lists = preg_match_all('/<(ul|ol)\b/i', $html);
        
        return [
            'h1_count' => $h1,
            'h1_text' => $h1 > 0 ? strip_tags($m[1][0] ?? '') : '',
            'h2_count' => $h2,
            'h3_count' => $h3,
            'h4_count' => $h4,
            'total_headings' => $h1 + $h2 + $h3 + $h4,
            'sections' => $sections,
            'lists' => $lists,
            'has_proper_hierarchy' => ($h1 === 1 && $h2 > 0),
        ];
    }

    /** Analiza el contenido (palabras, oraciones, legibilidad) */
    private static function analyzeContent(string $text, array $words, int $wordCount): array
    {
        $sentences = preg_split('/[.!?]+/', $text, -1, PREG_SPLIT_NO_EMPTY);
        $paragraphs = preg_split('/\n\s*\n/', $text, -1, PREG_SPLIT_NO_EMPTY);
        
        // Índice de legibilidad simplificado (Flesch adaptado al español)
        $syllables = 0;
        foreach (array_slice($words, 0, 200) as $word) {
            $syllables += max(1, preg_match_all('/[aeiouáéíóúü]+/i', $word));
        }
        $avgWordsPerSentence = count($sentences) > 0 ? $wordCount / count($sentences) : 0;
        $readability = max(0, min(100, 206.84 - (0.6 * ($syllables / max(1, count($words)))) - (1.02 * $avgWordsPerSentence)));
        
        // Densidad de keywords (palabras más frecuentes excluyendo stopwords)
        $stopwords = ['el','la','los','las','un','una','unos','unas','de','del','al','a','en','y','o','u','que','es','por','con','para','su','sus','se','son','como','más','pero','sus','le','lo','si','no','ha','hay'];
        $filtered = array_filter($words, fn($w) => strlen($w) > 3 && !in_array(strtolower($w), $stopwords));
        $freq = array_count_values(array_map('strtolower', $filtered));
        arsort($freq);
        $topKeywords = array_slice($freq, 0, 10, true);
        
        return [
            'word_count' => $wordCount,
            'sentence_count' => count($sentences),
            'paragraph_count' => count($paragraphs),
            'avg_words_per_sentence' => round($avgWordsPerSentence, 1),
            'readability_score' => round($readability),
            'top_keywords' => $topKeywords,
            'reading_time_minutes' => max(1, (int)ceil($wordCount / 200)),
        ];
    }

    /** Analiza aspectos técnicos SEO */
    private static function analyzeTechnical(array $page, array $seo): array
    {
        $title = $seo['basic']['title'] ?? '';
        $desc = $seo['basic']['description'] ?? '';
        $slug = $page['slug'] ?? '';
        
        return [
            'title_length' => mb_strlen($title),
            'title_optimal' => mb_strlen($title) >= 30 && mb_strlen($title) <= 60,
            'desc_length' => mb_strlen($desc),
            'desc_optimal' => mb_strlen($desc) >= 120 && mb_strlen($desc) <= 160,
            'slug_length' => mb_strlen($slug),
            'slug_optimal' => mb_strlen($slug) <= 60 && preg_match('/^[a-z0-9-]+$/', $slug),
            'has_canonical' => !empty($seo['basic']['canonical']),
            'has_og' => !empty($seo['og']['title']) && !empty($seo['og']['image']),
            'has_twitter' => !empty($seo['twitter']['card']),
            'has_json_ld' => true, // Siempre se genera
            'has_h1' => !empty($page['h1']),
        ];
    }

    /** Analiza keywords y densidad */
    private static function analyzeKeywords(string $text, array $words, int $wordCount): array
    {
        // Detectar keyword principal (la más frecuente de 2+ palabras)
        $bigrams = [];
        for ($i = 0; $i < count($words) - 1; $i++) {
            $bigram = strtolower($words[$i] . ' ' . $words[$i+1]);
            if (strlen($bigram) > 6) {
                $bigrams[$bigram] = ($bigrams[$bigram] ?? 0) + 1;
            }
        }
        arsort($bigrams);
        $mainKeyword = key($bigrams) ?: '';
        $mainKeywordCount = current($bigrams) ?: 0;
        $keywordDensity = $wordCount > 0 ? ($mainKeywordCount / $wordCount) * 100 : 0;
        
        return [
            'main_keyword' => $mainKeyword,
            'main_keyword_count' => $mainKeywordCount,
            'keyword_density' => round($keywordDensity, 2),
            'density_optimal' => $keywordDensity >= 1 && $keywordDensity <= 3,
            'top_bigrams' => array_slice($bigrams, 0, 5, true),
        ];
    }

    /** Analiza enlaces internos y externos */
    private static function analyzeLinks(string $html): array
    {
        preg_match_all('/<a\b[^>]*href=["\']([^"\']+)["\'][^>]*>(.*?)<\/a>/is', $html, $matches);
        $internal = 0;
        $external = 0;
        $nofollow = 0;
        $anchors = [];
        
        foreach ($matches[1] as $i => $href) {
            if (str_starts_with($href, '/') || str_starts_with($href, '#') || !str_starts_with($href, 'http')) {
                $internal++;
            } else {
                $external++;
            }
            if (stripos($matches[0][$i], 'nofollow') !== false) $nofollow++;
            $anchorText = strip_tags($matches[2][$i]);
            if (strlen($anchorText) > 2) $anchors[] = $anchorText;
        }
        
        return [
            'internal' => $internal,
            'external' => $external,
            'nofollow' => $nofollow,
            'total' => count($matches[1]),
            'anchor_texts' => array_slice($anchors, 0, 10),
            'has_diversity' => count(array_unique($anchors)) > 3,
        ];
    }

    /** Analiza imágenes y medios */
    private static function analyzeMedia(string $html): array
    {
        preg_match_all('/<img\b[^>]*>/i', $html, $imgs);
        $withAlt = 0;
        $withTitle = 0;
        foreach ($imgs[0] as $img) {
            if (preg_match('/alt\s*=\s*["\'][^"\']+["\']/i', $img)) $withAlt++;
            if (preg_match('/title\s*=\s*["\'][^"\']+["\']/i', $img)) $withTitle++;
        }
        
        $videos = preg_match_all('/<(video|iframe)\b/i', $html);
        
        return [
            'images_total' => count($imgs[0]),
            'images_with_alt' => $withAlt,
            'images_with_title' => $withTitle,
            'alt_coverage' => count($imgs[0]) > 0 ? round(($withAlt / count($imgs[0])) * 100) : 100,
            'videos' => $videos,
        ];
    }

    /** Calcula el score competitivo total */
    private static function calculateCompetitiveScore(array $structure, array $content, array $technical, array $keywords, array $links, array $media): int
    {
        $score = 0;
        
        // Estructura (25 pts)
        if ($structure['h1_count'] === 1) $score += 8;
        if ($structure['has_proper_hierarchy']) $score += 7;
        if ($structure['h2_count'] >= 2) $score += 5;
        if ($structure['lists'] > 0) $score += 3;
        if ($structure['sections'] > 0) $score += 2;
        
        // Contenido (30 pts)
        if ($content['word_count'] >= 300) $score += 10;
        elseif ($content['word_count'] >= 150) $score += 5;
        if ($content['readability_score'] >= 60) $score += 8;
        elseif ($content['readability_score'] >= 40) $score += 4;
        if ($content['paragraph_count'] >= 3) $score += 5;
        if (count($content['top_keywords']) >= 5) $score += 4;
        if ($content['sentence_count'] >= 5) $score += 3;
        
        // Técnico (20 pts)
        if ($technical['title_optimal']) $score += 5;
        if ($technical['desc_optimal']) $score += 5;
        if ($technical['slug_optimal']) $score += 3;
        if ($technical['has_canonical']) $score += 2;
        if ($technical['has_og']) $score += 3;
        if ($technical['has_twitter']) $score += 2;
        
        // Keywords (10 pts)
        if ($keywords['density_optimal']) $score += 6;
        elseif ($keywords['keyword_density'] > 0) $score += 3;
        if (!empty($keywords['main_keyword'])) $score += 4;
        
        // Enlaces (10 pts)
        if ($links['internal'] >= 3) $score += 5;
        elseif ($links['internal'] > 0) $score += 2;
        if ($links['external'] >= 1) $score += 3;
        if ($links['has_diversity']) $score += 2;
        
        // Medios (5 pts)
        if ($media['alt_coverage'] === 100) $score += 3;
        elseif ($media['alt_coverage'] >= 50) $score += 1;
        if ($media['images_total'] >= 2) $score += 2;
        
        return min(100, $score);
    }

    /** Benchmark promedio de páginas top 10 de Google */
    private static function getBenchmark(): array
    {
        return [
            'word_count' => 1200,
            'h1_count' => 1,
            'h2_count' => 6,
            'images' => 5,
            'internal_links' => 8,
            'external_links' => 3,
            'reading_time' => 6,
            'title_length' => 55,
            'desc_length' => 150,
            'avg_competitive_score' => 78,
        ];
    }

    /** Compara con el benchmark */
    private static function compareWithBenchmark(int $score, array $structure, array $content, array $keywords, array $links, array $benchmark): array
    {
        return [
            'score_vs_benchmark' => $score - $benchmark['avg_competitive_score'],
            'words_vs_benchmark' => $content['word_count'] - $benchmark['word_count'],
            'h2_vs_benchmark' => $structure['h2_count'] - $benchmark['h2_count'],
            'images_vs_benchmark' => ($content['word_count'] / 300) - $benchmark['images'],
            'links_vs_benchmark' => ($links['internal'] + $links['external']) - ($benchmark['internal_links'] + $benchmark['external_links']),
            'level' => $score >= 80 ? 'excellent' : ($score >= 60 ? 'good' : ($score >= 40 ? 'fair' : 'poor')),
        ];
    }

    /** Genera recomendaciones priorizadas */
    private static function generateRecommendations(array $structure, array $content, array $technical, array $keywords, array $links, array $media, array $comparison): array
    {
        $recs = [];
        $priority = 1;
        
        // Críticas
        if ($structure['h1_count'] === 0) {
            $recs[] = ['priority' => $priority++, 'severity' => 'critical', 'title' => 'Falta el H1', 'description' => 'Añade un único H1 con tu keyword principal.', 'impact' => 'Alto'];
        } elseif ($structure['h1_count'] > 1) {
            $recs[] = ['priority' => $priority++, 'severity' => 'critical', 'title' => 'Múltiples H1', 'description' => 'Tienes ' . $structure['h1_count'] . ' H1. Deja solo uno.', 'impact' => 'Alto'];
        }
        
        if ($content['word_count'] < 300) {
            $recs[] = ['priority' => $priority++, 'severity' => 'critical', 'title' => 'Contenido muy corto', 'description' => 'Tienes ' . $content['word_count'] . ' palabras. El benchmark son 1200. Añade al menos 600 palabras.', 'impact' => 'Alto'];
        }
        
        if (!$technical['title_optimal']) {
            $recs[] = ['priority' => $priority++, 'severity' => 'high', 'title' => 'Meta title no óptimo', 'description' => 'Actual: ' . $technical['title_length'] . ' caracteres. Ideal: 30-60.', 'impact' => 'Alto'];
        }
        
        if (!$technical['desc_optimal']) {
            $recs[] = ['priority' => $priority++, 'severity' => 'high', 'title' => 'Meta description no óptima', 'description' => 'Actual: ' . $technical['desc_length'] . ' caracteres. Ideal: 120-160.', 'impact' => 'Alto'];
        }
        
        // Medias
        if ($structure['h2_count'] < 3) {
            $recs[] = ['priority' => $priority++, 'severity' => 'medium', 'title' => 'Pocos subtítulos H2', 'description' => 'Añade más H2 para estructurar el contenido. Benchmark: 6.', 'impact' => 'Medio'];
        }
        
        if ($media['alt_coverage'] < 100) {
            $recs[] = ['priority' => $priority++, 'severity' => 'medium', 'title' => 'Imágenes sin alt', 'description' => $media['images_total'] - $media['images_with_alt'] . ' imágenes sin atributo alt.', 'impact' => 'Medio'];
        }
        
        if ($links['internal'] < 3) {
            $recs[] = ['priority' => $priority++, 'severity' => 'medium', 'title' => 'Pocos enlaces internos', 'description' => 'Añade enlaces a otras páginas del sitio. Benchmark: 8.', 'impact' => 'Medio'];
        }
        
        if (!$keywords['density_optimal'] && !empty($keywords['main_keyword'])) {
            $recs[] = ['priority' => $priority++, 'severity' => 'medium', 'title' => 'Densidad de keyword no óptima', 'description' => 'Actual: ' . $keywords['keyword_density'] . '%. Ideal: 1-3%.', 'impact' => 'Medio'];
        }
        
        // Bajas
        if (!$technical['has_og']) {
            $recs[] = ['priority' => $priority++, 'severity' => 'low', 'title' => 'Falta Open Graph', 'description' => 'Configura OG para mejorar compartir en redes sociales.', 'impact' => 'Bajo'];
        }
        
        if ($content['readability_score'] < 60) {
            $recs[] = ['priority' => $priority++, 'severity' => 'low', 'title' => 'Legibilidad mejorable', 'description' => 'Usa frases más cortas y párrafos más pequeños.', 'impact' => 'Bajo'];
        }
        
        if ($links['external'] === 0) {
            $recs[] = ['priority' => $priority++, 'severity' => 'low', 'title' => 'Sin enlaces externos', 'description' => 'Añade enlaces a fuentes de autoridad para mejorar credibilidad.', 'impact' => 'Bajo'];
        }
        
        return $recs;
    }
}
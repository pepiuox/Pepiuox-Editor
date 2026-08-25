<?php
namespace App\Services;

class MetaDescriptionGenerator
{
    /**
     * Genera 3 variantes de meta description a partir del contenido HTML.
     * Cada una con diferente enfoque: resumen, keyword-focused, CTA.
     */
    public static function generate(string $html, string $title = '', string $keyword = ''): array
    {
        $text = self::cleanHtml($html);
        $sentences = self::extractSentences($text);
        
        if (empty($sentences)) {
            return [
                ['variant' => 'empty', 'text' => '', 'score' => 0],
                ['variant' => 'empty', 'text' => '', 'score' => 0],
                ['variant' => 'empty', 'text' => '', 'score' => 0],
            ];
        }
        
        $variants = [];
        
        // ═══ VARIANTE 1: RESUMEN (primeras oraciones relevantes) ═══
        $summary = self::buildSummary($sentences, 155);
        $variants[] = [
            'variant' => 'summary',
            'label' => 'Resumen automático',
            'text' => $summary,
            'length' => mb_strlen($summary),
            'score' => self::scoreDescription($summary, $keyword),
        ];
        
        // ═══ VARIANTE 2: KEYWORD-FOCUSED ═══
        $keywordDesc = self::buildKeywordFocused($sentences, $title, $keyword, 155);
        $variants[] = [
            'variant' => 'keyword',
            'label' => 'Enfocada en keyword',
            'text' => $keywordDesc,
            'length' => mb_strlen($keywordDesc),
            'score' => self::scoreDescription($keywordDesc, $keyword),
        ];
        
        // ═══ VARIANTE 3: CTA (Call to Action) ═══
        $ctaDesc = self::buildWithCta($sentences, $title, 155);
        $variants[] = [
            'variant' => 'cta',
            'label' => 'Con llamada a la acción',
            'text' => $ctaDesc,
            'length' => mb_strlen($ctaDesc),
            'score' => self::scoreDescription($ctaDesc, $keyword),
        ];
        
        // Ordenar por score
        usort($variants, fn($a, $b) => $b['score'] <=> $a['score']);
        
        return $variants;
    }

    /** Limpia el HTML y extrae texto plano */
    private static function cleanHtml(string $html): string
    {
        // Eliminar scripts y estilos
        $html = preg_replace('/<(script|style|nav|footer|header)\b[^>]*>.*?<\/\1>/is', '', $html);
        $text = strip_tags($html);
        $text = html_entity_decode($text, ENT_QUOTES, 'UTF-8');
        $text = preg_replace('/\s+/', ' ', $text);
        return trim($text);
    }

    /** Extrae oraciones del texto */
    private static function extractSentences(string $text): array
    {
        // Dividir por puntos, excluyendo abreviaturas comunes
        $text = preg_replace('/\b(Sr|Sra|Srta|Dr|Dra|Ing|Lic|Ud|Uds|etc|vs|EE\.UU)\./i', '\1@DOT@', $text);
        $parts = preg_split('/[.!?]+\s+/', $text, -1, PREG_SPLIT_NO_EMPTY);
        $sentences = [];
        foreach ($parts as $p) {
            $p = trim(str_replace('@DOT@', '.', $p));
            if (mb_strlen($p) >= 15 && mb_strlen($p) <= 200) {
                $sentences[] = $p;
            }
        }
        return $sentences;
    }

    /** Construye un resumen con las primeras oraciones */
    private static function buildSummary(array $sentences, int $maxLength): string
    {
        $result = '';
        foreach ($sentences as $s) {
            $test = $result ? $result . ' ' . $s : $s;
            if (mb_strlen($test) > $maxLength) break;
            $result = $test;
        }
        return self::finalize($result, $maxLength);
    }

    /** Construye descripción enfocada en keyword */
    private static function buildKeywordFocused(array $sentences, string $title, string $keyword, int $maxLength): string
    {
        if (empty($keyword)) {
            return self::buildSummary($sentences, $maxLength);
        }
        
        // Buscar oraciones que contengan la keyword
        $relevant = [];
        foreach ($sentences as $s) {
            if (stripos($s, $keyword) !== false) {
                $relevant[] = $s;
            }
        }
        
        if (empty($relevant)) {
            // Si no hay keyword en el contenido, usar las primeras oraciones
            return self::buildSummary($sentences, $maxLength);
        }
        
        // Construir con la primera oración relevante + contexto
        $result = $relevant[0];
        foreach ($sentences as $s) {
            if ($s === $relevant[0]) continue;
            $test = $result . ' ' . $s;
            if (mb_strlen($test) > $maxLength) break;
            $result = $test;
            if (mb_strlen($result) >= $maxLength - 20) break;
        }
        
        return self::finalize($result, $maxLength);
    }

    /** Construye descripción con CTA */
    private static function buildWithCta(array $sentences, string $title, int $maxLength): string
    {
        $ctaOptions = [
            'Descubre más aquí.',
            'Entra y conoce todos los detalles.',
            'Visítanos para saber más.',
            'Lee el artículo completo.',
            'Explora todo el contenido.',
            'Consulta toda la información.',
        ];
        
        // Tomar la primera oración como gancho
        $hook = $sentences[0] ?? '';
        $hook = mb_strlen($hook) > 80 ? mb_substr($hook, 0, 77) . '...' : $hook;
        
        // Añadir CTA
        foreach ($ctaOptions as $cta) {
            $test = $hook . ' ' . $cta;
            if (mb_strlen($test) <= $maxLength) {
                return $test;
            }
        }
        
        return self::finalize($hook, $maxLength);
    }

    /** Finaliza la descripción (trunca inteligentemente) */
    private static function finalize(string $text, int $maxLength): string
    {
        $text = trim($text);
        if (mb_strlen($text) <= $maxLength) {
            // Asegurar que termina en punto
            if (!preg_match('/[.!?]$/', $text)) {
                $text .= '.';
            }
            return $text;
        }
        
        // Truncar en límite de palabra
        $truncated = mb_substr($text, 0, $maxLength - 3);
        $lastSpace = mb_strrpos($truncated, ' ');
        if ($lastSpace !== false && $lastSpace > $maxLength * 0.7) {
            $truncated = mb_substr($truncated, 0, $lastSpace);
        }
        return rtrim($truncated, ' ,;') . '...';
    }

    /** Puntúa una descripción (0-100) */
    private static function scoreDescription(string $desc, string $keyword): int
    {
        $score = 0;
        $len = mb_strlen($desc);
        
        // Longitud óptima (40 pts)
        if ($len >= 120 && $len <= 160) $score += 40;
        elseif ($len >= 100 && $len <= 170) $score += 25;
        elseif ($len >= 50) $score += 10;
        
        // Contiene keyword (30 pts)
        if ($keyword && stripos($desc, $keyword) !== false) {
            $score += 30;
            // Bonus si está al principio
            if (stripos($desc, $keyword) < 30) $score += 5;
        }
        
        // Termina en punto o CTA (15 pts)
        if (preg_match('/[.!?]$/', $desc)) $score += 10;
        if (preg_match('/(descubre|entra|visita|lee|explora|consulta)/i', $desc)) $score += 5;
        
        // No es repetitiva (15 pts)
        $words = str_word_count(strtolower($desc), 1);
        $unique = count(array_unique($words));
        if ($unique / max(1, count($words)) > 0.7) $score += 15;
        
        return min(100, $score);
    }
}
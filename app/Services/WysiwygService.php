<?php
namespace App\Services;

class WysiwygService
{
    /** Sanitiza HTML de TinyMCE (mantiene etiquetas seguras). */
    public static function sanitize(string $html): string
    {
        // Permitir etiquetas comunes de Bootstrap + contenido
        $allowed = '<p><br><h1><h2><h3><h4><h5><h6><a><img><ul><ol><li>'
                 . '<blockquote><pre><code><strong><em><u><s><sub><sup>'
                 . '<table><thead><tbody><tr><th><td><div><span><figure><figcaption>'
                 . '<hr><video><audio><source><iframe>';

        // Eliminar scripts y eventos on*
        $html = preg_replace('/<script\b[^>]*>.*?<\/script>/is', '', $html);
        $html = preg_replace('/\son\w+\s*=\s*["\'][^"\']*["\']/i', '', $html);
        $html = preg_replace('/\son\w+\s*=\s*[^\s>]*/i', '', $html);
        $html = preg_replace('/javascript\s*:/i', '', $html);
        $html = preg_replace('/data\s*:\s*text\/html/i', '', $html);

        return strip_tags($html, $allowed);
    }

    /** Extrae el primer párrafo como resumen. */
    public static function extractExcerpt(string $html, int $length = 160): string
    {
        $text = strip_tags($html);
        $text = preg_replace('/\s+/', ' ', $text);
        $text = trim($text);
        if (mb_strlen($text) <= $length) return $text;
        return mb_substr($text, 0, $length - 3) . '...';
    }

    /** Reemplaza URLs de imágenes relativas por absolutas. */
    public static function absoluteUrls(string $html, string $baseUrl): string
    {
        return preg_replace_callback(
            '/(src|href)=["\'](\/uploads\/[^"\']+)["\']/',
            fn($m) => $m[1] . '="' . rtrim($baseUrl, '/') . $m[2] . '"',
            $html
        );
    }
}
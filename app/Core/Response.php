<?php
namespace App\Core;

class Response
{
    public static function render(string $html, int $code = 200): void
    {
        http_response_code($code);
        header('Content-Type: text/html; charset=utf-8');
        echo $html;
        exit;
    }

    public static function json($data, int $code = 200): void
    {
        http_response_code($code);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        exit;
    }

    public static function redirect(string $url, int $code = 302): void
    {
        http_response_code($code);
        header('Location: ' . $url);
        exit;
    }

    public static function abort(int $code, string $message = ''): void
    {
        http_response_code($code);
        $titles = [404=>'No encontrado',403=>'Prohibido',500=>'Error interno'];
        $title = $titles[$code] ?? 'Error';
        echo "<h1>$code $title</h1><p>" . htmlspecialchars($message) . "</p>";
        exit;
    }
}
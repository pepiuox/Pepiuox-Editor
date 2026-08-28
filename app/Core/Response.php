<?php
namespace App\Core;

/**
 * Clase Response: encapsula las respuestas HTTP
 */
class Response
{
    public static function render(string $html, int $code = 200): void
    {
        if (headers_sent($file, $line)) {
            error_log("[Response] Headers already sent in $file:$line");
            echo $html;
            return;
        }
        http_response_code($code);
        header('Content-Type: text/html; charset=utf-8');
        header('X-Content-Type-Options: nosniff');
        echo $html;
        exit;
    }

    public static function json($data, int $code = 200): void
    {
        if (headers_sent($file, $line)) {
            error_log("[Response] Headers already sent in $file:$line");
            echo json_encode($data, JSON_UNESCAPED_UNICODE);
            return;
        }
        http_response_code($code);
        header('Content-Type: application/json; charset=utf-8');
        header('Cache-Control: no-store, no-cache, must-revalidate');
        header('X-Content-Type-Options: nosniff');
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    public static function redirect(string $url, int $code = 302): void
    {
        if (headers_sent()) {
            echo '<meta http-equiv="refresh" content="0;url=' . htmlspecialchars($url) . '">';
            echo '<script>window.location.href=' . json_encode($url) . ';</script>';
            return;
        }
        http_response_code($code);
        header('Location: ' . $url);
        exit;
    }

    public static function abort(int $code, string $message = ''): void
    {
        $titles = [
            400 => 'Solicitud incorrecta',
            401 => 'No autorizado',
            403 => 'Prohibido',
            404 => 'No encontrado',
            405 => 'Método no permitido',
            429 => 'Demasiadas solicitudes',
            500 => 'Error interno del servidor',
            503 => 'Servicio no disponible',
        ];
        $title = $titles[$code] ?? 'Error';

        if (!headers_sent()) {
            http_response_code($code);
            header('Content-Type: text/html; charset=utf-8');
        }

        $safeMessage = htmlspecialchars($message, ENT_QUOTES, 'UTF-8');
        echo <<<HTML
        <!DOCTYPE html>
        <html lang="es">
        <head>
            <meta charset="utf-8">
            <meta name="viewport" content="width=device-width, initial-scale=1">
            <title>{$code} - {$title}</title>
            <style>
                body { font-family: system-ui, sans-serif; display: flex; align-items: center;
                       justify-content: center; min-height: 100vh; margin: 0;
                       background: #f8f9fa; color: #333; }
                .error-box { text-align: center; padding: 2rem; }
                .error-code { font-size: 6rem; font-weight: 800; color: #dee2e6; line-height: 1; }
                .error-title { font-size: 1.5rem; margin: 0.5rem 0; }
                .error-msg { color: #6c757d; margin-top: 0.5rem; }
                a { color: #0d6efd; text-decoration: none; }
            </style>
        </head>
        <body>
            <div class="error-box">
                <div class="error-code">{$code}</div>
                <h1 class="error-title">{$title}</h1>
                <p class="error-msg">{$safeMessage}</p>
                <a href="/">← Volver al inicio</a>
            </div>
        </body>
        </html>
        HTML;
        exit;
    }
}
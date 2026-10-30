<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Services\PublicThemeService;

/**
 * Entrega los recursos visuales **de la web pública**: la hoja de estilos
 * generada y el manifiesto de aplicación.
 *
 * Ambas rutas son anónimas a propósito: el CSS lo pide el navegador de
 * cualquier visitante. No exponen nada que no esté ya en el HTML público (son
 * colores y rutas de imagen), y no aceptan escritura.
 *
 * El CSS se sirve con `ETag` derivado del contenido: si el administrador no ha
 * cambiado nada, la segunda visita se resuelve con un `304` y ni siquiera se
 * envía el cuerpo. `Cache-Control` es corto (5 minutos) para que un cambio de
 * color se vea pronto sin castigar al servidor.
 *
 * @package App\Controllers
 * @since   2026-10-26
 */
final class PublicThemeController
{
    /**
     * `GET /public-theme.css` — hoja de estilos dinámica de la web pública.
     */
    public function css(Request $request): void
    {
        $css = PublicThemeService::css();

        $custom = PublicThemeService::customCss();
        if ($custom !== '') {
            $css .= "\n/* CSS personalizado — se carga sólo en la web pública */\n" . $custom . "\n";
        }

        $etag = '"' . PublicThemeService::version() . '"';

        if (!headers_sent()) {
            header('Content-Type: text/css; charset=utf-8');
            header('ETag: ' . $etag);
            header('Cache-Control: public, max-age=300, must-revalidate');
            header('X-Content-Type-Options: nosniff');
            header('Vary: Accept-Encoding');
        }

        // Comparación tolerante: algunos proxies y clientes devuelven el ETag
        // sin las comillas obligatorias. Se normalizan ambos lados.
        $ifNoneMatch = trim((string) ($_SERVER['HTTP_IF_NONE_MATCH'] ?? ''), " \t\"");
        if ($ifNoneMatch !== '' && ('"' . $ifNoneMatch . '"') === $etag) {
            http_response_code(304);
            return;
        }

        echo $css;
    }

    /**
     * `GET /site.webmanifest` — manifiesto con los iconos realmente disponibles.
     */
    public function manifest(Request $request): void
    {
        $payload = json_encode(
            PublicThemeService::manifest(),
            JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT
        );

        if (!headers_sent()) {
            header('Content-Type: application/manifest+json; charset=utf-8');
            header('Cache-Control: public, max-age=300');
            header('X-Content-Type-Options: nosniff');
        }

        echo $payload === false ? '{}' : $payload;
    }
}

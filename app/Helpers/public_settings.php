<?php
/**
 * Helpers de la identidad visual de la web pública.
 *
 * Envoltorios finos sobre `App\Services\PublicThemeService` para que las vistas
 * públicas no tengan que escribir el nombre completo de la clase, igual que
 * `t()` envuelve al servicio de traducciones o `can()` al de permisos.
 *
 * **Importante:** estas funciones son para el frontend público. El panel
 * administrativo no las usa y no debe usarlas: su identidad visual es fija.
 *
 * @package App\Helpers
 * @since   2026-10-26
 */

declare(strict_types=1);

use App\Services\PublicThemeService;

if (!function_exists('getPublicSettings')) {
    /**
     * Configuración visual completa de la web pública.
     *
     * Una sola consulta por petición (la cachea el servicio), así que puede
     * llamarse desde varios componentes sin coste añadido ni N+1.
     *
     * @return array<string, string>
     */
    function getPublicSettings(): array
    {
        return PublicThemeService::all();
    }
}

if (!function_exists('public_setting')) {
    /**
     * Valor suelto de la configuración pública.
     */
    function public_setting(string $key, ?string $default = null): string
    {
        return PublicThemeService::get($key, $default);
    }
}

if (!function_exists('public_logo')) {
    /**
     * URL del primer logotipo disponible de la lista dada.
     *
     * Devuelve `null` si ninguno existe, para que la vista pueda caer a la
     * marca textual en vez de dejar un `<img>` roto.
     *
     * @param string ...$slots Slots en orden de preferencia.
     */
    function public_logo(string ...$slots): ?string
    {
        return PublicThemeService::firstLogo(...$slots);
    }
}

if (!function_exists('public_logo_alt')) {
    /**
     * Texto alternativo de los logotipos públicos.
     */
    function public_logo_alt(): string
    {
        return PublicThemeService::logoAlt();
    }
}

if (!function_exists('public_favicon_tags')) {
    /**
     * Etiquetas `<link>` del `<head>` público, sólo para archivos existentes.
     */
    function public_favicon_tags(): string
    {
        return PublicThemeService::faviconTags();
    }
}

if (!function_exists('public_theme_css_url')) {
    /**
     * URL de la hoja de estilos dinámica, con la huella del contenido.
     *
     * El `?v=` cambia cuando cambia la configuración, así que el navegador pide
     * la versión nueva justo después de guardar, y mientras nada cambie sigue
     * habiendo `304` por `ETag`.
     */
    function public_theme_css_url(): string
    {
        return url('public-theme.css') . '?v=' . PublicThemeService::version();
    }
}

if (!function_exists('public_is_dark')) {
    /**
     * ¿El tema público configurado es oscuro? Decide `data-bs-theme`.
     */
    function public_is_dark(): bool
    {
        return PublicThemeService::isDark();
    }
}

<?php

declare(strict_types=1);

namespace App\Services;

/**
 * Identidad y metadatos del sitio que **sí** vienen de Configuración.
 *
 * El módulo de configuración guarda `general.site_name`, `site_description`,
 * `site_locale`, `site_timezone` y los valores SEO por defecto… y hasta ahora
 * el sitio público los ignoraba: `PublicController` pintaba la marca desde
 * `config/config.php` y las páginas no tenían meta descripción de reserva. Es
 * decir, el administrador cambiaba el nombre y no pasaba nada.
 *
 * Esta clase es el único punto que decide «de dónde sale cada dato»:
 *
 *   valor guardado en `settings`  →  valor de `config/config.php`  →  literal
 *
 * Se cachea por petición, así que se puede llamar desde el layout, el
 * controlador y los helpers sin coste. Si la base de datos falla, se degrada a
 * la configuración del archivo: el sitio nunca se queda sin nombre.
 *
 * @package App\Services
 * @since   2026-10-26
 */
final class SiteSettings
{
    /** @var array<string, array<string, mixed>>|null Caché de petición. */
    private static ?array $all = null;

    /**
     * Todas las claves de `settings` agrupadas, leídas en una sola consulta.
     *
     * @return array<string, array<string, mixed>>
     */
    private static function all(): array
    {
        if (self::$all !== null) {
            return self::$all;
        }

        try {
            self::$all = SettingService::all();
        } catch (\Throwable $e) {
            error_log('[SiteSettings] No se pudo leer la configuración: ' . $e->getMessage());
            self::$all = [];
        }

        return self::$all;
    }

    /**
     * Invalida la caché (necesario tras guardar en la misma petición).
     */
    public static function flush(): void
    {
        self::$all = null;
    }

    /**
     * Valor de configuración con reserva en `config/config.php`.
     */
    private static function value(string $group, string $key, string $fallback = ''): string
    {
        $value = self::all()[$group][$key] ?? null;

        if (is_string($value)) {
            $value = trim($value);
        } elseif (is_scalar($value)) {
            $value = (string) $value;
        } else {
            $value = '';
        }

        return $value !== '' ? $value : $fallback;
    }

    /**
     * Nombre del sitio: `general.site_name`, o `app_name` del archivo.
     *
     * Es lo que se ve en la cabecera del sitio público, en el pie, en el
     * `site.webmanifest` y en el texto alternativo de los logotipos.
     */
    public static function name(): string
    {
        $fallback = (string) ($GLOBALS['cfg']['app_name'] ?? 'PePiuoX Editor');
        return self::value('general', 'site_name', $fallback);
    }

    /**
     * Descripción corta del sitio (`general.site_description`).
     */
    public static function description(): string
    {
        return self::value('general', 'site_description', '');
    }

    /**
     * Código de idioma para `<html lang>` (`general.site_locale`).
     *
     * Se guarda como código corto («es», «en»); si alguien guarda «es_ES» se
     * normaliza al idioma base, que es lo que espera el atributo `lang`.
     */
    public static function language(): string
    {
        $fallback = (string) ($GLOBALS['cfg']['locale'] ?? 'es');
        $locale   = self::value('general', 'site_locale', $fallback);
        $base     = strtolower((string) preg_split('/[_-]/', $locale)[0]);

        return preg_match('/^[a-z]{2,3}$/', $base) === 1 ? $base : 'es';
    }

    /**
     * Zona horaria del sitio (`general.site_timezone`).
     */
    public static function timezone(): string
    {
        $fallback = (string) ($GLOBALS['cfg']['timezone'] ?? 'UTC');
        $timezone = self::value('general', 'site_timezone', $fallback);

        return in_array($timezone, timezone_identifiers_list(), true) ? $timezone : $fallback;
    }

    /**
     * Título por defecto (`seo.default_meta_title`), con reserva en el nombre.
     */
    public static function defaultTitle(): string
    {
        return self::value('seo', 'default_meta_title', self::name());
    }

    /**
     * Descripción por defecto (`seo.default_meta_description`), con reserva en
     * la descripción corta del sitio.
     */
    public static function defaultDescription(): string
    {
        return self::value('seo', 'default_meta_description', self::description());
    }

    /**
     * Título de documento para una página.
     *
     * Si la página trae título, se usa y se le añade el nombre del sitio
     * detrás (convención de pestaña). Si no, se cae al título por defecto.
     */
    public static function titleFor(?string $pageTitle): string
    {
        $pageTitle = trim((string) $pageTitle);

        if ($pageTitle === '' || $pageTitle === 'Sin título') {
            return self::defaultTitle();
        }

        // Evita «Nombre · Nombre» cuando la página se llama igual que el sitio.
        if (strcasecmp($pageTitle, self::name()) === 0) {
            return $pageTitle;
        }

        return $pageTitle . ' · ' . self::name();
    }

    /**
     * Descripción para el `<meta name="description">` de una página.
     */
    public static function descriptionFor(?string $pageDescription): string
    {
        $pageDescription = trim((string) $pageDescription);

        if ($pageDescription !== '') {
            return $pageDescription;
        }

        return self::defaultDescription();
    }
}

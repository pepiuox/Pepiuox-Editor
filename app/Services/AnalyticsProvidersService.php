<?php

namespace App\Services;

/**
 * Medición externa del sitio público: Google, Yandex, Bing y compañía.
 *
 * ── No confundir con `AnalyticsService` ─────────────────────────────
 * `AnalyticsService` es la analítica **propia** del panel: registra eventos en
 * `analytics_events` y resume visitas de `visits`, todo en la base del proyecto.
 * Esta clase, en cambio, solo compone las etiquetas de los proveedores
 * externos que el dueño configure. Son dos cosas distintas y no se mezclan.
 *
 * ── Por qué identificadores y no código ─────────────────────────────
 * Cada proveedor se configura con un identificador, no pegando el fragmento que
 * él distribuye. Así el valor se puede validar y decidir si se emite: si
 * alguien escribe comillas o una etiqueta `<script>`, no encaja con el patrón y
 * **no se emite nada**. Pegar código en un campo de texto sería una puerta
 * abierta a inyectarlo en todas las páginas del sitio.
 *
 * ── Quién lo usa ────────────────────────────────────────────────────
 *  - `views/public/layout.php` → `headTags()`, dentro del `<head>`.
 *  - `views/seo/global.php`    → `FIELDS`, para pintar los campos del panel.
 *  - `SeoController`           → `filtrarEntrada()`, al guardar.
 *
 * @package PePiuoX\Editor\Services
 * @since   2026-11-03
 */
final class AnalyticsProvidersService
{
    /** Grupo de ajustes donde se guardan. Comparte grupo con el resto del SEO. */
    public const GROUP = 'seo';

    /**
     * Nombre de la cookie donde se guarda la decisión del visitante.
     *
     * Valores: `todas` (acepta la medición) o `esenciales` (solo lo necesario).
     */
    public const CONSENT_COOKIE = 'pep_cookie_consent';

    /**
     * Proveedores que instalan cookies en el navegador.
     *
     * Plausible y Umami no las usan, así que quedan fuera de esta lista y se
     * emiten siempre. Los de aquí **no se cargan** hasta que el visitante
     * acepta: sin consentimiento el `<script>` sale como `type="text/plain"` y
     * no se ejecuta.
     *
     * @var list<string>
     */
    private const REQUIEREN_CONSENTIMIENTO = [
        'ga_tracking',
        'analytics_gtm',
        'analytics_yandex',
        'analytics_clarity',
    ];

    /**
     * Proveedores soportados.
     *
     * `patron` se aplica al valor guardado. Si no casa, el proveedor no se emite:
     * es la frontera de seguridad de esta clase.
     *
     * @var array<string, array{label: string, placeholder: string, ayuda: string, patron: string}>
     */
    public const FIELDS = [
        'ga_tracking' => [
            'label'       => 'Google Analytics 4',
            'placeholder' => 'G-XXXXXXXXXX',
            'ayuda'       => 'Identificador de medición de GA4. Solo el ID, no el código.',
            'patron'      => '/^G-[A-Z0-9]{4,20}$/i',
        ],
        'analytics_gtm' => [
            'label'       => 'Google Tag Manager',
            'placeholder' => 'GTM-XXXXXXX',
            'ayuda'       => 'Contenedor de GTM. Si lo usas, no repitas GA4 aquí: decláralo dentro de GTM.',
            'patron'      => '/^GTM-[A-Z0-9]{4,12}$/i',
        ],
        'analytics_yandex' => [
            'label'       => 'Yandex Metrica',
            'placeholder' => '12345678',
            'ayuda'       => 'Número de contador de Yandex Metrica (solo dígitos).',
            'patron'      => '/^\d{5,12}$/',
        ],
        'analytics_clarity' => [
            'label'       => 'Microsoft Clarity',
            'placeholder' => 'abcdefghij',
            'ayuda'       => 'Identificador del proyecto de Clarity (mapas de calor y grabaciones).',
            'patron'      => '/^[a-z0-9]{6,20}$/i',
        ],
        'analytics_plausible' => [
            'label'       => 'Plausible',
            'placeholder' => 'tu-dominio.com',
            'ayuda'       => 'Dominio tal y como está dado de alta en Plausible. Sin cookies.',
            'patron'      => '/^[a-z0-9]([a-z0-9.-]{1,120})$/i',
        ],
        'analytics_umami_id' => [
            'label'       => 'Umami · identificador',
            'placeholder' => '00000000-0000-0000-0000-000000000000',
            'ayuda'       => 'Identificador del sitio en Umami. Hace falta también el servidor.',
            'patron'      => '/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i',
        ],
        'analytics_umami_host' => [
            'label'       => 'Umami · servidor',
            'placeholder' => 'https://analitica.tu-dominio.com',
            'ayuda'       => 'URL de tu instalación de Umami, sin barra final. Debe ser HTTPS.',
            'patron'      => '#^https://[a-z0-9.-]{3,120}(/[a-z0-9._-]+)*$#i',
        ],
        'gsc_verification' => [
            'label'       => 'Google Search Console',
            'placeholder' => 'cadena de verificación',
            'ayuda'       => 'Meta de verificación de propiedad de Search Console.',
            'patron'      => '/^[A-Za-z0-9_-]{10,100}$/',
        ],
        'analytics_bing' => [
            'label'       => 'Bing Webmaster Tools',
            'placeholder' => 'cadena de verificación',
            'ayuda'       => 'Meta de verificación de propiedad de Bing (msvalidate.01).',
            'patron'      => '/^[A-Za-z0-9]{8,100}$/',
        ],
    ];

    /**
     * Devuelve los valores válidos que hay configurados.
     *
     * Descarta en silencio cualquier valor que no encaje con su patrón: un
     * identificador mal escrito no debe romper la página ni, mucho menos,
     * colarse en el HTML.
     *
     * @return array<string, string> Clave del proveedor => valor validado.
     */
    public static function vigentes(): array
    {
        $salida = [];

        foreach (self::FIELDS as $clave => $definicion) {
            $valor = trim((string) SettingService::get(self::GROUP, $clave, ''));

            if ($valor !== '' && preg_match($definicion['patron'], $valor) === 1) {
                $salida[$clave] = $valor;
            }
        }

        return $salida;
    }

    /**
     * Compone las etiquetas de medición y verificación para el `<head>`.
     *
     * Cada valor se escapa otra vez al imprimirlo, aunque ya haya pasado el
     * patrón: la validación es la primera barrera, no la única.
     *
     * @return string HTML listo para insertar. Cadena vacía si no hay nada.
     */
    public static function headTags(): string
    {
        $v = self::vigentes();
        $siempre    = '';  // metadatos y proveedores que no usan cookies
        $conCookies = '';  // requieren consentimiento del visitante

        // ── Verificación de propiedad (metadatos) ───────────────────
        if (isset($v['gsc_verification'])) {
            $siempre .= '    <meta name="google-site-verification" content="'
                . htmlspecialchars($v['gsc_verification'], ENT_QUOTES, 'UTF-8') . '">' . "\n";
        }
        if (isset($v['analytics_bing'])) {
            $siempre .= '    <meta name="msvalidate.01" content="'
                . htmlspecialchars($v['analytics_bing'], ENT_QUOTES, 'UTF-8') . '">' . "\n";
        }

        // ── Google Analytics 4 ──────────────────────────────────────
        if (isset($v['ga_tracking'])) {
            $id = htmlspecialchars($v['ga_tracking'], ENT_QUOTES, 'UTF-8');
            $conCookies .= <<<HTML
    <script async src="https://www.googletagmanager.com/gtag/js?id={$id}"></script>
    <script>
      window.dataLayer = window.dataLayer || [];
      function gtag(){dataLayer.push(arguments);}
      gtag('js', new Date());
      gtag('config', '{$id}');
    </script>

HTML;
        }

        // ── Google Tag Manager ──────────────────────────────────────
        if (isset($v['analytics_gtm'])) {
            $id = htmlspecialchars($v['analytics_gtm'], ENT_QUOTES, 'UTF-8');
            $conCookies .= <<<HTML
    <script>(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':
    new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],
    j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src=
    'https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);
    })(window,document,'script','dataLayer','{$id}');</script>

HTML;
        }

        // ── Yandex Metrica ──────────────────────────────────────────
        if (isset($v['analytics_yandex'])) {
            $id = htmlspecialchars($v['analytics_yandex'], ENT_QUOTES, 'UTF-8');
            $conCookies .= <<<HTML
    <script>
      (function(m,e,t,r,i,k,a){m[i]=m[i]||function(){(m[i].a=m[i].a||[]).push(arguments)};
      m[i].l=1*new Date();
      for (var j = 0; j < document.scripts.length; j++) {if (document.scripts[j].src === r) { return; }}
      k=e.createElement(t),a=e.getElementsByTagName(t)[0],k.async=1,k.src=r,a.parentNode.insertBefore(k,a)})
      (window, document, "script", "https://mc.yandex.ru/metrika/tag.js", "ym");
      ym({$id}, "init", {clickmap:true, trackLinks:true, accurateTrackBounce:true});
    </script>

HTML;
        }

        // ── Microsoft Clarity ───────────────────────────────────────
        if (isset($v['analytics_clarity'])) {
            $id = htmlspecialchars($v['analytics_clarity'], ENT_QUOTES, 'UTF-8');
            $conCookies .= <<<HTML
    <script>
      (function(c,l,a,r,i,t,y){
        c[a]=c[a]||function(){(c[a].q=c[a].q||[]).push(arguments)};
        t=l.createElement(r);t.async=1;t.src="https://www.clarity.ms/tag/"+i;
        y=l.getElementsByTagName(r)[0];y.parentNode.insertBefore(t,y);
      })(window, document, "clarity", "script", "{$id}");
    </script>

HTML;
        }

        // ── Plausible (sin cookies) ─────────────────────────────────
        if (isset($v['analytics_plausible'])) {
            $dominio = htmlspecialchars($v['analytics_plausible'], ENT_QUOTES, 'UTF-8');
            $siempre .= '    <script defer data-domain="' . $dominio
                . '" src="https://plausible.io/js/script.js"></script>' . "\n";
        }

        // ── Umami (sin cookies) ────────────────────────────────────
        if (isset($v['analytics_umami_id'], $v['analytics_umami_host'])) {
            $id = htmlspecialchars($v['analytics_umami_id'], ENT_QUOTES, 'UTF-8');
            $servidor = htmlspecialchars(rtrim($v['analytics_umami_host'], '/'), ENT_QUOTES, 'UTF-8');
            $siempre .= '    <script defer data-website-id="' . $id . '" src="'
                . $servidor . '/script.js"></script>' . "\n";
        }

        // Sin consentimiento, los scripts que instalan cookies salen como
        // `type="text/plain"`: el navegador los guarda pero **no los ejecuta**.
        // El aviso los reactiva si el visitante acepta.
        if ($conCookies !== '' && !self::consentimientoDado()) {
            $conCookies = (string) preg_replace(
                '/<script(?=[\s>])/',
                '<script type="text/plain" data-cookie-consent="analytics"',
                $conCookies
            );
        }

        return $siempre . $conCookies;
    }

    /**
     * ¿Hay algún proveedor configurado que instale cookies?
     *
     * Plausible y Umami no las usan, así que no cuentan aquí.
     *
     * @return bool True si alguno de los que requieren consentimiento está activo.
     */
    public static function requiereConsentimiento(): bool
    {
        $v = self::vigentes();

        foreach (self::REQUIEREN_CONSENTIMIENTO as $clave) {
            if (isset($v[$clave])) {
                return true;
            }
        }

        return false;
    }

    /**
     * ¿El visitante ya aceptó la medición?
     *
     * @return bool True si la cookie de consentimiento vale «todas».
     */
    public static function consentimientoDado(): bool
    {
        return ($_COOKIE[self::CONSENT_COOKIE] ?? '') === 'todas';
    }

    /**
     * ¿Hay que enseñar el aviso en esta visita?
     *
     * @return bool True si hay proveedores con cookies y aún no hay decisión.
     */
    public static function necesitaAviso(): bool
    {
        return self::requiereConsentimiento() && !self::consentimientoDado();
    }

    /**
     * Limpia lo que llega del formulario antes de guardarlo.
     *
     * Un identificador que no encaja con su patrón se guarda vacío: es preferible
     * perder esa configuración a publicar una página con código inyectado. El
     * panel avisa de qué campos se descartaron.
     *
     * @param array<string, mixed> $entrada Datos del formulario.
     * @return array{valores: array<string, string>, descartados: list<string>}
     */
    public static function filtrarEntrada(array $entrada): array
    {
        $valores = [];
        $descartados = [];

        foreach (self::FIELDS as $clave => $definicion) {
            if (!array_key_exists($clave, $entrada)) {
                continue;
            }

            $valor = trim((string) $entrada[$clave]);

            if ($valor === '') {
                $valores[$clave] = '';
                continue;
            }

            if (preg_match($definicion['patron'], $valor) === 1) {
                $valores[$clave] = $valor;
                continue;
            }

            $valores[$clave] = '';
            $descartados[] = $definicion['label'];
        }

        return ['valores' => $valores, 'descartados' => $descartados];
    }
}

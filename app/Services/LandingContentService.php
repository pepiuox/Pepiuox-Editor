<?php

declare(strict_types=1);

namespace App\Services;

/**
 * Contenido editorial de la portada comercial.
 *
 * ── Qué resuelve ────────────────────────────────────────────────────
 * La portada pública (ver `views/public/landing.php`) necesita textos,
 * tarjetas de funciones, especificaciones técnicas, beneficios y una galería
 * de capturas. Hasta ahora ese contenido no existía en ninguna parte: el
 * panel tenía 41 claves del grupo `public`, pero todas eran de identidad
 * visual (colores, logotipos, favicon, CSS), ninguna de texto editorial.
 *
 * Este servicio es el único punto que decide de dónde sale cada dato:
 *
 *   valor guardado en `settings` (grupo `landing`)  →  valor por defecto en código
 *
 * El orden importa: la base de datos manda para que el dueño pueda editar sin
 * tocar código, y los valores por defecto del código garantizan que la portada
 * se pueda ver y previsualizar **antes** de aplicar la migración que siembra
 * las claves. Sin ellos, una instalación recién creada mostraría una portada
 * vacía.
 *
 * Se cachea por petición con una sola consulta, igual que `SiteSettings` y
 * `PublicThemeService`. Si la base de datos falla, se degrada a los valores por
 * defecto: la portada nunca se queda en blanco.
 *
 * ── Por qué los valores por defecto están también en SQL ────────────
 * `db/migrations/2026_10_31_landing_content.{up,down}.sql` siembra las mismas
 * claves en la base de datos para que sean editables desde el panel. La
 * duplicación es deliberada y es el mismo patrón que ya usa
 * `PublicThemeService` con sus temas: el código define el estado inicial, la
 * base de datos guarda lo que el dueño cambia. Los dos deben coincidir; si se
 * cambia uno, se cambia el otro.
 *
 * @package App\Services
 * @since   2026-10-31
 */
final class LandingContentService
{
    /** Permiso del panel que gobierna la edición de la portada. */
    public const PERMISSION = 'settings.manage';

    /** Límites de seguridad: una lista no puede crecer sin tope. */
    public const MAX_FEATURES = 12;
    public const MAX_SPECS    = 16;
    public const MAX_BENEFITS = 8;
    public const MAX_GALLERY  = 12;

    /** Largo máximo de un texto corto (título, etiqueta, eslogan). */
    private const MAX_SHORT = 180;

    /** Largo máximo de un párrafo. */
    private const MAX_LONG = 1200;

    /** @var array<string, mixed>|null Caché de petición. */
    private static ?array $resolved = null;

    /**
     * Contenido por defecto de la portada.
     *
     * Debe coincidir con `db/migrations/2026_10_31_landing_content.up.sql`.
     * Cada afirmación corresponde a algo que la plataforma hace de verdad.
     *
     * @return array<string, mixed>
     */
    public static function defaults(): array
    {
        return [
            'enabled'          => false,
            'hero_kicker'      => 'Editor y CMS web en PHP 8 y MariaDB',
            'hero_title'       => 'Publica, versiona y controla tu web desde un solo panel',
            'hero_subtitle'    => 'Contenido, diseño y seguridad en la misma herramienta, sin dependencias de terceros.',
            'hero_description' => 'PePiuoX Editor reúne constructor visual, control de versiones, plantillas, menús y personalización visual completa en una plataforma que se instala en tu propio servidor. Sin licencias por usuario y sin ataduras a un servicio externo.',
            'hero_image'       => '',
            'hero_cta_primary'   => ['label' => 'Entrar al sistema', 'href' => '/login'],
            'hero_cta_secondary' => ['label' => 'Ver funciones', 'href' => '#caracteristicas'],
            'sections' => [
                'presentation' => [
                    'title'       => 'Qué es PePiuoX Editor',
                    'problem'     => 'Mantener una web suele repartirse entre un gestor de contenidos, un maquetador y una carpeta de copias de seguridad. Cada pieza guarda su verdad y ninguna sabe de las otras.',
                    'audience'    => 'Equipos que publican contenido con frecuencia y no quieren depender de un desarrollador para cada cambio, pero tampoco aceptan perder control sobre el código, los datos ni el diseño.',
                    'advantages'  => 'Una sola base de datos, un solo panel y un solo historial. Lo que se edita se versiona, lo que se publica se puede revertir y lo que se rompe se restaura con una copia fechada.',
                ],
                'features' => [
                    'title' => 'Funciones',
                    'intro' => 'Los módulos que ya están en la plataforma, no una hoja de ruta.',
                ],
                'specs' => [
                    'title' => 'Especificaciones técnicas',
                    'intro' => 'Lo que hay que saber antes de instalarlo en un servidor.',
                ],
                'benefits' => [
                    'title' => 'Qué cambia con la plataforma',
                    'intro' => 'El efecto práctico de tener todo en el mismo sitio.',
                ],
                'gallery' => [
                    'title' => 'El sistema por dentro',
                    'intro' => 'Pantallas reales del panel. Las imágenes se cargan desde Configuración → Portada.',
                ],
            ],
            'features' => [
                ['icon' => 'bi-vector-pen', 'title' => 'Constructor visual de bloques', 'description' => 'Arrastra bloques ya diseñados sobre Bootstrap 5 y publica sin escribir HTML. La biblioteca de bloques es propia y coherente con el resto del panel.'],
                ['icon' => 'bi-clock-history', 'title' => 'Versionado del contenido', 'description' => 'Cada guardado deja una versión recuperable. Puedes comparar, restaurar o descartar sin miedo a perder el trabajo posterior.'],
                ['icon' => 'bi-layout-text-window-reverse', 'title' => 'Plantillas reutilizables', 'description' => 'Define una estructura una vez y utilízala en tantas páginas como necesites, con versiones propias de cada plantilla.'],
                ['icon' => 'bi-diagram-3', 'title' => 'Menús jerárquicos', 'description' => 'Menús multinivel con orden arrastrable, asignación por página y caché que se regenera sola al cambiar la estructura.'],
                ['icon' => 'bi-palette', 'title' => 'Identidad visual sin tocar código', 'description' => 'Cinco temas, dieciséis colores, diez huecos de logotipo según el contexto, favicon y CSS propio, con el contraste AA calculado en cada superficie.'],
                ['icon' => 'bi-shield-lock', 'title' => 'Seguridad en la base', 'description' => 'Contraseñas con bcrypt de coste 12, protección CSRF en los formularios, límite de intentos de acceso y verificación en dos pasos con códigos de recuperación.'],
                ['icon' => 'bi-graph-up-arrow', 'title' => 'Analítica y auditoría', 'description' => 'Visitas, estadísticas diarias y un registro de quién hizo qué, cuándo y desde dónde. Todo consultable desde el propio panel.'],
                ['icon' => 'bi-plug', 'title' => 'API versionada', 'description' => 'Endpoints en /api/v1 con aplicaciones, claves y tokens para que otros sistemas lean tu contenido sin entrar al panel.'],
            ],
            'specs' => [
                ['label' => 'Lenguaje',            'value' => 'PHP 8.3 o superior, con tipado estricto'],
                ['label' => 'Base de datos',       'value' => 'MariaDB 11 o superior, mediante PDO con consultas preparadas'],
                ['label' => 'Servidor web',        'value' => 'Apache 2.4 con mod_rewrite, o nginx con la reescritura equivalente'],
                ['label' => 'Dependencias',        'value' => 'Ninguna en tiempo de ejecución: autocarga PSR-4 propia, sin Composer'],
                ['label' => 'Interfaz pública',    'value' => 'Bootstrap 5.3 e iconografía Bootstrap Icons'],
                ['label' => 'Constructor',         'value' => 'GrapesJS con biblioteca de bloques propia'],
                ['label' => 'Seguridad',           'value' => 'Bcrypt coste 12, CSRF, límite de peticiones, 2FA TOTP, sesiones HttpOnly y SameSite'],
                ['label' => 'Interoperabilidad',   'value' => 'API REST versionada en /api/v1 con autenticación por token'],
            ],
            'benefits' => [
                ['icon' => 'bi-lightning-charge', 'title' => 'Automatización', 'description' => 'La caché, los menús, el sitemap y las copias programadas se regeneran sin intervención manual.'],
                ['icon' => 'bi-bezier2', 'title' => 'Procesos más cortos', 'description' => 'Editar, previsualizar y publicar ocurre en la misma pantalla, sin exportar ni subir archivos por FTP.'],
                ['icon' => 'bi-hdd-stack', 'title' => 'Administración centralizada', 'description' => 'Páginas, plantillas, menús, archivos, usuarios y ajustes comparten panel, permisos e historial.'],
                ['icon' => 'bi-clipboard-data', 'title' => 'Control de la información', 'description' => 'Cada acción queda auditada y cada contenido versionado: se puede saber qué cambió y volver atrás.'],
            ],
            'gallery'     => [],
            'footer_note' => 'PePiuoX Editor — editor y CMS web en PHP 8 y MariaDB. Instalable en servidor propio.',
        ];
    }

    /**
     * Contenido resuelto de la portada.
     *
     * @return array<string, mixed>
     */
    public static function all(): array
    {
        if (self::$resolved !== null) {
            return self::$resolved;
        }

        $defaults = self::defaults();
        $stored   = self::stored();

        $out             = $defaults;
        $out['enabled']  = self::toBool($stored['enabled'] ?? $defaults['enabled']);

        foreach (['hero_kicker', 'hero_title', 'hero_subtitle', 'hero_description', 'hero_image', 'footer_note'] as $key) {
            if (isset($stored[$key]) && is_string($stored[$key]) && trim($stored[$key]) !== '') {
                $out[$key] = trim($stored[$key]);
            }
        }

        foreach (['hero_cta_primary', 'hero_cta_secondary'] as $key) {
            $cta = self::sanitizeCta($stored[$key] ?? null);
            if ($cta !== null) {
                $out[$key] = $cta;
            }
        }

        $sections = self::sanitizeSections($stored['sections'] ?? null);
        if ($sections !== []) {
            $out['sections'] = array_replace_recursive($defaults['sections'], $sections);
        }

        $features = self::sanitizeFeatures($stored['features'] ?? null);
        if ($features !== []) {
            $out['features'] = $features;
        }

        $specs = self::sanitizeSpecs($stored['specs'] ?? null);
        if ($specs !== []) {
            $out['specs'] = $specs;
        }

        $benefits = self::sanitizeBenefits($stored['benefits'] ?? null);
        if ($benefits !== []) {
            $out['benefits'] = $benefits;
        }

        $gallery = self::sanitizeGallery($stored['gallery'] ?? null);
        if ($gallery !== []) {
            $out['gallery'] = $gallery;
        }

        self::$resolved = $out;

        return $out;
    }

    /**
     * ¿Está activa la portada como página de inicio?
     *
     * Desactivada por defecto (ADR-006): la migración no cambia la home de un
     * sitio publicado; eso lo decide el dueño.
     */
    public static function enabled(): bool
    {
        return self::all()['enabled'] === true;
    }

    /**
     * Invalida la caché de la petición (tras guardar).
     */
    public static function flush(): void
    {
        self::$resolved = null;
    }

    /**
     * Valida y guarda el contenido editado desde el panel.
     *
     * Todo lo que entra por aquí viene de un formulario: se valida campo a campo
     * con las mismas reglas que usa la lectura, y lo que no pasa se descarta en
     * lugar de guardarse. Un dato inválido no borra el que ya había: se informa y
     * se deja el anterior.
     *
     * @param array<string, mixed> $input Datos del formulario.
     * @return array{ok:bool, errores:list<string>, avisos:list<string>, guardadas:int}
     */
    public static function save(array $input): array
    {
        $errores = [];
        $avisos  = [];
        $cuenta  = 0;

        $textos = [
            'hero_kicker'      => self::MAX_SHORT,
            'hero_title'       => self::MAX_SHORT,
            'hero_subtitle'    => 400,
            'hero_description' => self::MAX_LONG,
            'footer_note'      => 400,
        ];

        foreach ($textos as $clave => $max) {
            $valor = isset($input[$clave]) && is_string($input[$clave]) ? trim($input[$clave]) : '';

            if (mb_strlen($valor) > $max) {
                $errores[] = "«{$clave}» supera los {$max} caracteres: se recortó.";
                $valor = mb_substr($valor, 0, $max);
            }

            SettingService::set('landing', $clave, $valor, 'string');
            $cuenta++;
        }

        // Imagen del hero: solo ruta interna o URL http(s).
        $imagen = isset($input['hero_image']) && is_string($input['hero_image']) ? trim($input['hero_image']) : '';
        if ($imagen !== '' && !self::isSafeMediaPath($imagen)) {
            $errores[] = 'La imagen del hero debe ser una ruta del sitio (/uploads/...) o una URL http(s). No se guardó.';
            $imagen = '';
        } elseif (mb_strlen($imagen) > 300) {
            $errores[] = 'La ruta de la imagen del hero es demasiado larga. No se guardó.';
            $imagen = '';
        }
        SettingService::set('landing', 'hero_image', $imagen, 'string');
        $cuenta++;

        // Interruptor de la portada. Solo cambia si el formulario lo envía: así
        // guardar el contenido no activa la portada por accidente.
        if (array_key_exists('enabled', $input)) {
            $activa = !empty($input['enabled']) ? '1' : '0';
            SettingService::set('landing', 'enabled', $activa, 'bool');
            $cuenta++;
        }

        // Botones del hero.
        foreach (['hero_cta_primary', 'hero_cta_secondary'] as $clave) {
            $cta = self::sanitizeCta([
                'label' => (string) ($input[$clave . '_label'] ?? ''),
                'href'  => (string) ($input[$clave . '_href'] ?? ''),
            ]);

            if ($cta === null) {
                $errores[] = 'El botón «' . $clave . '» necesita etiqueta (máx. 60) y un destino válido: ruta que empiece por /, ancla #, o http(s). Se conserva el anterior.';
                continue;
            }

            SettingService::set('landing', $clave, $cta, 'json');
            $cuenta++;
        }

        // Textos de sección: solo se guardan los que llegan con contenido; el
        // resto conserva lo que hubiera.
        $secciones = [];
        foreach (['presentation', 'features', 'specs', 'benefits', 'gallery'] as $seccion) {
            foreach (['title', 'intro', 'problem', 'audience', 'advantages'] as $campo) {
                $valor = isset($input['sections'][$seccion][$campo]) && is_string($input['sections'][$seccion][$campo])
                    ? trim($input['sections'][$seccion][$campo])
                    : '';
                if ($valor !== '') {
                    $secciones[$seccion][$campo] = $valor;
                }
            }
        }
        if ($secciones !== []) {
            SettingService::set('landing', 'sections', $secciones, 'json');
            $cuenta++;
        }

        // Listas. Se guardan siempre, aunque queden vacías: vaciar la lista es
        // una acción legítima (la vista tiene su estado vacío).
        $listas = [
            'features' => self::sanitizeFeatures($input['features'] ?? null),
            'specs'    => self::sanitizeSpecs($input['specs'] ?? null),
            'benefits' => self::sanitizeBenefits($input['benefits'] ?? null),
            'gallery'  => self::sanitizeGallery($input['gallery'] ?? null),
        ];

        $limites = [
            'features' => self::MAX_FEATURES,
            'specs'    => self::MAX_SPECS,
            'benefits' => self::MAX_BENEFITS,
            'gallery'  => self::MAX_GALLERY,
        ];

        foreach ($listas as $clave => $lista) {
            $enviadas = is_array($input[$clave] ?? null) ? count($input[$clave]) : 0;

            if ($enviadas > $lista && count($lista) === $limites[$clave]) {
                $avisos[] = "«{$clave}» admite un máximo de {$limites[$clave]} elementos: el resto se descartó.";
            }
            if ($enviadas > count($lista)) {
                $avisos[] = "En «{$clave}» se descartaron " . ($enviadas - count($lista)) . ' fila(s) con datos incompletos.';
            }

            SettingService::set('landing', $clave, $lista, 'json');
            $cuenta++;
        }

        self::flush();
        SettingService::clearCache();

        return [
            'ok'        => $errores === [],
            'errores'   => $errores,
            'avisos'    => array_values(array_unique($avisos)),
            'guardadas' => $cuenta,
        ];
    }

    /**
     * ¿Es una ruta servible por el sitio o una URL absoluta http(s)?
     */
    private static function isSafeMediaPath(string $valor): bool
    {
        $local   = str_starts_with($valor, '/') && !str_starts_with($valor, '//');
        $externa = (bool) preg_match('#^https?://[^\s]+$#i', $valor);

        return $local || $externa;
    }

    /**
     * Lee el grupo `landing` de una sola consulta, degradando si falla.
     *
     * @return array<string, mixed>
     */
    private static function stored(): array
    {
        try {
            $all = SettingService::all();
        } catch (\Throwable $e) {
            error_log('[LandingContent] No se pudo leer el contenido: ' . $e->getMessage());
            return [];
        }

        $group = $all['landing'] ?? [];

        return is_array($group) ? $group : [];
    }

    /** @param mixed $value Valor guardado. */
    private static function toBool(mixed $value): bool
    {
        if (is_bool($value)) {
            return $value;
        }

        return filter_var($value, FILTER_VALIDATE_BOOLEAN);
    }

    /**
     * Valida un botón de acción: etiqueta y destino.
     *
     * El destino se restringe a rutas internas, anclas o URL absoluta http(s).
     * `javascript:` y `data:` quedan fuera: el contenido es editable y la
     * portada no debe convertirse en un inyector de esquemas.
     *
     * @param mixed $value Valor guardado.
     * @return array{label:string, href:string}|null
     */
    private static function sanitizeCta(mixed $value): ?array
    {
        if (!is_array($value)) {
            return null;
        }

        $label = isset($value['label']) && is_string($value['label']) ? trim($value['label']) : '';
        $href  = isset($value['href']) && is_string($value['href']) ? trim($value['href']) : '';

        if ($label === '' || mb_strlen($label) > 60) {
            return null;
        }

        if ($href === '' || mb_strlen($href) > 200) {
            return null;
        }

        $esRutaInterna = str_starts_with($href, '/') && !str_starts_with($href, '//');
        $esAncla       = str_starts_with($href, '#');
        $esExterna     = (bool) preg_match('#^https?://[^\s]+$#i', $href);

        if (!$esRutaInterna && !$esAncla && !$esExterna) {
            return null;
        }

        return ['label' => $label, 'href' => $href];
    }

    /**
     * Valida el icono como una clase de Bootstrap Icons.
     */
    private static function sanitizeIcon(mixed $value): string
    {
        $icon = is_string($value) ? trim($value) : '';

        return preg_match('/^bi-[a-z0-9-]{1,48}$/', $icon) === 1 ? $icon : 'bi-check2-circle';
    }

    /**
     * @param mixed $value Valor guardado.
     * @return array<string, array<string, string>>
     */
    private static function sanitizeSections(mixed $value): array
    {
        if (!is_array($value)) {
            return [];
        }

        $permitidas = ['title' => 120, 'problem' => self::MAX_LONG, 'audience' => self::MAX_LONG,
                       'advantages' => self::MAX_LONG, 'intro' => self::MAX_LONG];
        $out = [];

        foreach ($value as $sect => $campos) {
            if (!is_string($sect) || !is_array($campos)) {
                continue;
            }
            foreach ($campos as $clave => $texto) {
                if (!is_string($clave) || !isset($permitidas[$clave]) || !is_string($texto)) {
                    continue;
                }
                $texto = trim($texto);
                if ($texto === '' || mb_strlen($texto) > $permitidas[$clave]) {
                    continue;
                }
                $out[$sect][$clave] = $texto;
            }
        }

        return $out;
    }

    /**
     * @param mixed $value Valor guardado.
     * @return list<array{icon:string,title:string,description:string}>
     */
    private static function sanitizeFeatures(mixed $value): array
    {
        if (!is_array($value)) {
            return [];
        }

        $out = [];
        foreach (array_slice($value, 0, self::MAX_FEATURES) as $item) {
            if (!is_array($item)) {
                continue;
            }
            $title = isset($item['title']) && is_string($item['title']) ? trim($item['title']) : '';
            $desc  = isset($item['description']) && is_string($item['description']) ? trim($item['description']) : '';
            if ($title === '' || $desc === '' || mb_strlen($title) > self::MAX_SHORT || mb_strlen($desc) > 400) {
                continue;
            }
            $out[] = ['icon' => self::sanitizeIcon($item['icon'] ?? ''), 'title' => $title, 'description' => $desc];
        }

        return $out;
    }

    /**
     * @param mixed $value Valor guardado.
     * @return list<array{label:string,value:string}>
     */
    private static function sanitizeSpecs(mixed $value): array
    {
        if (!is_array($value)) {
            return [];
        }

        $out = [];
        foreach (array_slice($value, 0, self::MAX_SPECS) as $item) {
            if (!is_array($item)) {
                continue;
            }
            $label = isset($item['label']) && is_string($item['label']) ? trim($item['label']) : '';
            $val   = isset($item['value']) && is_string($item['value']) ? trim($item['value']) : '';
            if ($label === '' || $val === '' || mb_strlen($label) > 60 || mb_strlen($val) > self::MAX_SHORT) {
                continue;
            }
            $out[] = ['label' => $label, 'value' => $val];
        }

        return $out;
    }

    /**
     * @param mixed $value Valor guardado.
     * @return list<array{icon:string,title:string,description:string}>
     */
    private static function sanitizeBenefits(mixed $value): array
    {
        if (!is_array($value)) {
            return [];
        }

        $out = [];
        foreach (array_slice($value, 0, self::MAX_BENEFITS) as $item) {
            if (!is_array($item)) {
                continue;
            }
            $title = isset($item['title']) && is_string($item['title']) ? trim($item['title']) : '';
            $desc  = isset($item['description']) && is_string($item['description']) ? trim($item['description']) : '';
            if ($title === '' || $desc === '' || mb_strlen($title) > self::MAX_SHORT || mb_strlen($desc) > 400) {
                continue;
            }
            $out[] = ['icon' => self::sanitizeIcon($item['icon'] ?? ''), 'title' => $title, 'description' => $desc];
        }

        return $out;
    }

    /**
     * @param mixed $value Valor guardado.
     * @return list<array{image:string,alt:string,caption:string}>
     */
    private static function sanitizeGallery(mixed $value): array
    {
        if (!is_array($value)) {
            return [];
        }

        $out = [];
        foreach (array_slice($value, 0, self::MAX_GALLERY) as $item) {
            if (!is_array($item)) {
                continue;
            }
            $img = isset($item['image']) && is_string($item['image']) ? trim($item['image']) : '';
            $alt = isset($item['alt']) && is_string($item['alt']) ? trim($item['alt']) : '';
            $cap = isset($item['caption']) && is_string($item['caption']) ? trim($item['caption']) : '';
            if ($img === '' || mb_strlen($img) > 300 || mb_strlen($alt) > self::MAX_SHORT || mb_strlen($cap) > self::MAX_SHORT) {
                continue;
            }
            // Solo rutas servibles por el propio sitio o URL absoluta http(s).
            $local   = str_starts_with($img, '/') && !str_starts_with($img, '//');
            $externa = (bool) preg_match('#^https?://[^\s]+$#i', $img);
            if (!$local && !$externa) {
                continue;
            }
            $out[] = ['image' => $img, 'alt' => $alt, 'caption' => $cap];
        }

        return $out;
    }
}

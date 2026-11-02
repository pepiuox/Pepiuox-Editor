<?php
namespace App\Services;

use App\Core\Database;

class PageRenderer
{
    private int $startpage = 1;
    private int $is_published = 1;
    private string $status = 'published';
    private static array $menuCache = [];
    private static array $pageCache = [];

    /**
     * Obtiene la página de inicio con caché
     */
    public function getHomePage(): ?array
    {
        // Cache para evitar múltiples consultas
        $cacheKey = 'home_page';
        if (isset(self::$pageCache[$cacheKey])) {
            return self::$pageCache[$cacheKey];
        }

        $page = Database::fetch(
            "SELECT * FROM pages 
             WHERE startpage = ? AND is_published = ? AND status = ?
             LIMIT 1",
            [$this->startpage, $this->is_published, $this->status]
        );

        if ($page) {
            $page = $this->processPageData($page);
            self::$pageCache[$cacheKey] = $page;
        }

        return $page;
    }

    /**
     * Obtiene una página por slug
     */
    public function getPageBySlug(string $slug): ?array
    {
        // Limpiar el slug para la búsqueda
        $cleanSlug = $this->sanitizeSlug($slug);
        
        // Si el slug está vacío o es solo guiones, buscar la página de inicio
        if (empty($cleanSlug) || $cleanSlug === '-' || $cleanSlug === '---') {
            return $this->getHomePage();
        }

        // Cache para evitar múltiples consultas del mismo slug
        $cacheKey = 'page_' . $cleanSlug;
        if (isset(self::$pageCache[$cacheKey])) {
            return self::$pageCache[$cacheKey];
        }

        // MEJORADO: Buscar por slug con diferentes variaciones
        $page = Database::fetch(
            "SELECT * FROM pages 
             WHERE slug = ? AND is_published = ? AND status = ?
             OR slug LIKE ? AND is_published = ? AND status = ?
             LIMIT 1",
            [
                $cleanSlug, $this->is_published, $this->status,
                '%/' . $cleanSlug, $this->is_published, $this->status
            ]
        );

        // Si no se encuentra, intentar buscar por slug sin prefijo
        if (!$page) {
            $slugParts = explode('/', $cleanSlug);
            $lastSlug = end($slugParts);
            
            $page = Database::fetch(
                "SELECT * FROM pages 
                 WHERE slug LIKE ? AND is_published = ? AND status = ?
                 LIMIT 1",
                ['%/' . $lastSlug, $this->is_published, $this->status]
            );
        }

        if ($page) {
            $page = $this->processPageData($page);
            self::$pageCache[$cacheKey] = $page;
        }

        return $page;
    }

    /**
     * Procesa los datos de la página (sanitización y preparación)
     */
    private function processPageData(array $page): array
    {
        // Sanitizar datos básicos
        $page['title'] = htmlspecialchars($page['title'], ENT_QUOTES, 'UTF-8');
        $page['description'] = htmlspecialchars($page['description'] ?? '', ENT_QUOTES, 'UTF-8');
        $page['slug'] = $this->sanitizeSlug($page['slug']);
        
        // Procesar contenido si existe
        if (isset($page['content_html']) && !empty($page['content_html'])) {
            $page['content_html'] = $this->processContent($page['content_html']);
        }
        
        // Procesar CSS y JS
        if (isset($page['content_css']) && !empty($page['content_css'])) {
            $page['content_css'] = $this->optimizeCss($page['content_css']);
        }
        
        if (isset($page['content_js']) && !empty($page['content_js'])) {
            $page['content_js'] = $this->optimizeJs($page['content_js']);
        }
        
        return $page;
    }

    /**
     * Procesa el contenido HTML (sanitización y optimización)
     */
    private function processContent(string $content): string
    {
        // Eliminar scripts maliciosos
        $content = $this->sanitizeHtml($content);
        
        // Procesar enlaces internos
        $content = $this->processInternalLinks($content);
        
        // Optimizar imágenes
        $content = $this->optimizeImages($content);
        
        return $content;
    }

    /**
     * Sanitiza HTML para prevenir XSS
     */
    private function sanitizeHtml(string $html): string
    {
        // Lista de tags permitidos.
        //
        // `strip_tags` no filtra atributos: conserva los que lleve el tag. Pero al
        // eliminar un tag se lleva sus atributos con él, y por eso `button` hace
        // falta aquí: sin él, un acordeón de Bootstrap perdía `data-bs-toggle` y
        // dejaba de abrirse en el sitio publicado. Un `button` sin formulario que
        // lo envíe no ejecuta nada por sí solo.
        $allowedTags = [
            'div', 'span', 'p', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6',
            'a', 'img', 'ul', 'ol', 'li', 'table', 'tr', 'td', 'th',
            'strong', 'em', 'b', 'i', 'u', 's', 'br', 'hr', 'blockquote',
            'pre', 'code', 'section', 'article', 'header', 'footer',
            'main', 'nav', 'aside', 'figure', 'figcaption', 'button'
        ];
        
        // Eliminar eventos JavaScript.
        //
        // El patrón va ANCLADO a un espacio previo: así solo coincide con un
        // atributo que empiece por `on`. Sin ese ancla, `aria-controls="x"`
        // coincidía por dentro —«on» + «trols» + «="»— y quedaba convertido en
        // `aria-cr`, rompiendo el atributo y la accesibilidad del bloque.
        $html = preg_replace('/\son\w+\s*=\s*"[^"]*"/i', '', $html);
        $html = preg_replace("/\son\w+\s*=\s*'[^']*'/i", '', $html);
        
        // Eliminar javascript: en href
        $html = preg_replace('/href="javascript:[^"]*"/', 'href="#"', $html);
        $html = preg_replace("/href='javascript:[^']*'/", 'href="#"', $html);
        
        // Usar strip_tags con tags permitidos
        return strip_tags($html, '<' . implode('><', $allowedTags) . '>');
    }

    /**
     * Procesa enlaces internos
     */
    private function processInternalLinks(string $content): string
    {
        // Convertir enlaces internos de /slug a URL completa
        return preg_replace_callback(
            '/<a\s+href="\/([^"]+)"([^>]*)>/',
            function($matches) {
                $slug = $matches[1];
                $attrs = $matches[2];
                $baseUrl = rtrim(BASE_URL ?? '', '/');
                return '<a href="' . $baseUrl . '/' . $slug . '"' . $attrs . '>';
            },
            $content
        );
    }

    /**
     * Optimiza imágenes (añadir lazy loading)
     */
    private function optimizeImages(string $content): string
    {
        return preg_replace(
            '/<img\s+([^>]*)>/',
            '<img $1 loading="lazy" decoding="async">',
            $content
        );
    }

    /**
     * Optimiza CSS
     */
    private function optimizeCss(string $css): string
    {
        // Eliminar comentarios
        $css = preg_replace('/\/\*.*?\*\//s', '', $css);
        
        // Eliminar espacios innecesarios
        $css = preg_replace('/\s+/', ' ', $css);
        $css = preg_replace('/\s*([{}:;,])\s*/', '$1', $css);
        
        return $css;
    }

    /**
     * Optimiza JavaScript (básico)
     */
    private function optimizeJs(string $js): string
    {
        // Eliminar comentarios
        $js = preg_replace('/\/\/.*$/m', '', $js);
        $js = preg_replace('/\/\*.*?\*\//s', '', $js);
        
        // Eliminar espacios innecesarios
        $js = preg_replace('/\s+/', ' ', $js);
        
        return $js;
    }

    /**
     * Menú de navegación que corresponde a una página.
     *
     * El árbol lo resuelve MenuService (menú de la página → menú principal de la
     * ubicación → menú automático) y aquí solo se sanea y se cachea en memoria
     * para esta petición.
     *
     * @param array<string, mixed>|null $page
     * @return array<int, array<string, mixed>>
     */
    public function menuForPage(?array $page, string $location = 'header'): array
    {
        $cacheKey = 'menu_' . $location . '_' . (int) ($page['menu_id'] ?? 0) . '_' . (int) ($page['id'] ?? 0);

        if (isset(self::$menuCache[$cacheKey])) {
            return self::$menuCache[$cacheKey];
        }

        $service = new MenuService(new Database(), new ActivityLoggerService(new Database()));
        $tree = $service->navigationFor($page, $location);

        $menu = $this->sanitizeMenuItems($tree);
        self::$menuCache[$cacheKey] = $menu;

        return $menu;
    }

    /**
     * Menú por defecto (sin página concreta): útil para 404 y vistas de respaldo.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getMenu(?int $parentId = null): array
    {
        $menu = $this->menuForPage(null, 'header');

        if ($parentId === null) {
            return $menu;
        }

        // Compatibilidad: si alguien pide una rama concreta del menú resuelto.
        $find = static function (array $items, int $id) use (&$find): array {
            foreach ($items as $item) {
                if ((int) $item['id'] === $id) {
                    return $item['children'] ?? [];
                }
                if (!empty($item['children'])) {
                    $found = $find($item['children'], $id);
                    if ($found !== []) {
                        return $found;
                    }
                }
            }
            return [];
        };

        return $find($menu, $parentId);
    }

    /**
     * Sanea los ítems del menú para pintarlos (escape + slug normalizado).
     *
     * @param array<int, array<string, mixed>> $items
     * @return array<int, array<string, mixed>>
     */
    private function sanitizeMenuItems(array $items): array
    {
        $out = [];
        foreach ($items as $item) {
            $item['title'] = htmlspecialchars((string) ($item['title'] ?? ''), ENT_QUOTES, 'UTF-8');
            $item['icon'] = htmlspecialchars((string) ($item['icon'] ?? ''), ENT_QUOTES, 'UTF-8');
            $item['url'] = htmlspecialchars((string) ($item['url'] ?? ''), ENT_QUOTES, 'UTF-8');
            $item['slug'] = $this->sanitizeSlug((string) ($item['slug'] ?? ''));
            $item['target'] = htmlspecialchars((string) ($item['target'] ?? ''), ENT_QUOTES, 'UTF-8');

            $children = $item['children'] ?? [];
            $item['children'] = is_array($children) ? $this->sanitizeMenuItems($children) : [];

            $out[] = $item;
        }
        return $out;
    }

    /**
     * Construye la URL de un item del menú
     */
    public function buildMenuItemUrl(array $item): string
    {
        if (!empty($item['url'])) {
            return $item['url'];
        }

        $slug = trim($item['slug'] ?? '', '/');
        if (empty($slug)) {
            return '#';
        }

        $baseUrl = rtrim(BASE_URL ?? '', '/');
        return $baseUrl . '/' . $slug;
    }

    /**
     * Renderiza el menú completo como HTML
     */
    public function renderMenuHtml(array $menu): string
    {
        if (empty($menu)) {
            return '<ul class="navbar-nav ms-auto mb-2 mb-lg-0 align-items-lg-center"></ul>';
        }

        return '<ul class="navbar-nav ms-auto mb-2 mb-lg-0 align-items-lg-center">'
            . $this->renderMenuItems($menu, 0)
            . '</ul>';
    }

    /**
     * Renderiza los items del menú recursivamente
     */
    private function renderMenuItems(array $items, int $level = 0): string
    {
        if (empty($items)) {
            return '';
        }

        $html = '';

        foreach ($items as $item) {
            if (($item['status'] ?? '') !== 'published') {
                continue;
            }

            $children = $item['children'] ?? [];
            $hasChildren = !empty($children);
            $url = $this->buildMenuItemUrl($item);
            $urlAttr = htmlspecialchars($url, ENT_QUOTES, 'UTF-8');
            $title = htmlspecialchars($item['title'] ?? '', ENT_QUOTES, 'UTF-8');
            $targetAttr = !empty($item['target']) 
                ? ' target="' . htmlspecialchars($item['target'], ENT_QUOTES, 'UTF-8') . '"' 
                : '';
            $iconHtml = !empty($item['icon']) 
                ? '<i class="' . htmlspecialchars($item['icon'], ENT_QUOTES, 'UTF-8') . ' me-2"></i>' 
                : '';

            if ($level === 0) {
                if ($hasChildren) {
                    $html .= '<li class="nav-item dropdown has-submenu">';
                    $html .= '<div class="nav-link nav-link-parent">';
                    $html .= '<a class="split-link" href="' . $urlAttr . '"' . $targetAttr . '>';
                    $html .= $iconHtml . $title;
                    $html .= '</a>';
                    $html .= '<button type="button" class="menu-caret dropdown-caret" aria-haspopup="true" aria-expanded="false">';
                    $html .= '<i class="bi bi-chevron-down"></i>';
                    $html .= '</button>';
                    $html .= '</div>';
                    $html .= '<ul class="dropdown-menu">';
                    $html .= $this->renderMenuItems($children, $level + 1);
                    $html .= '</ul>';
                    $html .= '</li>';
                } else {
                    $html .= '<li class="nav-item">';
                    $html .= '<a class="nav-link" href="' . $urlAttr . '"' . $targetAttr . '>';
                    $html .= $iconHtml . $title;
                    $html .= '</a>';
                    $html .= '</li>';
                }
            } else {
                if ($hasChildren) {
                    $html .= '<li class="dropdown-submenu has-submenu">';
                    $html .= '<div class="dropdown-item dropdown-item-parent">';
                    $html .= '<a class="split-link" href="' . $urlAttr . '"' . $targetAttr . '>';
                    $html .= $iconHtml . $title;
                    $html .= '</a>';
                    $html .= '<button type="button" class="menu-caret submenu-caret" aria-haspopup="true" aria-expanded="false">';
                    $html .= '<i class="bi bi-chevron-right"></i>';
                    $html .= '</button>';
                    $html .= '</div>';
                    $html .= '<ul class="dropdown-menu">';
                    $html .= $this->renderMenuItems($children, $level + 1);
                    $html .= '</ul>';
                    $html .= '</li>';
                } else {
                    $html .= '<li>';
                    $html .= '<a class="dropdown-item" href="' . $urlAttr . '"' . $targetAttr . '>';
                    $html .= $iconHtml . $title;
                    $html .= '</a>';
                    $html .= '</li>';
                }
            }
        }

        return $html;
    }

    /**
     * Construye el HTML completo de una página
     */
    public function buildPageHtml(array $page): string
    {
        if (empty($page)) {
            return '<div class="page-content"><p>Página no disponible</p></div>';
        }

        $title = htmlspecialchars($page['title'] ?? 'Sin título', ENT_QUOTES, 'UTF-8');
        $desc = htmlspecialchars($page['description'] ?? '', ENT_QUOTES, 'UTF-8');
        $slug = $this->sanitizeSlug($page['slug'] ?? '');
        $css = (string)($page['content_css'] ?? '');
        $html = (string)($page['content_html'] ?? '');
        $js = (string)($page['content_js'] ?? '');

        // Si no hay contenido HTML, mostrar mensaje
        if (empty($html) && empty($css) && empty($js)) {
            return '<div class="page-content"><p>Esta página está en construcción.</p></div>';
        }

        // Sanitizar HTML antes de mostrarlo
        $html = $this->sanitizeHtml($html);

        // Evita cierre prematuro del bloque <script>
        $js = str_replace('</script>', '<\/script>', $js);

        // Construir estructura de la página
        $htmlOutput = '<div class="page-content" data-page-id="' . $page['id'] . '" data-page-slug="' . $slug . '">';
        
        if (!empty($css)) {
            $htmlOutput .= '<style>' . $this->optimizeCss($css) . '</style>';
        }
        
        $htmlOutput .= '<div class="page-body">' . $html . '</div>';
        
        if (!empty($js)) {
            $htmlOutput .= '<script>' . $this->optimizeJs($js) . '</script>';
        }
        
        $htmlOutput .= '</div>';

        return $htmlOutput;
    }

    /**
     * Sanitiza un slug
     */
    private function sanitizeSlug(string $slug): string
    {
        $slug = strtolower(trim($slug));
        $slug = urldecode($slug);
        $slug = preg_replace('/[^a-z0-9\-_\/]/', '-', $slug);
        $slug = preg_replace('/-+/', '-', $slug);
        return trim($slug, '-/') ?: 'pagina-' . time();
    }

    /**
     * Limpia la caché del renderer
     */
    public function clearCache(): void
    {
        self::$pageCache = [];
        self::$menuCache = [];
    }

    /**
     * Obtiene el título de la página para el head
     */
    public function getPageTitle(?array $page): string
    {
        if (empty($page)) {
            return 'Página no encontrada';
        }
        
        return htmlspecialchars($page['title'] ?? 'Sin título', ENT_QUOTES, 'UTF-8');
    }

    /**
     * Obtiene la descripción de la página para el head
     */
    public function getPageDescription(?array $page): string
    {
        if (empty($page)) {
            return 'Página no encontrada';
        }
        
        return htmlspecialchars($page['description'] ?? '', ENT_QUOTES, 'UTF-8');
    }

    /**
     * Obtiene el slug de la página para el head
     */
    public function getPageSlug(?array $page): string
    {
        if (empty($page)) {
            return '404';
        }
        
        return $this->sanitizeSlug($page['slug'] ?? '');
    }
}
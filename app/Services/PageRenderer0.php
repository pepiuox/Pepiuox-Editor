<?php
namespace App\Services;

use App\Core\Database;

/**
 * PageRenderer - Servicio para renderizar páginas públicas
 * 
 * Funcionalidades:
 * - Carga páginas desde la base de datos
 * - Renderiza menús multinivel
 * - Maneja caché de páginas
 * - Genera URLs amigables
 */
class PageRenderer
{
    private int $startpage = 1;
    private int $is_published = 1;
    private string $status = 'published';

    /**
     * Obtiene la página de inicio
     */
    public function getHomePage(): ?array
    {
        return Database::fetch(
            "SELECT * FROM pages 
             WHERE startpage = ? AND is_published = ? AND status = ?
             LIMIT 1",
            [$this->startpage, $this->is_published, $this->status]
        );
    }

    /**
     * Obtiene una página por slug
     */
    public function getPageBySlug(string $slug): ?array
    {
        return Database::fetch(
            "SELECT * FROM pages 
             WHERE slug = ? AND is_published = ? AND status = ?",
            [$slug, $this->is_published, $this->status]
        );
    }

    /**
     * Obtiene el contenido completo de una página por ID
     */
    public function getPageContent(int $id): ?array
    {
        return Database::fetch(
            "SELECT id, title, slug, description, content_html, content_css, 
                    content_php, content_js, status, meta_title, meta_description
             FROM pages WHERE id = ?",
            [$id]
        );
    }

    /**
     * Verifica si una página existe y está publicada
     */
    public function pageExists(string $slug): bool
    {
        $result = Database::fetch(
            "SELECT COUNT(*) AS count FROM pages 
             WHERE slug = ? AND status = ?",
            [$slug, $this->status]
        );
        return ($result['count'] ?? 0) > 0;
    }

    /**
     * Genera una URL segura para una página
     */
    public function generatePageUrl(string $slug): string
    {
        $baseUrl = rtrim(BASE_URL ?? '', '/');
        $safeSlug = $this->sanitizeSlug($slug);
        return $baseUrl . '/' . $safeSlug;
    }

    /**
     * Sanitiza un slug para uso en URLs
     */
    public function sanitizeSlug(string $slug): string
    {
        $slug = strtolower(trim($slug));
        $slug = preg_replace('/[^a-z0-9\-]/', '-', $slug);
        $slug = preg_replace('/-+/', '-', $slug);
        return trim($slug, '-');
    }

    /**
     * Obtiene el menú completo multinivel
     */
    public function getMenu(?int $parentId = null): array
    {
        if ($parentId === null) {
            $items = Database::fetchAll(
                "SELECT * FROM page_menu 
                 WHERE parent_id IS NULL AND status = 'published' AND is_published = 1
                 ORDER BY `order` ASC, sort ASC"
            );
        } else {
            $items = Database::fetchAll(
                "SELECT * FROM page_menu 
                 WHERE parent_id = ? AND status = 'published' AND is_published = 1
                 ORDER BY `order` ASC, sort ASC",
                [$parentId]
            );
        }

        foreach ($items as &$item) {
            $item['children'] = $this->getMenu((int)$item['id']);
        }

        return $items;
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
        if ($slug === '') {
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
                // Nivel principal
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
                // Submenús
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
     * Obtiene el título SEO de una página
     */
    public function getPageSeoTitle(array $page): string
    {
        if (!empty($page['meta_title'])) {
            return $page['meta_title'];
        }
        
        $siteName = SettingService::get('general', 'site_name', 'PePiuoX Maker');
        return ($page['title'] ?? 'Página') . ' · ' . $siteName;
    }

    /**
     * Obtiene la descripción SEO de una página
     */
    public function getPageSeoDescription(array $page): string
    {
        if (!empty($page['meta_description'])) {
            return $page['meta_description'];
        }
        
        return $page['description'] ?? '';
    }

    /**
     * Construye el HTML completo de una página
     */
    public function buildPageHtml(array $page): string
    {
        $title = htmlspecialchars($this->getPageSeoTitle($page), ENT_QUOTES, 'UTF-8');
        $description = htmlspecialchars($this->getPageSeoDescription($page), ENT_QUOTES, 'UTF-8');
        $css = $page['content_css'] ?? '';
        $html = $page['content_html'] ?? '';
        $js = $page['content_js'] ?? '';

        // Evitar cierre prematuro de script
        $js = str_replace('</script>', '<\/script>', $js);

        return <<<HTML
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{$title}</title>
    <meta name="description" content="{$description}">
    <meta name="generator" content="PePiuoX Maker">
    <style>{$css}</style>
</head>
<body data-page-id="{$page['id']}" data-page-slug="{$page['slug']}">
{$html}
<script>{$js}</script>
</body>
</html>
HTML;
    }
}
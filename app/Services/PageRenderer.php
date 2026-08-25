<?php
namespace App\Services;

use App\Core\Database;

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
        $title = htmlspecialchars($page['title'], ENT_QUOTES, 'UTF-8');
        $desc = htmlspecialchars($page['description'] ?? '', ENT_QUOTES, 'UTF-8');
        $slug = $this->sanitizeSlug($page['slug']);
        $css = (string)($page['content_css'] ?? '');
        $html = (string)($page['content_html'] ?? '');
        $js = (string)($page['content_js'] ?? '');

        // Evita cierre prematuro del bloque <script>
        $js = str_replace('</script>', '<\/script>', $js);

        return <<<HTML
<div class="page-content" data-page-id="{$page['id']}" data-page-slug="{$slug}">
    <style>{$css}</style>
    {$html}
    <script>{$js}</script>
</div>
HTML;
    }

    /**
     * Sanitiza un slug
     */
    private function sanitizeSlug(string $slug): string
    {
        $slug = strtolower(trim($slug));
        $slug = preg_replace('/[^a-z0-9\-]/', '-', $slug);
        $slug = preg_replace('/-+/', '-', $slug);
        return trim($slug, '-') ?: 'pagina-' . time();
    }
}
<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

/**
 * Modelo de páginas con inyección de dependencias (PHP 8.2+)
 * 
 * Gestiona operaciones CRUD completas para admin y frontend público.
 * Todos los métodos incluyen los 16 campos completos de la tabla pages.
 * 
 * @package App\Models
 */
final class PageModel
{
    public function __construct(
        private readonly Database $db
    ) {}

    // ═══════════════════════════════════════════════════════════════
    // 📋 CONSULTAS CON FILTROS (Admin)
    // ═══════════════════════════════════════════════════════════════

    /**
     * Obtiene páginas con filtros avanzados (admin)
     */
    public function getPages(int $userId, string $search = '', string $filterStatus = ''): array
    {
        $sql = "SELECT 
                    p.*, 
                    u.username AS author_name, 
                    t.name AS template_name
                FROM pages p
                LEFT JOIN users u ON u.id = p.user_id
                LEFT JOIN templates t ON t.id = p.template_id
                WHERE p.user_id = ?";
        
        $params = [$userId];

        if ($search !== '') {
            $sql .= " AND (p.title LIKE ? OR p.slug LIKE ? OR p.description LIKE ?)";
            $params[] = "%{$search}%";
            $params[] = "%{$search}%";
            $params[] = "%{$search}%";
        }

        if ($filterStatus !== '' && \in_array($filterStatus, ['draft', 'published', 'archived'], true)) {
            $sql .= " AND p.status = ?";
            $params[] = $filterStatus;
        }

        $sql .= " ORDER BY p.updated_at DESC";

        return $this->db->fetchAll($sql, $params);
    }

    /**
     * Obtiene una página por ID con filtro de usuario
     */
    public function getPage(int $id, int $userId): ?array
    {
        return $this->db->fetch(
            "SELECT * FROM pages WHERE id = ? AND user_id = ?",
            [$id, $userId]
        );
    }

    /**
     * Obtiene una página por ID sin filtro de usuario (admin global)
     */
    public function getPageById(int $id): ?array
    {
        return $this->db->fetch("SELECT * FROM pages WHERE id = ?", [$id]);
    }

    /**
     * Obtiene una página por slug con información relacionada
     */
    public function findBySlug(string $slug): ?array
    {
        return $this->db->fetch(
            "SELECT 
                p.*, 
                u.username AS author_name, 
                t.name AS template_name
             FROM pages p
             LEFT JOIN users u ON u.id = p.user_id
             LEFT JOIN templates t ON t.id = p.template_id
             WHERE p.slug = ?",
            [$slug]
        );
    }

    /**
     * Obtiene una página por slug con filtro de usuario
     */
    public function getBySlug(string $slug, int $userId): ?array
    {
        return $this->db->fetch(
            "SELECT * FROM pages WHERE slug = ? AND user_id = ?",
            [$slug, $userId]
        );
    }

    // ═══════════════════════════════════════════════════════════════
    // ➕ CREAR Y ACTUALIZAR (con todos los campos)
    // ═══════════════════════════════════════════════════════════════

    /**
     * Crea una nueva página con todos los campos
     * 
     * @param array $data Datos completos de la página
     * @return int ID de la página creada
     */
    public function create(array $data): int
    {
        $sql = "INSERT INTO pages (
                    user_id, template_id, menu_id, parent_id, title, slug, description, h1,
                    content_html, content_css, content_js, content_php, grapesjs_data,
                    startpage, is_published, status, publish_at
                ) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)";
        
        return (int) $this->db->insert($sql, [
            $data['user_id'],
            $data['template_id'] ?? null,
            $data['menu_id'] ?? null,
            $data['parent_id'] ?? null,
            $data['title'],
            $data['slug'],
            $data['description'] ?? '',
            $data['h1'] ?? null,
            $data['content_html'] ?? '',
            $data['content_css'] ?? '',
            $data['content_js'] ?? '',
            $data['content_php'] ?? '',
            $data['grapesjs_data'] ?? null,
            $data['startpage'] ?? 0,
            $data['is_published'] ?? 0,
            $data['status'] ?? 'draft',
            $data['publish_at'] ?? null,
        ]);
    }

    /**
     * Actualiza una página existente con todos los campos
     * 
     * @param int   $id     ID de la página
     * @param array $data   Datos a actualizar
     * @param int   $userId ID del usuario (para verificar propiedad)
     * @return bool True si se actualizó correctamente
     */
    public function update(int $id, array $data, int $userId): bool
    {
        $sql = "UPDATE pages SET
                    template_id = ?, 
                    menu_id = ?,
                    parent_id = ?, 
                    title = ?, 
                    slug = ?,
                    description = ?, 
                    h1 = ?,
                    content_html = ?, 
                    content_css = ?, 
                    content_js = ?, 
                    content_php = ?,
                    grapesjs_data = ?,
                    startpage = ?, 
                    is_published = ?, 
                    status = ?, 
                    publish_at = ?
                WHERE id = ? AND user_id = ?";
        
        return (bool) $this->db->execute($sql, [
            $data['template_id'] ?? null,
            $data['menu_id'] ?? null,
            $data['parent_id'] ?? null,
            $data['title'],
            $data['slug'],
            $data['description'] ?? '',
            $data['h1'] ?? null,
            $data['content_html'] ?? '',
            $data['content_css'] ?? '',
            $data['content_js'] ?? '',
            $data['content_php'] ?? '',
            $data['grapesjs_data'] ?? null,
            $data['startpage'] ?? 0,
            $data['is_published'] ?? 0,
            $data['status'] ?? 'draft',
            $data['publish_at'] ?? null,
            $id,
            $userId
        ]);
    }

    /**
     * Actualiza una página sin filtro de usuario (admin global)
     */
    public function updateById(int $id, array $data): bool
    {
        $fields = [];
        $params = [];
        
        $allowed = [
            'template_id', 'menu_id', 'parent_id', 'title', 'slug', 'description', 'h1',
            'content_html', 'content_css', 'content_js', 'content_php', 'grapesjs_data',
            'startpage', 'is_published', 'status', 'publish_at'
        ];
        
        foreach ($allowed as $f) {
            if (\array_key_exists($f, $data)) {
                $fields[] = "`$f` = ?";
                $params[] = $data[$f];
            }
        }
        
        if (!$fields) {
            return false;
        }
        
        $params[] = $id;
        
        return (bool) $this->db->execute(
            "UPDATE pages SET " . \implode(',', $fields) . " WHERE id = ?",
            $params
        );
    }

    /**
     * Elimina una página
     */
    public function delete(int $id, int $userId): bool
    {
        return (bool) $this->db->execute(
            "DELETE FROM pages WHERE id = ? AND user_id = ?",
            [$id, $userId]
        );
    }

    /**
     * Elimina una página sin filtro de usuario (admin global)
     */
    public function deleteById(int $id): bool
    {
        return (bool) $this->db->execute("DELETE FROM pages WHERE id = ?", [$id]);
    }

    // ═══════════════════════════════════════════════════════════════
    // 📋 DUPLICAR Y CAMBIAR ESTADO
    // ═══════════════════════════════════════════════════════════════

    /**
     * Duplica una página con slug único
     */
    public function duplicate(int $id, int $userId): ?int
    {
        $page = $this->getPage($id, $userId);
        if (!$page) {
            return null;
        }

        $newSlug = $this->uniqueSlug($page['slug'] . '-copia');
        
        $data = [
            'user_id'        => $userId,
            'template_id'    => $page['template_id'],
            'parent_id'      => $page['parent_id'],
            'title'          => $page['title'] . ' (copia)',
            'slug'           => $newSlug,
            'description'    => $page['description'],
            'h1'             => $page['h1'],
            'content_html'   => $page['content_html'],
            'content_css'    => $page['content_css'],
            'content_js'     => $page['content_js'],
            'content_php'    => $page['content_php'],
            'grapesjs_data'  => $page['grapesjs_data'],
            'startpage'      => 0,
            'is_published'   => 0,
            'status'         => 'draft',
            'publish_at'     => null,
        ];

        return $this->create($data);
    }

    /**
     * Cambia el estado de una página
     */
    public function toggleStatus(int $id, string $newStatus, int $userId): bool
    {
        $isPub = ($newStatus === 'published') ? 1 : 0;
        
        return (bool) $this->db->execute(
            "UPDATE pages SET status = ?, is_published = ? WHERE id = ? AND user_id = ?",
            [$newStatus, $isPub, $id, $userId]
        );
    }

    // ═══════════════════════════════════════════════════════════════
    // 🏠 PÁGINA DE INICIO
    // ═══════════════════════════════════════════════════════════════

    /**
     * Establece una página como página de inicio
     */
    public function setStartpage(int $id, int $userId): bool
    {
        $this->db->execute(
            "UPDATE pages SET startpage = 0 WHERE user_id = ?",
            [$userId]
        );
        
        return (bool) $this->db->execute(
            "UPDATE pages SET startpage = 1 WHERE id = ? AND user_id = ?",
            [$id, $userId]
        );
    }

    /**
     * Obtiene la página de inicio
     */
    public function getStartPage(): ?array
    {
        return $this->db->fetch(
            "SELECT * FROM pages 
             WHERE startpage = 1 
               AND is_published = 1 
               AND status = 'published' 
             LIMIT 1"
        );
    }

    // ═══════════════════════════════════════════════════════════════
    // 🌐 FRONTEND: PÁGINAS PÚBLICAS
    // ═══════════════════════════════════════════════════════════════

    /**
     * Obtiene una página pública por slug
     */
    public function getPublicPageBySlug(string $slug): ?array
    {
        return $this->db->fetch(
            "SELECT p.*, u.username AS author_name, t.name AS template_name
             FROM pages p
             LEFT JOIN users u ON u.id = p.user_id
             LEFT JOIN templates t ON t.id = p.template_id
             WHERE p.slug = ? 
               AND p.is_published = 1 
               AND p.status = 'published'",
            [$slug]
        );
    }

    /**
     * Obtiene todas las páginas públicas publicadas
     */
    public function getPublishedPages(): array
    {
        return $this->db->fetchAll(
            "SELECT * FROM pages
             WHERE is_published = 1 
               AND status = 'published'
             ORDER BY updated_at DESC"
        );
    }

    /**
     * Obtiene páginas para generar sitemap
     */
    public function publishedForSitemap(): array
    {
        return $this->db->fetchAll(
            "SELECT slug, updated_at FROM pages
             WHERE status = 'published' 
               AND is_published = 1
             ORDER BY updated_at DESC"
        );
    }

    /**
     * Obtiene páginas que usan una plantilla específica
     */
    public function usingTemplate(int $templateId): array
    {
        return $this->db->fetchAll(
            "SELECT id, slug FROM pages WHERE template_id = ?",
            [$templateId]
        );
    }

    // ═══════════════════════════════════════════════════════════════
    // 🔍 BÚSQUEDA AVANZADA
    // ═══════════════════════════════════════════════════════════════

    /**
     * Busca páginas por término (búsqueda en contenido)
     */
    public function searchPages(string $term, int $userId): array
    {
        $searchTerm = "%{$term}%";
        
        return $this->db->fetchAll(
            "SELECT * FROM pages
             WHERE user_id = ?
               AND (title LIKE ? 
                    OR slug LIKE ? 
                    OR description LIKE ? 
                    OR content_html LIKE ?)
             ORDER BY updated_at DESC",
            [$userId, $searchTerm, $searchTerm, $searchTerm, $searchTerm]
        );
    }

    // ═══════════════════════════════════════════════════════════════
    // 🔧 UTILIDADES: SLUGS
    // ═══════════════════════════════════════════════════════════════

    /**
     * Genera un slug único
     */
    public function uniqueSlug(string $base, ?int $ignoreId = null): string
    {
        $slug = $this->slugify($base);
        $original = $slug;
        $i = 1;

        while (true) {
            $sql = "SELECT COUNT(*) AS c FROM pages WHERE slug = ?";
            $p = [$slug];

            if ($ignoreId !== null) {
                $sql .= " AND id <> ?";
                $p[] = $ignoreId;
            }

            $row = $this->db->fetch($sql, $p);
            
            if ((int) $row['c'] === 0) {
                return $slug;
            }

            $slug = $original . '-' . (++$i);
        }
    }

    /**
     * Convierte texto a slug URL-friendly
     */
    public function slugify(string $text): string
    {
        $text = \mb_strtolower($text);
        $text = \transliterator_transliterate('Any-Latin; Latin-ASCII; Lower()', $text) ?: $text;
        $text = \preg_replace('/[^a-z0-9\s-]/', '', $text);
        $text = \preg_replace('/[\s-]+/', '-', $text);
        $text = \trim($text, '-');
        
        return $text !== '' ? $text : 'page';
    }
}
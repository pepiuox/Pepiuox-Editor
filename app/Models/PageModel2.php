<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

/**
 * Modelo de páginas con inyección de dependencias
 * 
 * Gestiona operaciones CRUD completas para el panel de administración
 * y operaciones de lectura para el frontend público.
 * 
 * Características:
 * - Soporte completo para todos los campos de páginas
 * - Filtros por usuario, estado y búsqueda
 * - Generación de slugs únicos
 * - Duplicación de páginas
 * - Gestión de página de inicio
 * - Consultas optimizadas con JOINs
 * 
 * @package App\Models
 */
class PageModel
{
    private Database $db;

    public function __construct(Database $db)
    {
        $this->db = $db;
    }

    // ═══════════════════════════════════════════════════════════════
    // 📋 ADMIN: CONSULTAS CON FILTROS
    // ═══════════════════════════════════════════════════════════════

    /**
     * Obtiene páginas con filtros avanzados (admin)
     * 
     * @param int    $userId       ID del usuario (filtra por propietario)
     * @param string $search       Término de búsqueda (título, slug, descripción)
     * @param string $filterStatus Estado: draft|published|archived
     * @return array Lista de páginas con author_name y template_name
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
     * 
     * @param int $id     ID de la página
     * @param int $userId ID del usuario (propietario)
     * @return array|null Datos de la página o null si no existe/no pertenece al usuario
     */
    public function getPage(int $id, int $userId): ?array
    {
        return $this->db->fetch(
            "SELECT * FROM pages WHERE id = ? AND user_id = ?",
            [$id, $userId]
        );
    }

    /**
     * Obtiene una página por ID sin filtro de usuario
     * Útil para administradores con permisos globales
     * 
     * @param int $id ID de la página
     * @return array|null Datos de la página o null si no existe
     */
    public function getPageById(int $id): ?array
    {
        return $this->db->fetch("SELECT * FROM pages WHERE id = ?", [$id]);
    }

    /**
     * Obtiene una página por slug con información relacionada
     * 
     * @param string $slug Slug de la página
     * @return array|null Datos de la página con author_name y template_name
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
     * 
     * @param string $slug   Slug de la página
     * @param int    $userId ID del usuario (propietario)
     * @return array|null Datos de la página o null
     */
    public function getBySlug(string $slug, int $userId): ?array
    {
        return $this->db->fetch(
            "SELECT * FROM pages WHERE slug = ? AND user_id = ?",
            [$slug, $userId]
        );
    }

    // ═══════════════════════════════════════════════════════════════
    // ➕ ADMIN: CREAR Y ACTUALIZAR
    // ═══════════════════════════════════════════════════════════════

    /**
     * Crea una nueva página con todos los campos
     * 
     * @param array $data Datos de la página:
     *   - user_id (int, requerido)
     *   - template_id (int|null)
     *   - parent_id (int|null)
     *   - title (string, requerido)
     *   - slug (string, requerido)
     *   - description (string)
     *   - h1 (string|null)
     *   - content_html (string)
     *   - content_css (string)
     *   - content_js (string)
     *   - content_php (string)
     *   - grapesjs_data (string|null, JSON)
     *   - startpage (int, 0|1)
     *   - is_published (int, 0|1)
     *   - status (string: draft|published|archived)
     *   - publish_at (string|null, datetime)
     * @return int ID de la página creada
     */
    public function create(array $data): int
    {
        $sql = "INSERT INTO pages (
                    user_id, template_id, parent_id, title, slug, description, h1,
                    content_html, content_css, content_js, content_php, grapesjs_data,
                    startpage, is_published, status, publish_at
                ) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)";
        
        return (int) $this->db->insert($sql, [
            $data['user_id'],
            $data['template_id'] ?? null,
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
     * Actualiza una página existente
     * 
     * @param int   $id     ID de la página
     * @param array $data   Datos a actualizar (mismos campos que create)
     * @param int   $userId ID del usuario (para verificar propiedad)
     * @return bool True si se actualizó correctamente
     */
    public function update(int $id, array $data, int $userId): bool
    {
        $sql = "UPDATE pages SET
                    template_id = ?, 
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
     * Elimina una página
     * 
     * @param int $id     ID de la página
     * @param int $userId ID del usuario (para verificar propiedad)
     * @return bool True si se eliminó correctamente
     */
    public function delete(int $id, int $userId): bool
    {
        return (bool) $this->db->execute(
            "DELETE FROM pages WHERE id = ? AND user_id = ?",
            [$id, $userId]
        );
    }

    // ═══════════════════════════════════════════════════════════════
    // 📋 ADMIN: DUPLICAR Y CAMBIAR ESTADO
    // ═══════════════════════════════════════════════════════════════

    /**
     * Duplica una página con slug único
     * 
     * @param int $id     ID de la página original
     * @param int $userId ID del usuario que duplica
     * @return int|null ID de la nueva página o null si falla
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
     * 
     * @param int    $id        ID de la página
     * @param string $newStatus Nuevo estado: draft|published|archived
     * @param int    $userId    ID del usuario (para verificar propiedad)
     * @return bool True si se actualizó correctamente
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
     * Desactiva todas las demás páginas de inicio del usuario
     * 
     * @param int $id     ID de la página
     * @param int $userId ID del usuario
     * @return bool True si se actualizó correctamente
     */
    public function setStartpage(int $id, int $userId): bool
    {
        // Desactivar todas las páginas de inicio del usuario
        $this->db->execute(
            "UPDATE pages SET startpage = 0 WHERE user_id = ?",
            [$userId]
        );
        
        // Activar la página seleccionada
        return (bool) $this->db->execute(
            "UPDATE pages SET startpage = 1 WHERE id = ? AND user_id = ?",
            [$id, $userId]
        );
    }

    /**
     * Obtiene la página de inicio
     * 
     * @return array|null Datos de la página de inicio o null si no existe
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
     * Solo devuelve páginas publicadas y visibles
     * 
     * @param string $slug Slug de la página
     * @return array|null Datos de la página o null
     */
    public function getPublicPageBySlug(string $slug): ?array
    {
        return $this->db->fetch(
            "SELECT * FROM pages
             WHERE slug = ? 
               AND is_published = 1 
               AND status = 'published'",
            [$slug]
        );
    }

    /**
     * Obtiene todas las páginas públicas publicadas
     * 
     * @return array Lista de páginas publicadas
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
     * 
     * @return array Lista de slugs y fechas de actualización
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

    // ═══════════════════════════════════════════════════════════════
    // 🔍 BÚSQUEDA AVANZADA
    // ═══════════════════════════════════════════════════════════════

    /**
     * Busca páginas por término (búsqueda en contenido)
     * 
     * @param string $term   Término de búsqueda
     * @param int    $userId ID del usuario
     * @return array Lista de páginas que coinciden
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
     * Si el slug ya existe, agrega un sufijo numérico
     * 
     * @param string   $base     Slug base
     * @param int|null $ignoreId ID a ignorar (para actualizaciones)
     * @return string Slug único
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
     * 
     * @param string $text Texto a convertir
     * @return string Slug generado
     */
    public function slugify(string $text): string
    {
        $text = \mb_strtolower($text);
        
        // Transliterar caracteres especiales a ASCII
        $text = \transliterator_transliterate('Any-Latin; Latin-ASCII; Lower()', $text) ?: $text;
        
        // Eliminar caracteres no alfanuméricos (excepto espacios y guiones)
        $text = \preg_replace('/[^a-z0-9\s-]/', '', $text);
        
        // Reemplazar espacios y guiones múltiples por un solo guión
        $text = \preg_replace('/[\s-]+/', '-', $text);
        
        // Eliminar guiones al inicio y final
        $text = \trim($text, '-');
        
        return $text !== '' ? $text : 'page';
    }
}
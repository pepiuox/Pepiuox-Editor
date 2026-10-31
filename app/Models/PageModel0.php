<?php
namespace App\Models;

use App\Core\Database;
use PDO;

/**
 * Modelo para gestión de páginas
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

    /**
     * Obtiene páginas con filtros
     */
    public function getPages(int $userId, string $search = '', string $filterStatus = ''): array
    {
        $sql = "SELECT * FROM pages WHERE user_id = ?";
        $params = [$userId];

        if ($search) {
            $sql .= " AND (title LIKE ? OR slug LIKE ? OR description LIKE ?)";
            $params[] = "%{$search}%";
            $params[] = "%{$search}%";
            $params[] = "%{$search}%";
        }

        if ($filterStatus && in_array($filterStatus, ['draft', 'published', 'archived'])) {
            $sql .= " AND status = ?";
            $params[] = $filterStatus;
        }

        $sql .= " ORDER BY updated_at DESC";

        return $this->db->fetchAll($sql, $params);
    }

    /**
     * Obtiene una página por ID
     */
    public function getPage(int $id, int $userId): ?array
    {
        return $this->db->fetch(
            "SELECT * FROM pages WHERE id = ? AND user_id = ?",
            [$id, $userId]
        );
    }

    /**
     * Obtiene página por slug
     */
    public function getBySlug(string $slug, int $userId): ?array
    {
        return $this->db->fetch(
            "SELECT id FROM pages WHERE slug = ? AND user_id = ?",
            [$slug, $userId]
        );
    }

    /**
     * Crea una nueva página
     */
    public function create(array $data): int
    {
        $sql = "INSERT INTO pages (
            user_id, title, slug, description, 
            content_html, content_css, content_php, content_js, 
            status, is_published, startpage
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

        return (int)$this->db->insert($sql, [
            $data['user_id'],
            $data['title'],
            $data['slug'],
            $data['description'],
            $data['content_html'] ?? '',
            $data['content_css'] ?? '',
            $data['content_php'] ?? '',
            $data['content_js'] ?? '',
            $data['status'] ?? 'draft',
            $data['is_published'] ?? 0,
            $data['startpage'] ?? 0
        ]);
    }

    /**
     * Actualiza una página
     */
    public function update(int $id, array $data, int $userId): bool
    {
        $sql = "UPDATE pages SET 
            title = ?, slug = ?, description = ?, 
            content_html = ?, content_css = ?, content_php = ?, content_js = ?, 
            status = ?, is_published = ?, startpage = ? 
            WHERE id = ? AND user_id = ?";

        return $this->db->execute($sql, [
            $data['title'],
            $data['slug'],
            $data['description'],
            $data['content_html'] ?? '',
            $data['content_css'] ?? '',
            $data['content_php'] ?? '',
            $data['content_js'] ?? '',
            $data['status'] ?? 'draft',
            $data['is_published'] ?? 0,
            $data['startpage'] ?? 0,
            $id,
            $userId
        ]);
    }

    /**
     * Elimina una página
     */
    public function delete(int $id, int $userId): bool
    {
        return $this->db->execute(
            "DELETE FROM pages WHERE id = ? AND user_id = ?",
            [$id, $userId]
        );
    }

    /**
     * Duplica una página
     */
    public function duplicate(int $id, int $userId): ?int
    {
        $page = $this->getPage($id, $userId);
        if (!$page) {
            return null;
        }

        $newSlug = $page['slug'] . '-copia-' . time();

        $data = [
            'user_id' => $userId,
            'title' => $page['title'] . ' (copia)',
            'slug' => $newSlug,
            'description' => $page['description'],
            'content_html' => $page['content_html'],
            'content_css' => $page['content_css'],
            'content_php' => $page['content_php'],
            'content_js' => $page['content_js'],
            'status' => 'draft',
            'is_published' => 0,
            'startpage' => 0
        ];

        return $this->create($data);
    }

    /**
     * Cambia el estado de una página
     */
    public function toggleStatus(int $id, string $newStatus, int $userId): bool
    {
        $isPub = ($newStatus === 'published') ? 1 : 0;
        return $this->db->execute(
            "UPDATE pages SET status = ?, is_published = ? WHERE id = ? AND user_id = ?",
            [$newStatus, $isPub, $id, $userId]
        );
    }

    /**
     * Establece la página de inicio
     */
    public function setStartpage(int $id, int $userId): bool
    {
        // Desactivar todas las páginas de inicio
        $this->db->execute(
            "UPDATE pages SET startpage = 0 WHERE user_id = ?",
            [$userId]
        );

        // Activar la página seleccionada
        return $this->db->execute(
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
            "SELECT * FROM pages WHERE startpage = 1 AND is_published = 1 AND status = 'published' LIMIT 1"
        );
    }

    /**
     * Obtiene una página pública por slug
     */
    public function getPublicPageBySlug(string $slug): ?array
    {
        return $this->db->fetch(
            "SELECT * FROM pages 
             WHERE slug = ? AND is_published = 1 AND status = 'published'",
            [$slug]
        );
    }

    /**
     * Obtiene páginas públicas publicadas
     */
    public function getPublishedPages(): array
    {
        return $this->db->fetchAll(
            "SELECT * FROM pages 
             WHERE is_published = 1 AND status = 'published' 
             ORDER BY updated_at DESC"
        );
    }

    /**
     * Busca páginas por término
     */
    public function searchPages(string $term, int $userId): array
    {
        $searchTerm = "%{$term}%";
        return $this->db->fetchAll(
            "SELECT * FROM pages 
             WHERE user_id = ? 
             AND (title LIKE ? OR slug LIKE ? OR description LIKE ? OR content_html LIKE ?)
             ORDER BY updated_at DESC",
            [$userId, $searchTerm, $searchTerm, $searchTerm, $searchTerm]
        );
    }
}
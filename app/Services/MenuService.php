<?php
namespace App\Services;

use App\Core\Database;
use App\Core\Auth;
use App\Services\ActivityLoggerService;
use Throwable;

/**
 * Servicio de gestión de menús
 * 
 * @package App\Services
 */
class MenuService
{
    private Database $db;
    private ActivityLoggerService $logger;
    private string $table = 'menus';

    public function __construct(Database $db, ActivityLoggerService $logger)
    {
        $this->db = $db;
        $this->logger = $logger;
    }

    /**
     * Obtiene menú en formato plano (para administración)
     */
    public function getFlatMenu(string $group = 'main'): array
    {
        try {
            return $this->db->fetchAll(
                "SELECT * FROM {$this->table} 
                 WHERE menu_group = ? 
                 ORDER BY parent_id ASC, `order` ASC",
                [$group]
            );
        } catch (Throwable $e) {
            error_log('[MenuService] Error en getFlatMenu: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Obtiene menú completo estructurado
     */
    public function getNavigationMenu(string $group = 'main'): array
    {
        $items = $this->db->fetchAll(
            "SELECT * FROM {$this->table} 
             WHERE menu_group = ? AND is_published = 1 AND status = 'published'
             ORDER BY parent_id ASC, `order` ASC",
            [$group]
        );
        return $this->buildNavigationTree($items);
    }

    /**
     * Construye el árbol de navegación
     */
    public function buildNavigationTree(array $items, ?int $parentId = null): array
    {
        $branch = [];
        foreach ($items as $item) {
            if ((int)$item['parent_id'] === (int)$parentId) {
                $children = $this->buildNavigationTree($items, (int)$item['id']);
                if (!empty($children)) {
                    $item['children'] = $children;
                }
                $branch[] = $item;
            }
        }
        return $branch;
    }

    /**
     * Obtiene el menú para administración
     */
    public function getAdminMenu(int $userId): array
    {
        $items = $this->db->fetchAll(
            "SELECT * FROM {$this->table} 
             WHERE user_id = ? 
             ORDER BY menu_group, parent_id, `order`",
            [$userId]
        );
        return $this->buildNavigationTree($items);
    }

    /**
     * Obtiene el próximo orden para un grupo
     */
    public function getNextOrder(string $group): int
    {
        $result = $this->db->fetch(
            "SELECT COALESCE(MAX(`order`), 0) + 1 as next_order 
             FROM {$this->table} 
             WHERE menu_group = ?",
            [$group]
        );
        return (int)($result['next_order'] ?? 1);
    }

    /**
     * Obtiene todas las páginas para selector
     */
    public function getAllPagesForSelect(int $userId): array
    {
        try {
            return $this->db->fetchAll(
                "SELECT id, title, slug, parent_id, status
                 FROM {$this->table}
                 WHERE user_id = ?
                 ORDER BY `order` ASC",
                [$userId]
            );
        } catch (Throwable $e) {
            error_log('[MenuService] Error en getAllPagesForSelect: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Obtiene una página por ID
     */
    public function getPageById(int $id): ?array
    {
        try {
            return $this->db->fetch(
                "SELECT * FROM {$this->table} WHERE id = ?",
                [$id]
            );
        } catch (Throwable $e) {
            error_log('[MenuService] Error en getPageById: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Crea una nueva página de menú
     */
    public function createPage(int $userId, array $data): array
    {
        try {
            // Validar datos
            if (empty($data['title'])) {
                throw new \Exception('El título es obligatorio');
            }

            // Generar slug si no se proporciona
            if (empty($data['slug']) && !empty($data['title'])) {
                $data['slug'] = $this->slugify($data['title']);
            }

            // Verificar slug único
            if (!empty($data['slug']) && $this->slugExists($data['slug'], $data['menu_group'] ?? 'main')) {
                $data['slug'] = $data['slug'] . '-' . time();
            }

            // Determinar nivel
            $level = 0;
            if (!empty($data['parent_id'])) {
                $parent = $this->getPageById((int)$data['parent_id']);
                if ($parent) {
                    $level = (int)$parent['level'] + 1;
                }
            }

            // Insertar
            $id = $this->db->insert(
                "INSERT INTO {$this->table} (
                    user_id, title, slug, url, icon, page_id,
                    menu_group, parent_id, level, `order`, 
                    is_published, status, target
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
                [
                    $userId,
                    $data['title'],
                    $data['slug'] ?? null,
                    $data['url'] ?? null,
                    $data['icon'] ?? null,
                    $data['page_id'] ?? null,
                    $data['menu_group'] ?? 'main',
                    $data['parent_id'] ?? null,
                    $level,
                    $data['order'] ?? $this->getNextOrder($data['menu_group'] ?? 'main'),
                    $data['is_published'] ?? 1,
                    $data['status'] ?? 'published',
                    $data['target'] ?? '_self'
                ]
            );

            $this->logger->logCurrentUser(
                'menu_page_created',
                "Creó la página de menú: {$data['title']} (ID: {$id})"
            );

            return [
                'success' => true,
                'id' => $id,
                'message' => 'Página de menú creada correctamente'
            ];
        } catch (Throwable $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Actualiza una página de menú
     */
    public function updatePage(int $id, int $userId, array $data): array
    {
        try {
            $page = $this->getPageById($id);
            if (!$page) {
                throw new \Exception('Página no encontrada');
            }

            if ((int)$page['user_id'] !== $userId) {
                throw new \Exception('No tienes permisos para editar esta página');
            }

            // Determinar nivel si cambia el parent_id
            if (isset($data['parent_id']) && (int)$data['parent_id'] !== (int)$page['parent_id']) {
                $level = 0;
                if (!empty($data['parent_id'])) {
                    $parent = $this->getPageById((int)$data['parent_id']);
                    if ($parent) {
                        $level = (int)$parent['level'] + 1;
                    }
                }
                $data['level'] = $level;
            }

            $fields = [];
            $params = [];

            $allowedFields = [
                'title', 'slug', 'url', 'icon', 'page_id', 'menu_group',
                'parent_id', 'level', 'order', 'is_published', 'status', 'target'
            ];

            foreach ($allowedFields as $field) {
                if (array_key_exists($field, $data) && $data[$field] !== null) {
                    $fields[] = "$field = ?";
                    $params[] = $data[$field];
                }
            }

            if (empty($fields)) {
                throw new \Exception('No hay campos para actualizar');
            }

            $params[] = $id;
            $params[] = $userId;

            $this->db->execute(
                "UPDATE {$this->table} SET " . implode(', ', $fields) . " WHERE id = ? AND user_id = ?",
                $params
            );

            $this->logger->logCurrentUser(
                'menu_page_updated',
                "Actualizó la página de menú ID: {$id}"
            );

            return [
                'success' => true,
                'message' => 'Página de menú actualizada correctamente'
            ];
        } catch (Throwable $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Elimina una página de menú
     */
    public function deletePage(int $id, int $userId): array
    {
        try {
            $page = $this->getPageById($id);
            if (!$page) {
                throw new \Exception('Página no encontrada');
            }

            if ((int)$page['user_id'] !== $userId) {
                throw new \Exception('No tienes permisos para eliminar esta página');
            }

            // Verificar si tiene hijos
            $children = $this->db->fetchAll(
                "SELECT id, title FROM {$this->table} WHERE parent_id = ?",
                [$id]
            );

            if (!empty($children)) {
                return [
                    'success' => false,
                    'error' => 'No se puede eliminar porque tiene submenús',
                    'children' => $children
                ];
            }

            $this->db->execute(
                "DELETE FROM {$this->table} WHERE id = ? AND user_id = ?",
                [$id, $userId]
            );

            $this->logger->logCurrentUser(
                'menu_page_deleted',
                "Eliminó la página de menú: {$page['title']} (ID: {$id})"
            );

            return [
                'success' => true,
                'message' => 'Página de menú eliminada correctamente'
            ];
        } catch (Throwable $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Duplica una página de menú
     */
    public function duplicatePage(int $id, int $userId): array
    {
        try {
            $page = $this->getPageById($id);
            if (!$page) {
                throw new \Exception('Página no encontrada');
            }

            $newData = [
                'user_id' => $userId,
                'title' => $page['title'] . ' (copia)',
                'slug' => $this->slugify($page['title'] . '-copia'),
                'url' => $page['url'],
                'icon' => $page['icon'],
                'page_id' => $page['page_id'],
                'menu_group' => $page['menu_group'],
                'parent_id' => $page['parent_id'],
                'is_published' => 0,
                'status' => 'draft',
                'target' => $page['target'],
                'order' => $this->getNextOrder($page['menu_group']),
            ];

            return $this->createPage($userId, $newData);
        } catch (Throwable $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Actualiza el orden de los items
     */
    public function updateOrder(array $items, int $userId): array
    {
        try {
            $this->db->beginTransaction();

            foreach ($items as $index => $item) {
                $id = (int)($item['id'] ?? 0);
                $order = (int)($item['order'] ?? $index);
                $parentId = isset($item['parent_id']) && $item['parent_id'] !== '' 
                    ? (int)$item['parent_id'] 
                    : null;

                // Actualizar nivel si cambia el parent
                $level = 0;
                if ($parentId !== null) {
                    $parent = $this->getPageById($parentId);
                    if ($parent) {
                        $level = (int)$parent['level'] + 1;
                    }
                }

                $this->db->execute(
                    "UPDATE {$this->table} 
                     SET `order` = ?, parent_id = ?, level = ? 
                     WHERE id = ? AND user_id = ?",
                    [$order, $parentId, $level, $id, $userId]
                );
            }

            $this->db->commit();

            return [
                'success' => true,
                'message' => 'Orden actualizado correctamente'
            ];
        } catch (Throwable $e) {
            $this->db->rollBack();
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Obtiene el breadcrumb de una página
     */
    public function getBreadcrumb(int $pageId): array
    {
        $breadcrumb = [];
        $currentId = $pageId;

        while ($currentId) {
            $page = $this->db->fetch(
                "SELECT id, title, slug, url, parent_id FROM {$this->table} 
                 WHERE id = ? AND is_published = 1 AND status = 'published'",
                [$currentId]
            );

            if ($page) {
                array_unshift($breadcrumb, $page);
                $currentId = (int)$page['parent_id'];
            } else {
                break;
            }
        }

        return $breadcrumb;
    }

    /**
     * Obtiene estadísticas del menú
     */
    public function getStats(int $userId): array
    {
        return $this->db->fetch(
            "SELECT 
                COUNT(*) as total,
                COUNT(CASE WHEN is_published = 1 THEN 1 END) as published,
                COUNT(CASE WHEN is_published = 0 THEN 1 END) as draft,
                COUNT(DISTINCT menu_group) as groups
             FROM {$this->table}
             WHERE user_id = ?",
            [$userId]
        );
    }

    /**
     * Verifica si un slug existe
     */
    private function slugExists(string $slug, string $group): bool
    {
        $result = $this->db->fetch(
            "SELECT COUNT(*) as count FROM {$this->table} 
             WHERE slug = ? AND menu_group = ?",
            [$slug, $group]
        );
        return ($result['count'] ?? 0) > 0;
    }

    /**
     * Convierte texto a slug
     */
    private function slugify(string $text): string
    {
        $text = preg_replace('~[^\pL\d]+~u', '-', $text);
        $text = iconv('utf-8', 'us-ascii//TRANSLIT', $text);
        $text = preg_replace('~[^-\w]+~', '', $text);
        $text = trim($text, '-');
        $text = strtolower($text);
        return $text ?: 'n-a';
    }
}
<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\{Database, Auth};
use Throwable;

/**
 * Servicio del sistema de menús.
 *
 * Modelo (ver 02-DOCS/wiki/ftd/menus-diagnostico-y-diseno.md):
 *   - `menus`     catálogo de menús. `is_default` = menú automático de una
 *                 ubicación; `is_primary` = menú que sustituye al predeterminado
 *                 en su ubicación.
 *   - `page_menu` ítems. Cada fila pertenece a un menú (`menu_id`), con su
 *                 PROPIO `parent_id` y `order`: la jerarquía del menú es
 *                 independiente de la jerarquía de páginas (`pages.parent_id`).
 *   - `pages.menu_id` menú asignado a una página concreta (prioridad máxima al
 *                 elegir qué menú mostrar).
 *
 * Antes de esto el servicio consultaba la tabla `menus` como si fuera la de
 * ítems (columnas title/order/page_id/menu_group que no existen), así que todo
 * el módulo de administración fallaba en silencio.
 *
 * @package App\Services
 */
class MenuService
{
    /** Tabla del catálogo de menús. */
    private string $menusTable = 'menus';

    /** Tabla de ítems de menú. */
    private string $itemsTable = 'page_menu';

    /** Tabla de páginas. */
    private string $pagesTable = 'pages';

    /** Ubicaciones reconocidas (el render público usa 'header'). */
    public const LOCATIONS = ['header', 'footer', 'sidebar'];

    public function __construct(
        private readonly Database $db,
        private readonly ActivityLoggerService $logger
    ) {
    }

    // ═══════════════════════════════════════════════════════════════
    //  CATÁLOGO DE MENÚS
    // ═══════════════════════════════════════════════════════════════

    /**
     * Lista los menús con su número de ítems.
     *
     * @return array<int, array<string, mixed>>
     */
    public function listMenus(): array
    {
        try {
            return $this->db->fetchAll(
                "SELECT m.*,
                        (SELECT COUNT(*) FROM {$this->itemsTable} pm WHERE pm.menu_id = m.id) AS item_count
                 FROM {$this->menusTable} m
                 ORDER BY m.is_default DESC, m.location ASC, m.name ASC"
            );
        } catch (Throwable $e) {
            error_log('[MenuService] Error en listMenus: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * @return array<string, mixed>|null
     */
    public function findMenu(int $id): ?array
    {
        return $this->db->fetch("SELECT * FROM {$this->menusTable} WHERE id = ?", [$id]);
    }

    /**
     * @return array<string, mixed>|null
     */
    public function findMenuBySlug(string $slug): ?array
    {
        return $this->db->fetch("SELECT * FROM {$this->menusTable} WHERE slug = ?", [$slug]);
    }

    /**
     * Menú automático (sincronizado con las páginas publicadas) de una ubicación.
     *
     * @return array<string, mixed>|null
     */
    public function defaultMenu(string $location = 'header'): ?array
    {
        return $this->db->fetch(
            "SELECT * FROM {$this->menusTable}
             WHERE is_default = 1 AND location = ? AND is_active = 1
             ORDER BY id ASC LIMIT 1",
            [$location]
        );
    }

    /**
     * Menú personalizado marcado para sustituir al predeterminado en una ubicación.
     *
     * @return array<string, mixed>|null
     */
    public function primaryMenu(string $location = 'header'): ?array
    {
        return $this->db->fetch(
            "SELECT * FROM {$this->menusTable}
             WHERE is_primary = 1 AND is_default = 0 AND location = ? AND is_active = 1
             ORDER BY id ASC LIMIT 1",
            [$location]
        );
    }

    /**
     * Crea un menú.
     *
     * @param array{name?:string,slug?:string,location?:string,is_active?:int,is_primary?:int} $data
     * @return array{success:bool,id?:int,message?:string,error?:string}
     */
    public function createMenu(array $data): array
    {
        try {
            $name = trim((string) ($data['name'] ?? ''));
            if ($name === '') {
                throw new \InvalidArgumentException('El nombre del menú es obligatorio.');
            }

            $slug = $this->slugify((string) ($data['slug'] ?? $name));
            if ($this->findMenuBySlug($slug)) {
                $slug .= '-' . substr(bin2hex(random_bytes(3)), 0, 5);
            }

            $location = $this->normalizeLocation((string) ($data['location'] ?? 'header'));

            $id = $this->db->insert(
                "INSERT INTO {$this->menusTable} (name, slug, location, is_active, is_default, is_primary)
                 VALUES (?, ?, ?, ?, 0, 0)",
                [$name, $slug, $location, (int) ($data['is_active'] ?? 1)]
            );

            $this->logger->logCurrentUser('menu_created', "Menú creado: {$name} (ID {$id})");

            return ['success' => true, 'id' => $id, 'message' => 'Menú creado.'];
        } catch (Throwable $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Actualiza los datos del menú (no de sus ítems).
     *
     * @return array{success:bool,message?:string,error?:string}
     */
    public function updateMenu(int $id, array $data): array
    {
        try {
            $menu = $this->findMenu($id);
            if (!$menu) {
                throw new \RuntimeException('Menú no encontrado.');
            }

            $fields = [];
            $params = [];

            if (array_key_exists('name', $data)) {
                $name = trim((string) $data['name']);
                if ($name === '') {
                    throw new \InvalidArgumentException('El nombre del menú es obligatorio.');
                }
                $fields[] = 'name = ?';
                $params[] = $name;
            }
            if (array_key_exists('slug', $data)) {
                $slug = $this->slugify((string) $data['slug']);
                if ($slug === '') {
                    throw new \InvalidArgumentException('El slug del menú no es válido.');
                }
                $other = $this->findMenuBySlug($slug);
                if ($other && (int) $other['id'] !== $id) {
                    throw new \InvalidArgumentException('Ya existe otro menú con ese slug.');
                }
                $fields[] = 'slug = ?';
                $params[] = $slug;
            }
            if (array_key_exists('location', $data)) {
                $newLocation = $this->normalizeLocation((string) $data['location']);
                $fields[] = 'location = ?';
                $params[] = $newLocation;
            }
            if (array_key_exists('is_active', $data)) {
                if ((int) $menu['is_default'] === 1 && (int) $data['is_active'] === 0) {
                    throw new \InvalidArgumentException('El menú automático no se puede desactivar; usa otro menú como principal.');
                }
                $fields[] = 'is_active = ?';
                $params[] = (int) $data['is_active'];
            }

            // Sustituir al predeterminado en su ubicación.
            if (array_key_exists('is_primary', $data)) {
                $wantPrimary = (int) $data['is_primary'] === 1;
                if ($wantPrimary && (int) $menu['is_default'] === 1) {
                    throw new \InvalidArgumentException('El menú automático no puede ser además el que sustituye al principal.');
                }
                if ($wantPrimary) {
                    $this->clearPrimary((string) ($data['location'] ?? $menu['location']));
                }
                $fields[] = 'is_primary = ?';
                $params[] = $wantPrimary ? 1 : 0;
            }

            if ($fields === []) {
                throw new \InvalidArgumentException('No hay cambios que guardar.');
            }

            $params[] = $id;
            $this->db->execute(
                "UPDATE {$this->menusTable} SET " . implode(', ', $fields) . ' WHERE id = ?',
                $params
            );

            // Si cambia la ubicación de un menú principal, no puede quedar otro
            // principal en la ubicación nueva.
            if ((int) ($menu['is_primary'] ?? 0) === 1
                && isset($newLocation)
                && $newLocation !== (string) $menu['location']) {
                $this->clearPrimary($newLocation, $id);
            }

            $this->logger->logCurrentUser('menu_updated', "Menú actualizado (ID {$id})");

            return ['success' => true, 'message' => 'Menú actualizado.'];
        } catch (Throwable $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Elimina un menú y sus ítems (la FK es ON DELETE CASCADE).
     *
     * @return array{success:bool,message?:string,error?:string}
     */
    public function deleteMenu(int $id): array
    {
        try {
            $menu = $this->findMenu($id);
            if (!$menu) {
                throw new \RuntimeException('Menú no encontrado.');
            }
            if ((int) $menu['is_default'] === 1) {
                throw new \InvalidArgumentException('El menú automático no se puede eliminar.');
            }

            $this->db->execute("DELETE FROM {$this->menusTable} WHERE id = ?", [$id]);
            $this->logger->logCurrentUser('menu_deleted', "Menú eliminado: {$menu['name']} (ID {$id})");

            return ['success' => true, 'message' => 'Menú eliminado (sus páginas siguen intactas).'];
        } catch (Throwable $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Activa o desactiva un menú.
     *
     * @return array{success:bool,message?:string,error?:string}
     */
    public function toggleMenu(int $id): array
    {
        $menu = $this->findMenu($id);
        if (!$menu) {
            return ['success' => false, 'error' => 'Menú no encontrado.'];
        }
        if ((int) $menu['is_default'] === 1) {
            return ['success' => false, 'error' => 'El menú automático siempre está activo.'];
        }

        $this->db->execute(
            "UPDATE {$this->menusTable} SET is_active = ? WHERE id = ?",
            [(int) $menu['is_active'] === 1 ? 0 : 1, $id]
        );

        return ['success' => true, 'message' => 'Estado del menú actualizado.'];
    }

    /**
     * Marca un menú como el automático de su ubicación (y desmarca el resto).
     *
     * @return array{success:bool,message?:string,error?:string}
     */
    public function setDefault(int $id): array
    {
        try {
            $menu = $this->findMenu($id);
            if (!$menu) {
                throw new \RuntimeException('Menú no encontrado.');
            }

            Database::begin();
            $this->db->execute(
                "UPDATE {$this->menusTable} SET is_default = 0, is_primary = 0 WHERE location = ?",
                [$menu['location']]
            );
            $this->db->execute(
                "UPDATE {$this->menusTable} SET is_default = 1, is_active = 1 WHERE id = ?",
                [$id]
            );
            Database::commit();

            $this->logger->logCurrentUser('menu_set_default', "Menú predeterminado: {$menu['name']}");

            return ['success' => true, 'message' => 'Menú automático actualizado. Pulsa «Sincronizar» para regenerarlo.'];
        } catch (Throwable $e) {
            Database::rollback();
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Quita la marca de principal a todos los menús de una ubicación.
     */
    private function clearPrimary(string $location, ?int $exceptId = null): void
    {
        $sql = "UPDATE {$this->menusTable} SET is_primary = 0 WHERE location = ?";
        $params = [$location];
        if ($exceptId !== null) {
            $sql .= ' AND id <> ?';
            $params[] = $exceptId;
        }
        $this->db->execute($sql, $params);
    }

    // ═══════════════════════════════════════════════════════════════
    //  ÍTEMS
    // ═══════════════════════════════════════════════════════════════

    /**
     * Ítems de un menú en plano, ordenados.
     *
     * Con $onlyVisible se aplican las reglas de publicación: el ítem debe estar
     * publicado y, si apunta a una página, esa página debe estar publicada.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getItems(int $menuId, bool $onlyVisible = false): array
    {
        try {
            if ($onlyVisible) {
                return $this->db->fetchAll(
                    "SELECT pm.*
                     FROM {$this->itemsTable} pm
                     LEFT JOIN {$this->pagesTable} p ON p.id = pm.page_id
                     WHERE pm.menu_id = ?
                       AND pm.status = 'published' AND pm.is_published = 1
                       AND (pm.page_id IS NULL
                            OR (p.id IS NOT NULL AND p.status = 'published' AND p.is_published = 1))
                     ORDER BY pm.`order` ASC, pm.id ASC",
                    [$menuId]
                );
            }

            return $this->db->fetchAll(
                "SELECT pm.*, p.status AS page_status, p.is_published AS page_published
                 FROM {$this->itemsTable} pm
                 LEFT JOIN {$this->pagesTable} p ON p.id = pm.page_id
                 WHERE pm.menu_id = ?
                 ORDER BY pm.parent_id IS NULL DESC, pm.`order` ASC, pm.id ASC",
                [$menuId]
            );
        } catch (Throwable $e) {
            error_log('[MenuService] Error en getItems: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Árbol de ítems del menú.
     *
     * Un ítem cuyo padre no está en el conjunto visible sube al primer nivel: es
     * preferible a que el enlace desaparezca sin explicación.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getTree(int $menuId, bool $onlyVisible = false): array
    {
        $items = $this->getItems($menuId, $onlyVisible);
        if (!$items) {
            return [];
        }

        $present = [];
        foreach ($items as $item) {
            $present[(int) $item['id']] = true;
        }

        foreach ($items as &$item) {
            $parent = $item['parent_id'] !== null ? (int) $item['parent_id'] : 0;
            if ($parent !== 0 && !isset($present[$parent])) {
                $item['parent_id'] = null;
            }
        }
        unset($item);

        return $this->buildNavigationTree($items);
    }

    /**
     * Construye el árbol por `parent_id`.
     *
     * @param array<int, array<string, mixed>> $items
     * @return array<int, array<string, mixed>>
     */
    public function buildNavigationTree(array $items, ?int $parentId = null): array
    {
        $branch = [];
        foreach ($items as $item) {
            if ((int) ($item['parent_id'] ?? 0) === (int) $parentId) {
                $children = $this->buildNavigationTree($items, (int) $item['id']);
                if ($children !== []) {
                    $item['children'] = $children;
                }
                $branch[] = $item;
            }
        }
        return $branch;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function findItem(int $menuId, int $itemId): ?array
    {
        return $this->db->fetch(
            "SELECT * FROM {$this->itemsTable} WHERE id = ? AND menu_id = ?",
            [$itemId, $menuId]
        );
    }

    /**
     * Añade un ítem a un menú.
     *
     * @param array{page_id?:int|string|null,title?:string,slug?:string,url?:string,icon?:string,
     *              parent_id?:int|string|null,order?:int|string|null,status?:string,
     *              is_published?:int|string,target?:string} $data
     * @return array{success:bool,id?:int,message?:string,error?:string}
     */
    public function addItem(int $menuId, array $data): array
    {
        try {
            $menu = $this->findMenu($menuId);
            if (!$menu) {
                throw new \RuntimeException('Menú no encontrado.');
            }
            if ((int) $menu['is_default'] === 1) {
                throw new \InvalidArgumentException(
                    'El menú automático se genera solo desde las páginas publicadas. Añade ítems a un menú personalizado.'
                );
            }

            $pageId = $this->normalizeId($data['page_id'] ?? null);
            $page = $pageId !== null ? $this->findPage($pageId) : null;
            if ($pageId !== null && $page === null) {
                throw new \InvalidArgumentException('La página seleccionada no existe.');
            }

            $title = trim((string) ($data['title'] ?? ''));
            if ($title === '' && $page !== null) {
                $title = (string) $page['title'];
            }
            if ($title === '') {
                throw new \InvalidArgumentException('Indica un título o selecciona una página.');
            }

            $url = trim((string) ($data['url'] ?? ''));
            if ($url !== '' && !preg_match('~^(https?://|/|#|mailto:|tel:)~i', $url)) {
                throw new \InvalidArgumentException('La URL debe empezar por /, http(s)://, #, mailto: o tel:.');
            }

            $parentId = $this->normalizeId($data['parent_id'] ?? null);
            if ($parentId !== null) {
                $parentItem = $this->findItem($menuId, $parentId);
                if (!$parentItem) {
                    throw new \InvalidArgumentException('El elemento padre no pertenece a este menú.');
                }
            }

            if ($pageId !== null && $this->pageAlreadyInMenu($menuId, $pageId)) {
                throw new \InvalidArgumentException('Esa página ya está en este menú.');
            }

            $slug = $this->slugify((string) ($data['slug'] ?? ''));
            if ($slug === '') {
                $slug = $page !== null ? $this->slugify((string) $page['slug']) : $this->slugify($title);
            }
            $slug = $this->uniqueItemSlug($menuId, $slug);

            $order = $this->normalizeId($data['order'] ?? null) ?? $this->nextOrder($menuId, $parentId);
            $statusInput = (string) ($data['status'] ?? 'published');
            $status = in_array($statusInput, ['draft', 'published', 'archived'], true) ? $statusInput : 'published';
            $isPublished = array_key_exists('is_published', $data) ? (int) $data['is_published'] : 1;
            if ($status !== 'published') {
                $isPublished = 0;
            }
            $targetInput = (string) ($data['target'] ?? '_self');
            $target = in_array($targetInput, ['_self', '_blank', '_parent'], true) ? $targetInput : '_self';

            $id = $this->db->insert(
                "INSERT INTO {$this->itemsTable}
                    (menu_id, page_id, title, slug, url, icon, `order`, sort, parent_id, level,
                     is_published, status, target)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
                [
                    $menuId,
                    $pageId,
                    $title,
                    $slug,
                    $url !== '' ? $url : null,
                    trim((string) ($data['icon'] ?? '')) ?: null,
                    $order,
                    $order,
                    $parentId,
                    $this->itemLevel($menuId, $parentId),
                    $isPublished,
                    $status,
                    $target,
                ]
            );

            $this->logger->logCurrentUser('menu_item_created', "Ítem añadido a «{$menu['name']}»: {$title} (ID {$id})");

            return ['success' => true, 'id' => $id, 'message' => 'Elemento añadido al menú.'];
        } catch (Throwable $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Actualiza un ítem (título, página, url, padre, orden, estado…).
     *
     * @return array{success:bool,message?:string,error?:string}
     */
    public function updateItem(int $menuId, int $itemId, array $data): array
    {
        try {
            $item = $this->findItem($menuId, $itemId);
            if (!$item) {
                throw new \RuntimeException('Elemento no encontrado en este menú.');
            }

            $fields = [];
            $params = [];

            if (array_key_exists('title', $data)) {
                $title = trim((string) $data['title']);
                if ($title === '') {
                    throw new \InvalidArgumentException('El título no puede quedar vacío.');
                }
                $fields[] = 'title = ?';
                $params[] = $title;
            }

            if (array_key_exists('url', $data)) {
                $url = trim((string) $data['url']);
                if ($url !== '' && !preg_match('~^(https?://|/|#|mailto:|tel:)~i', $url)) {
                    throw new \InvalidArgumentException('La URL debe empezar por /, http(s)://, #, mailto: o tel:.');
                }
                $fields[] = 'url = ?';
                $params[] = $url !== '' ? $url : null;
            }

            if (array_key_exists('page_id', $data)) {
                $pageId = $this->normalizeId($data['page_id']);
                if ($pageId !== null) {
                    if ($this->findPage($pageId) === null) {
                        throw new \InvalidArgumentException('La página seleccionada no existe.');
                    }
                    if ($this->pageAlreadyInMenu($menuId, $pageId, $itemId)) {
                        throw new \InvalidArgumentException('Esa página ya está en este menú.');
                    }
                }
                $fields[] = 'page_id = ?';
                $params[] = $pageId;
            }

            if (array_key_exists('parent_id', $data)) {
                $parentId = $this->normalizeId($data['parent_id']);
                if ($parentId !== null) {
                    if ($this->findItem($menuId, $parentId) === null) {
                        throw new \InvalidArgumentException('El elemento padre no pertenece a este menú.');
                    }
                    if ($parentId === $itemId || $this->isDescendant($menuId, $parentId, $itemId)) {
                        throw new \InvalidArgumentException('Un elemento no puede colgar de sí mismo ni de sus hijos.');
                    }
                }
                $fields[] = 'parent_id = ?';
                $params[] = $parentId;
                $fields[] = 'level = ?';
                $params[] = $this->itemLevel($menuId, $parentId);
            }

            if (array_key_exists('order', $data)) {
                $order = (int) $data['order'];
                $fields[] = '`order` = ?';
                $params[] = $order;
                $fields[] = 'sort = ?';
                $params[] = $order;
            }

            if (array_key_exists('status', $data)) {
                $status = in_array($data['status'], ['draft', 'published', 'archived'], true)
                    ? (string) $data['status'] : 'draft';
                $fields[] = 'status = ?';
                $params[] = $status;
                $fields[] = 'is_published = ?';
                $params[] = $status === 'published' ? 1 : 0;
            } elseif (array_key_exists('is_published', $data)) {
                $fields[] = 'is_published = ?';
                $params[] = (int) $data['is_published'];
            }

            if (array_key_exists('target', $data)) {
                $fields[] = 'target = ?';
                $params[] = in_array($data['target'], ['_self', '_blank', '_parent'], true)
                    ? (string) $data['target'] : '_self';
            }

            if (array_key_exists('icon', $data)) {
                $fields[] = 'icon = ?';
                $params[] = trim((string) $data['icon']) ?: null;
            }

            if ($fields === []) {
                throw new \InvalidArgumentException('No hay cambios que guardar.');
            }

            $params[] = $itemId;
            $params[] = $menuId;
            $this->db->execute(
                "UPDATE {$this->itemsTable} SET " . implode(', ', $fields) . ' WHERE id = ? AND menu_id = ?',
                $params
            );

            $this->logger->logCurrentUser('menu_item_updated', "Ítem de menú actualizado (ID {$itemId})");

            return ['success' => true, 'message' => 'Elemento actualizado.'];
        } catch (Throwable $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Elimina un ítem y su rama (FK ON DELETE CASCADE sobre parent_id).
     *
     * @return array{success:bool,message?:string,error?:string}
     */
    public function deleteItem(int $menuId, int $itemId): array
    {
        try {
            $item = $this->findItem($menuId, $itemId);
            if (!$item) {
                throw new \RuntimeException('Elemento no encontrado en este menú.');
            }

            $this->db->execute(
                "DELETE FROM {$this->itemsTable} WHERE id = ? AND menu_id = ?",
                [$itemId, $menuId]
            );

            $this->logger->logCurrentUser('menu_item_deleted', "Ítem de menú eliminado: {$item['title']} (ID {$itemId})");

            return ['success' => true, 'message' => 'Elemento eliminado.'];
        } catch (Throwable $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Sube o baja un ítem entre sus hermanos (mismo padre).
     *
     * @return array{success:bool,message?:string,error?:string}
     */
    public function moveItem(int $menuId, int $itemId, string $direction): array
    {
        try {
            $item = $this->findItem($menuId, $itemId);
            if (!$item) {
                throw new \RuntimeException('Elemento no encontrado.');
            }

            $siblings = $this->db->fetchAll(
                "SELECT id, `order` FROM {$this->itemsTable}
                 WHERE menu_id = ? AND ((parent_id IS NULL AND ? IS NULL) OR parent_id = ?)
                 ORDER BY `order` ASC, id ASC",
                [$menuId, $item['parent_id'], $item['parent_id']]
            );

            $index = null;
            foreach ($siblings as $i => $row) {
                if ((int) $row['id'] === $itemId) {
                    $index = $i;
                    break;
                }
            }
            if ($index === null) {
                throw new \RuntimeException('No se pudo localizar el elemento entre sus hermanos.');
            }

            $swapWith = $direction === 'up' ? $index - 1 : $index + 1;
            if (!isset($siblings[$swapWith])) {
                return ['success' => true, 'message' => 'Ya está en el extremo.'];
            }

            Database::begin();
            $this->db->execute(
                "UPDATE {$this->itemsTable} SET `order` = ? , sort = ? WHERE id = ? AND menu_id = ?",
                [(int) $siblings[$swapWith]['order'], (int) $siblings[$swapWith]['order'], $itemId, $menuId]
            );
            $this->db->execute(
                "UPDATE {$this->itemsTable} SET `order` = ?, sort = ? WHERE id = ? AND menu_id = ?",
                [(int) $siblings[$index]['order'], (int) $siblings[$index]['order'], (int) $siblings[$swapWith]['id'], $menuId]
            );
            Database::commit();

            return ['success' => true, 'message' => 'Orden actualizado.'];
        } catch (Throwable $e) {
            Database::rollback();
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Reordena en bloque (arrastrar y soltar o edición masiva).
     *
     * @param array<int, array{id:int|string,order:int|string,parent_id?:int|string|null}> $rows
     * @return array{success:bool,message?:string,error?:string}
     */
    public function reorderItems(int $menuId, array $rows): array
    {
        try {
            if ($rows === []) {
                throw new \InvalidArgumentException('No hay elementos que reordenar.');
            }

            Database::begin();
            foreach (array_values($rows) as $index => $row) {
                $itemId = (int) ($row['id'] ?? 0);
                $item = $this->findItem($menuId, $itemId);
                if (!$item) {
                    throw new \RuntimeException("El elemento {$itemId} no pertenece a este menú.");
                }

                $parentId = $this->normalizeId($row['parent_id'] ?? null);
                if ($parentId !== null) {
                    if ($parentId === $itemId || $this->isDescendant($menuId, $parentId, $itemId)) {
                        throw new \InvalidArgumentException('El orden propuesto crearía un ciclo.');
                    }
                    if ($this->findItem($menuId, $parentId) === null) {
                        throw new \InvalidArgumentException('El padre propuesto no pertenece a este menú.');
                    }
                }

                $order = (int) ($row['order'] ?? $index);
                $this->db->execute(
                    "UPDATE {$this->itemsTable}
                     SET `order` = ?, sort = ?, parent_id = ?, level = ?
                     WHERE id = ? AND menu_id = ?",
                    [$order, $order, $parentId, $this->itemLevel($menuId, $parentId), $itemId, $menuId]
                );
            }
            Database::commit();

            return ['success' => true, 'message' => 'Orden actualizado.'];
        } catch (Throwable $e) {
            Database::rollback();
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    // ═══════════════════════════════════════════════════════════════
    //  SINCRONIZACIÓN DEL MENÚ AUTOMÁTICO
    // ═══════════════════════════════════════════════════════════════

    /**
     * Sincroniza el menú automático con las páginas publicadas.
     *
     * Reglas:
     *  - Solo entran páginas con status 'published' e is_published = 1.
     *  - La jerarquía del menú copia `pages.parent_id`; si el padre no está
     *    publicado, la página queda en el primer nivel.
     *  - El orden es el de `pages.id` (las páginas no tienen columna de orden).
     *  - Los ítems de páginas despublicadas o borradas salen SOLO de este menú.
     *  - Es idempotente: nunca duplica.
     *
     * @return array{success:bool,default_menu_id?:int,added?:int,updated?:int,removed?:int,error?:string}
     */
    public function syncDefaultMenu(): array
    {
        try {
            Database::begin();

            $menu = $this->db->fetch(
                "SELECT * FROM {$this->menusTable} WHERE is_default = 1 ORDER BY id ASC LIMIT 1"
            );

            if (!$menu) {
                $id = $this->db->insert(
                    "INSERT INTO {$this->menusTable} (name, slug, location, is_active, is_default, is_primary)
                     VALUES ('Menú automático', 'default', 'header', 1, 1, 0)"
                );
                $menu = $this->findMenu($id);
            }

            $menuId = (int) $menu['id'];

            $pages = $this->db->fetchAll(
                "SELECT id, title, slug, parent_id
                 FROM {$this->pagesTable}
                 WHERE status = 'published' AND is_published = 1
                 ORDER BY id ASC"
            );

            $publishedIds = [];
            foreach ($pages as $page) {
                $publishedIds[(int) $page['id']] = true;
            }

            // 1) Fuera los ítems cuya página ya no está publicada o no existe.
            $existing = $this->db->fetchAll(
                "SELECT id, page_id FROM {$this->itemsTable} WHERE menu_id = ?",
                [$menuId]
            );

            $removed = 0;
            foreach ($existing as $row) {
                $pageId = $row['page_id'] !== null ? (int) $row['page_id'] : 0;
                if ($pageId === 0 || !isset($publishedIds[$pageId])) {
                    $this->db->execute(
                        "DELETE FROM {$this->itemsTable} WHERE id = ? AND menu_id = ?",
                        [(int) $row['id'], $menuId]
                    );
                    $removed++;
                }
            }

            // El mapa se relee DESPUÉS de borrar: la FK de parent_id es ON DELETE
            // CASCADE, así que borrar el ítem de una página oculta puede haberse
            // llevado por delante el de su hija. Con el mapa viejo se intentaba
            // actualizar filas ya inexistentes y la nieta nunca volvía al menú.
            $itemByPage = [];
            foreach ($this->db->fetchAll(
                "SELECT id, page_id FROM {$this->itemsTable} WHERE menu_id = ? AND page_id IS NOT NULL",
                [$menuId]
            ) as $row) {
                $itemByPage[(int) $row['page_id']] = (int) $row['id'];
            }

            // 2) Alta y actualización, en orden de página.
            $added = 0;
            $updated = 0;
            $order = 0;
            foreach ($pages as $page) {
                $pageId = (int) $page['id'];
                $parentPageId = $page['parent_id'] !== null ? (int) $page['parent_id'] : 0;
                // Si el padre no está publicado, la página sube al primer nivel.
                $parentItemId = ($parentPageId !== 0 && isset($itemByPage[$parentPageId]))
                    ? $itemByPage[$parentPageId] : null;
                $level = $parentItemId !== null ? 1 : 0;

                if (isset($itemByPage[$pageId])) {
                    $this->db->execute(
                        "UPDATE {$this->itemsTable}
                         SET title = ?, slug = ?, `order` = ?, sort = ?, parent_id = ?, level = ?,
                             is_published = 1, status = 'published'
                         WHERE id = ? AND menu_id = ?",
                        [
                            (string) $page['title'],
                            (string) $page['slug'],
                            $order,
                            $order,
                            $parentItemId,
                            $level,
                            $itemByPage[$pageId],
                            $menuId,
                        ]
                    );
                    $updated++;
                } else {
                    $this->db->insert(
                        "INSERT INTO {$this->itemsTable}
                            (menu_id, page_id, title, slug, url, `order`, sort, parent_id, level,
                             is_published, status, target)
                         VALUES (?, ?, ?, ?, NULL, ?, ?, ?, ?, 1, 'published', '_self')",
                        [
                            $menuId,
                            $pageId,
                            (string) $page['title'],
                            (string) $page['slug'],
                            $order,
                            $order,
                            $parentItemId,
                            $level,
                        ]
                    );
                    $added++;
                }
                $order++;
            }

            // 3) Niveles definitivos (por si cambió la jerarquía de páginas).
            $this->recalculateLevels($menuId);

            Database::commit();

            $this->logger->logCurrentUser(
                'menu_synced',
                "Menú automático sincronizado: +{$added} ~{$updated} -{$removed}"
            );

            return [
                'success' => true,
                'default_menu_id' => $menuId,
                'added' => $added,
                'updated' => $updated,
                'removed' => $removed,
            ];
        } catch (Throwable $e) {
            Database::rollback();
            error_log('[MenuService] Error en syncDefaultMenu: ' . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Recalcula `level` de todos los ítems de un menú a partir de parent_id.
     */
    private function recalculateLevels(int $menuId): void
    {
        $items = $this->db->fetchAll(
            "SELECT id, parent_id FROM {$this->itemsTable} WHERE menu_id = ?",
            [$menuId]
        );

        $parents = [];
        foreach ($items as $row) {
            $parents[(int) $row['id']] = $row['parent_id'] !== null ? (int) $row['parent_id'] : null;
        }

        foreach ($parents as $id => $parent) {
            $level = 0;
            $seen = [$id => true];
            while ($parent !== null && $level < 10) {
                if (isset($seen[$parent])) { // ciclo: se corta
                    $level = 0;
                    break;
                }
                $seen[$parent] = true;
                $level++;
                $parent = $parents[$parent] ?? null;
            }
            $this->db->execute(
                "UPDATE {$this->itemsTable} SET level = ? WHERE id = ? AND menu_id = ?",
                [$level, $id, $menuId]
            );
        }
    }

    // ═══════════════════════════════════════════════════════════════
    //  RESOLUCIÓN: QUÉ MENÚ SE MUESTRA
    // ═══════════════════════════════════════════════════════════════

    /**
     * Decide qué menú corresponde mostrar.
     *
     * Prioridad:
     *  1. Menú asignado a la página (`pages.menu_id`) si está activo.
     *  2. Menú personalizado marcado como principal para esa ubicación.
     *  3. Menú automático (predeterminado) de esa ubicación.
     *  4. Ninguno (menú vacío).
     *
     * @param array<string, mixed>|null $page
     * @return array<string, mixed>|null
     */
    public function resolveForPage(?array $page, string $location = 'header'): ?array
    {
        $override = $page['menu_id'] ?? null;
        if ($override !== null && $override !== '') {
            $menu = $this->findMenu((int) $override);
            if ($menu && (int) $menu['is_active'] === 1) {
                return $menu;
            }
        }

        $primary = $this->primaryMenu($location);
        if ($primary) {
            return $primary;
        }

        return $this->defaultMenu($location);
    }

    /**
     * Árbol de navegación listo para pintar: solo ítems visibles.
     *
     * @param array<string, mixed>|null $page
     * @return array<int, array<string, mixed>>
     */
    public function navigationFor(?array $page, string $location = 'header'): array
    {
        $menu = $this->resolveForPage($page, $location);
        if (!$menu) {
            return [];
        }
        return $this->getTree((int) $menu['id'], true);
    }

    /**
     * Árbol publicado de un menú por slug (API pública).
     *
     * @return array<int, array<string, mixed>>
     */
    public static function byGroup(string $slug): array
    {
        try {
            $db = new Database();
            $service = new self($db, new ActivityLoggerService($db));
            $menu = $service->findMenuBySlug($slug);
            if (!$menu || (int) $menu['is_active'] !== 1) {
                return [];
            }
            return $service->getTree((int) $menu['id'], true);
        } catch (Throwable $e) {
            error_log('[MenuService] Error en byGroup: ' . $e->getMessage());
            return [];
        }
    }

    // ═══════════════════════════════════════════════════════════════
    //  APOYO A LA INTERFAZ
    // ═══════════════════════════════════════════════════════════════

    /**
     * Páginas disponibles para elegir en un menú (todas, con su estado).
     *
     * @return array<int, array<string, mixed>>
     */
    public function getAllPagesForSelect(?int $userId = null): array
    {
        try {
            return $this->db->fetchAll(
                "SELECT id, title, slug, status, is_published, parent_id
                 FROM {$this->pagesTable}
                 ORDER BY title ASC"
            );
        } catch (Throwable $e) {
            error_log('[MenuService] Error en getAllPagesForSelect: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Migas de pan de una página (usa la jerarquía REAL de páginas).
     *
     * @return array<int, array<string, mixed>>
     */
    public function getBreadcrumb(int $pageId): array
    {
        $breadcrumb = [];
        $current = $pageId;
        $visited = [];

        while ($current !== 0 && !in_array($current, $visited, true)) {
            $visited[] = $current;
            $page = $this->findPage($current);
            if (!$page) {
                break;
            }
            array_unshift($breadcrumb, $page);
            $current = $page['parent_id'] !== null ? (int) $page['parent_id'] : 0;
        }

        return $breadcrumb;
    }

    /**
     * Estadísticas para el panel.
     *
     * @return array<string, int>
     */
    public function getStats(): array
    {
        try {
            $menus = (int) ($this->db->fetch("SELECT COUNT(*) c FROM {$this->menusTable}")['c'] ?? 0);
            $items = (int) ($this->db->fetch("SELECT COUNT(*) c FROM {$this->itemsTable}")['c'] ?? 0);
            $published = (int) ($this->db->fetch(
                "SELECT COUNT(*) c FROM {$this->itemsTable} WHERE is_published = 1 AND status = 'published'"
            )['c'] ?? 0);

            return [
                'menus' => $menus,
                'items' => $items,
                'published' => $published,
                'hidden' => $items - $published,
            ];
        } catch (Throwable $e) {
            return ['menus' => 0, 'items' => 0, 'published' => 0, 'hidden' => 0];
        }
    }

    /**
     * Deja la base coherente: un solo predeterminado y un solo principal por
     * ubicación, sin menús principales que sean además el automático, sin
     * padres de otro menú y sin ciclos.
     *
     * @return array{success:bool,changes:array<int,string>}
     */
    public function repair(): array
    {
        $changes = [];

        try {
            // Un único predeterminado por ubicación.
            $defaults = $this->db->fetchAll(
                "SELECT id, location FROM {$this->menusTable} WHERE is_default = 1 ORDER BY id ASC"
            );
            $seenLocations = [];
            foreach ($defaults as $row) {
                if (isset($seenLocations[$row['location']])) {
                    $this->db->execute("UPDATE {$this->menusTable} SET is_default = 0 WHERE id = ?", [(int) $row['id']]);
                    $changes[] = "Menú {$row['id']} dejó de ser predeterminado (duplicado en {$row['location']}).";
                } else {
                    $seenLocations[$row['location']] = true;
                }
            }

            // El automático no puede ser además el principal.
            $this->db->execute("UPDATE {$this->menusTable} SET is_primary = 0 WHERE is_default = 1 AND is_primary = 1");

            // Un único principal por ubicación.
            $primaries = $this->db->fetchAll(
                "SELECT id, location FROM {$this->menusTable} WHERE is_primary = 1 ORDER BY id ASC"
            );
            $seenPrimary = [];
            foreach ($primaries as $row) {
                if (isset($seenPrimary[$row['location']])) {
                    $this->db->execute("UPDATE {$this->menusTable} SET is_primary = 0 WHERE id = ?", [(int) $row['id']]);
                    $changes[] = "Menú {$row['id']} dejó de ser principal (duplicado en {$row['location']}).";
                } else {
                    $seenPrimary[$row['location']] = true;
                }
            }

            // Padres de otro menú → primer nivel; y niveles recalculados.
            $orphans = (int) ($this->db->fetch(
                "SELECT COUNT(*) c FROM {$this->itemsTable} i
                 JOIN {$this->itemsTable} p ON p.id = i.parent_id
                 WHERE p.menu_id <> i.menu_id"
            )['c'] ?? 0);
            if ($orphans > 0) {
                $this->db->execute(
                    "UPDATE {$this->itemsTable} i
                     JOIN {$this->itemsTable} p ON p.id = i.parent_id
                     SET i.parent_id = NULL, i.level = 0
                     WHERE p.menu_id <> i.menu_id"
                );
                $changes[] = "{$orphans} ítem(s) con padre de otro menú pasaron al primer nivel.";
            }

            foreach ($this->db->fetchAll("SELECT id FROM {$this->menusTable}") as $menu) {
                $this->recalculateLevels((int) $menu['id']);
            }

            return ['success' => true, 'changes' => $changes];
        } catch (Throwable $e) {
            return ['success' => false, 'changes' => [$e->getMessage()]];
        }
    }

    // ═══════════════════════════════════════════════════════════════
    //  HELPERS PRIVADOS
    // ═══════════════════════════════════════════════════════════════

    /**
     * @return array<string, mixed>|null
     */
    private function findPage(int $id): ?array
    {
        return $this->db->fetch(
            "SELECT id, title, slug, parent_id, status, is_published, menu_id
             FROM {$this->pagesTable} WHERE id = ?",
            [$id]
        );
    }

    private function pageAlreadyInMenu(int $menuId, int $pageId, ?int $ignoreItemId = null): bool
    {
        $sql = "SELECT COUNT(*) c FROM {$this->itemsTable} WHERE menu_id = ? AND page_id = ?";
        $params = [$menuId, $pageId];
        if ($ignoreItemId !== null) {
            $sql .= ' AND id <> ?';
            $params[] = $ignoreItemId;
        }
        return (int) ($this->db->fetch($sql, $params)['c'] ?? 0) > 0;
    }

    private function isDescendant(int $menuId, int $candidateParentId, int $itemId): bool
    {
        $current = $candidateParentId;
        $guard = 0;
        while ($current !== null && $guard < 50) {
            if ($current === $itemId) {
                return true;
            }
            $row = $this->findItem($menuId, $current);
            $current = $row && $row['parent_id'] !== null ? (int) $row['parent_id'] : null;
            $guard++;
        }
        return false;
    }

    private function itemLevel(int $menuId, ?int $parentId): int
    {
        $level = 0;
        $current = $parentId;
        $guard = 0;
        while ($current !== null && $guard < 50) {
            $row = $this->findItem($menuId, $current);
            if (!$row) {
                break;
            }
            $level++;
            $current = $row['parent_id'] !== null ? (int) $row['parent_id'] : null;
            $guard++;
        }
        return $level;
    }

    private function nextOrder(int $menuId, ?int $parentId): int
    {
        $row = $this->db->fetch(
            "SELECT COALESCE(MAX(`order`), -1) + 1 AS next FROM {$this->itemsTable}
             WHERE menu_id = ? AND ((parent_id IS NULL AND ? IS NULL) OR parent_id = ?)",
            [$menuId, $parentId, $parentId]
        );
        return (int) ($row['next'] ?? 1);
    }

    private function uniqueItemSlug(int $menuId, string $slug): string
    {
        $base = $slug;
        $suffix = 1;
        while ((int) ($this->db->fetch(
            "SELECT COUNT(*) c FROM {$this->itemsTable} WHERE menu_id = ? AND slug = ?",
            [$menuId, $slug]
        )['c'] ?? 0) > 0) {
            $slug = $base . '-' . $suffix;
            $suffix++;
            if ($suffix > 500) {
                $slug = $base . '-' . bin2hex(random_bytes(3));
                break;
            }
        }
        return $slug;
    }

    private function normalizeId(mixed $value): ?int
    {
        if ($value === null || $value === '' || $value === '0' || $value === 0) {
            return null;
        }
        return (int) $value;
    }

    private function normalizeLocation(string $location): string
    {
        $location = $this->slugify($location);
        return in_array($location, self::LOCATIONS, true) ? $location : 'header';
    }

    private function slugify(string $text): string
    {
        $text = preg_replace('~[^\pL\d]+~u', '-', $text) ?? '';
        $converted = @iconv('utf-8', 'us-ascii//TRANSLIT', $text);
        if ($converted !== false) {
            $text = $converted;
        }
        $text = preg_replace('~[^-\w]+~', '', $text) ?? '';
        return strtolower(trim($text, '-')) ?: '';
    }
}

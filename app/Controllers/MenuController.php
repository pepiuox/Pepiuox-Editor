<?php
namespace App\Controllers;

use App\Core\{Request, Response, View, Session, CSRF, Database, Auth};
use App\Services\{MenuService, CacheService, AuditService};
use Throwable;

/**
 * Controlador de gestión de menús
 * 
 * @package App\Controllers
 */
class MenuController
{
    private MenuService $menuService;

    public function __construct(MenuService $menuService)
    {
        $this->menuService = $menuService;
    }

    /**
     * Muestra la lista de páginas del menú con soporte para múltiples grupos
     */
    public function index(Request $request): void
    {
        if (!Auth::can('menu.view')) {
            Response::abort(403, 'No tienes permisos para ver el menú');
        }

        $userId = Auth::id();
        $group = $request->get('group', 'main');
        
        // Obtener menú en formato plano (para administración)
        $items = $this->menuService->getFlatMenu($group);
        $menuTree = $this->menuService->getAdminMenu($userId);
        
        // Obtener grupos disponibles
        $groups = Database::fetchAll("SELECT DISTINCT menu_group FROM menus ORDER BY menu_group");
        
        // Estadísticas del menú
        $stats = $this->menuService->getStats($userId);

        Response::render(View::render('admin.menu.index', [
            'items' => $items,
            'menuTree' => $menuTree,
            'group' => $group,
            'groups' => $groups,
            'stats' => $stats,
            'userId' => $userId,
        ]));
    }

    /**
     * Muestra el formulario para crear una nueva página de menú
     */
    public function create(Request $request): void
    {
        if (!Auth::can('menu.create')) {
            Response::abort(403, 'No tienes permisos para crear páginas de menú');
        }

        $userId = Auth::id();
        $group = $request->get('group', 'main');
        
        // Obtener páginas para selector
        $pages = $this->menuService->getAllPagesForSelect($userId);
        
        // Obtener items del menú para jerarquía
        $menuItems = $this->menuService->getFlatMenu($group);
        
        // Obtener grupos disponibles
        $groups = Database::fetchAll("SELECT DISTINCT menu_group FROM menus ORDER BY menu_group");

        Response::render(View::render('admin.menu.create', [
            'pages' => $pages,
            'menuItems' => $menuItems,
            'groups' => $groups,
            'group' => $group,
        ]));
    }

    /**
     * Guarda una nueva página de menú
     */
    public function store(Request $request): void
    {
        CSRF::guard();

        if (!Auth::can('menu.create')) {
            Response::abort(403, 'No tienes permisos para crear páginas de menú');
        }

        $title = $request->post('title');
        $menuGroup = $request->post('menu_group', 'main');

        if (empty($title)) {
            $this->handleError($request, 'El título es obligatorio', 'admin/menus/create?group=' . $menuGroup);
            return;
        }

        try {
            $data = [
                'title' => $title,
                'slug' => $request->post('slug') ?: null,
                'url' => $request->post('url'),
                'icon' => $request->post('icon'),
                'page_id' => $request->post('page_id') ?: null,
                'parent_id' => $request->post('parent_id') ?: null,
                'menu_group' => $menuGroup,
                'is_published' => (int)$request->post('is_published', 1),
                'status' => $request->post('status', 'published'),
                'target' => $request->post('target', '_self'),
                'order' => $request->post('order') ?: $this->menuService->getNextOrder($menuGroup),
            ];

            $result = $this->menuService->createPage(Auth::id(), $data);

            if ($result['success']) {
                $this->invalidateCacheAndLog(
                    'menu_item_created',
                    "Item de menú creado: {$title} (Grupo: {$menuGroup})"
                );

                $this->handleSuccess($request, $result['message'], 'admin/menus?group=' . $menuGroup, $result['id'] ?? null);
            } else {
                $this->handleError($request, $result['error'], 'admin/menus/create?group=' . $menuGroup);
            }
        } catch (Throwable $e) {
            $this->handleException($request, $e, 'crear el item de menú', 'admin/menus/create?group=' . $menuGroup);
        }
    }

    /**
     * Muestra el formulario para editar una página de menú
     */
    public function edit(Request $request, array $params): void
    {
        if (!Auth::can('menu.edit')) {
            Response::abort(403, 'No tienes permisos para editar páginas de menú');
        }

        $id = (int)$params['id'];
        $userId = Auth::id();

        $page = $this->menuService->getPageById($id);

        if (!$page) {
            Response::abort(404, 'Página no encontrada');
        }

        if ((int)$page['user_id'] !== $userId) {
            Response::abort(403, 'No tienes permisos para editar esta página');
        }

        // Obtener páginas para selector
        $pages = $this->menuService->getAllPagesForSelect($userId);
        
        // Obtener items del menú para jerarquía
        $menuItems = $this->menuService->getFlatMenu($page['menu_group'] ?? 'main');
        
        // Obtener grupos disponibles
        $groups = Database::fetchAll("SELECT DISTINCT menu_group FROM menus ORDER BY menu_group");

        Response::render(View::render('admin.menu.edit', [
            'page' => $page,
            'pages' => $pages,
            'menuItems' => $menuItems,
            'groups' => $groups,
            'group' => $page['menu_group'] ?? 'main',
        ]));
    }

    /**
     * Actualiza una página de menú
     */
    public function update(Request $request, array $params): void
    {
        CSRF::guard();

        if (!Auth::can('menu.edit')) {
            Response::abort(403, 'No tienes permisos para editar páginas de menú');
        }

        $id = (int)$params['id'];
        $menuGroup = $request->post('menu_group', 'main');

        try {
            $data = [
                'title' => $request->post('title'),
                'slug' => $request->post('slug'),
                'url' => $request->post('url'),
                'icon' => $request->post('icon'),
                'page_id' => $request->post('page_id') ?: null,
                'parent_id' => $request->post('parent_id') ?: null,
                'menu_group' => $menuGroup,
                'is_published' => (int)$request->post('is_published', 1),
                'status' => $request->post('status', 'published'),
                'target' => $request->post('target', '_self'),
                'order' => $request->post('order'),
            ];

            $result = $this->menuService->updatePage($id, Auth::id(), $data);

            if ($result['success']) {
                $this->invalidateCacheAndLog(
                    'menu_item_updated',
                    "Item de menú actualizado ID: {$id}"
                );

                $this->handleSuccess($request, $result['message'], 'admin/menus?group=' . $menuGroup);
            } else {
                $this->handleError($request, $result['error'], 'admin/menus/' . $id . '/edit?group=' . $menuGroup);
            }
        } catch (Throwable $e) {
            $this->handleException($request, $e, 'actualizar el item de menú', 'admin/menus/' . $id . '/edit?group=' . $menuGroup);
        }
    }

    /**
     * Elimina una página de menú
     */
    public function delete(Request $request, array $params): void
    {
        CSRF::guard();

        if (!Auth::can('menu.delete')) {
            Response::abort(403, 'No tienes permisos para eliminar páginas de menú');
        }

        $id = (int)$params['id'];

        try {
            $page = $this->menuService->getPageById($id);
            $result = $this->menuService->deletePage($id, Auth::id());

            if ($result['success']) {
                $this->invalidateCacheAndLog(
                    'menu_item_deleted',
                    "Item de menú eliminado: {$page['title']} (ID: {$id})"
                );

                if ($request->isAjax()) {
                    Response::json(['success' => true, 'message' => $result['message']]);
                } else {
                    Session::flash('flash.success', $result['message']);
                }
            } else {
                if ($request->isAjax()) {
                    Response::json(['success' => false, 'error' => $result['error']]);
                } else {
                    Session::flash('flash.error', $result['error']);
                }
            }
        } catch (Throwable $e) {
            $this->handleException($request, $e, 'eliminar el item de menú', null, false);
        }

        if (!$request->isAjax()) {
            Response::redirect(url('admin/menus'));
        }
    }

    /**
     * Actualiza el orden del menú (drag & drop)
     */
    public function reorder(Request $request): void
    {
        CSRF::guard();

        if (!Auth::can('menu.edit')) {
            Response::abort(403, 'No tienes permisos para reordenar el menú');
        }

        // Soporte tanto para JSON como para POST estándar
        $items = $request->post('items');
        if (is_string($items)) {
            $items = json_decode($items, true);
        }

        if (!$items || !is_array($items)) {
            Response::json(['success' => false, 'error' => 'Datos inválidos']);
            return;
        }

        try {
            $result = $this->menuService->updateOrder($items, Auth::id());

            if ($result['success']) {
                $this->invalidateCacheAndLog(
                    'menu_reordered',
                    'Menú reordenado'
                );
            }

            Response::json($result);
        } catch (Throwable $e) {
            error_log('[MenuController] Error al reordenar: ' . $e->getMessage());
            Response::json(['success' => false, 'error' => 'Error al reordenar el menú']);
        }
    }

    /**
     * Publica o despublica una página de menú
     */
    public function toggleStatus(Request $request, array $params): void
    {
        CSRF::guard();

        if (!Auth::can('menu.edit')) {
            Response::abort(403, 'No tienes permisos para cambiar el estado');
        }

        $id = (int)$params['id'];
        $page = $this->menuService->getPageById($id);

        if (!$page) {
            Response::json(['success' => false, 'error' => 'Página no encontrada']);
            return;
        }

        try {
            $newStatus = $page['is_published'] ? 0 : 1;
            $result = $this->menuService->updatePage($id, Auth::id(), [
                'is_published' => $newStatus
            ]);

            if ($result['success']) {
                $this->invalidateCacheAndLog(
                    'menu_toggle_status',
                    "Estado del menú ID {$id} cambiado a " . ($newStatus ? 'publicado' : 'borrador')
                );
            }

            Response::json($result);
        } catch (Throwable $e) {
            error_log('[MenuController] Error al cambiar estado: ' . $e->getMessage());
            Response::json(['success' => false, 'error' => 'Error al cambiar el estado']);
        }
    }

    /**
     * Obtiene el menú en formato JSON (para API)
     */
    public function getMenuJson(Request $request): void
    {
        $group = $request->get('group', 'main');
        
        try {
            $menu = $this->menuService->getNavigationMenu($group);

            Response::json([
                'success' => true,
                'menu' => $menu,
                'group' => $group,
            ]);
        } catch (Throwable $e) {
            error_log('[MenuController] Error al obtener menú JSON: ' . $e->getMessage());
            Response::json([
                'success' => false,
                'error' => 'Error al obtener el menú'
            ]);
        }
    }

    /**
     * Obtiene el breadcrumb de una página
     */
    public function getBreadcrumb(Request $request, array $params): void
    {
        $id = (int)$params['id'];
        
        try {
            $breadcrumb = $this->menuService->getBreadcrumb($id);

            Response::json([
                'success' => true,
                'breadcrumb' => $breadcrumb
            ]);
        } catch (Throwable $e) {
            error_log('[MenuController] Error al obtener breadcrumb: ' . $e->getMessage());
            Response::json([
                'success' => false,
                'error' => 'Error al obtener el breadcrumb'
            ]);
        }
    }

    /**
     * Obtiene estadísticas del menú
     */
    public function stats(Request $request): void
    {
        if (!Auth::can('menu.view')) {
            Response::abort(403, 'No tienes permisos para ver estadísticas');
        }

        try {
            $userId = Auth::id();
            $stats = $this->menuService->getStats($userId);

            Response::json([
                'success' => true,
                'stats' => $stats
            ]);
        } catch (Throwable $e) {
            error_log('[MenuController] Error al obtener estadísticas: ' . $e->getMessage());
            Response::json([
                'success' => false,
                'error' => 'Error al obtener estadísticas'
            ]);
        }
    }

    /**
     * Duplica un item de menú
     */
    public function duplicate(Request $request, array $params): void
    {
        CSRF::guard();

        if (!Auth::can('menu.create')) {
            Response::abort(403, 'No tienes permisos para duplicar items de menú');
        }

        $id = (int)$params['id'];

        try {
            $result = $this->menuService->duplicatePage($id, Auth::id());

            if ($result['success']) {
                $this->invalidateCacheAndLog(
                    'menu_item_duplicated',
                    "Item de menú duplicado ID: {$id}"
                );

                $this->handleSuccess($request, $result['message'], 'admin/menus', $result['id'] ?? null);
            } else {
                $this->handleError($request, $result['error'], 'admin/menus');
            }
        } catch (Throwable $e) {
            $this->handleException($request, $e, 'duplicar el item de menú', 'admin/menus');
        }
    }

    /**
     * Métodos auxiliares para manejo de respuestas
     */
    private function invalidateCacheAndLog(string $action, string $description): void
    {
        CacheService::invalidateAllPages();
        AuditService::log($action, $description, 'menus');
    }

    private function handleSuccess(Request $request, string $message, string $redirect, ?int $id = null): void
    {
        if ($request->isAjax()) {
            $response = ['success' => true, 'message' => $message];
            if ($id !== null) {
                $response['id'] = $id;
            }
            Response::json($response);
        } else {
            Session::flash('flash.success', $message);
            Response::redirect(url($redirect));
        }
    }

    private function handleError(Request $request, string $error, string $redirect): void
    {
        if ($request->isAjax()) {
            Response::json(['success' => false, 'error' => $error]);
        } else {
            Session::flash('flash.error', $error);
            Response::redirect(url($redirect));
        }
    }

    private function handleException(Request $request, Throwable $e, string $action, string $redirect, bool $redirectOnError = true): void
    {
        error_log("[MenuController] Error al {$action}: " . $e->getMessage());
        
        if ($request->isAjax()) {
            Response::json(['success' => false, 'error' => "Error al {$action}"]);
        } else if ($redirectOnError && $redirect) {
            Session::flash('flash.error', "Error al {$action}");
            Response::redirect(url($redirect));
        } else {
            Session::flash('flash.error', "Error al {$action}");
        }
    }
}
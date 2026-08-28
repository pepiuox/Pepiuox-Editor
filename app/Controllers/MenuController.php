<?php
namespace App\Controllers;

use App\Core\{Request, Response, View, Session, CSRF, Auth};
use App\Services\{MenuService, CacheService, AuditService, ActivityLoggerService};
use Throwable;

/**
 * Controlador de gestión de menús
 */
class MenuController
{
    private MenuService $menuService;
    private CacheService $cacheService;
    private AuditService $auditService;
    private ActivityLoggerService $logger;

    public function __construct(
        MenuService $menuService,
        CacheService $cacheService,
        AuditService $auditService,
        ActivityLoggerService $logger
    ) {
        $this->menuService  = $menuService;
        $this->cacheService = $cacheService;
        $this->auditService = $auditService;
        $this->logger       = $logger;
    }

    public function index(Request $request): void
    {
        if (!Auth::can('menus.view')) {
            Response::abort(403, 'No tienes permisos para ver el menú');
        }

        $userId   = Auth::id();
        $group    = $request->get('group', 'main');
        $items    = $this->menuService->getFlatMenu($group);
        $menuTree = $this->menuService->getAdminMenu($userId);
        $groups   = $this->menuService->getMenuGroups();
        $pages    = $this->menuService->getAllPagesForSelect($userId);
        $stats    = $this->menuService->getStats($userId);

        // ✅ Vista correcta: menus.index → views/menus/index.php
        Response::render(View::render('menus.index', [
            'items'    => $items,
            'menu'     => $menuTree,
            'menuTree' => $menuTree,
            'group'    => $group,
            'groups'   => $groups,
            'pages'    => $pages,
            'stats'    => $stats,
            'userId'   => $userId,
        ]));
    }

    public function create(Request $request): void
    {
        if (!Auth::can('menus.create')) {
            Response::abort(403);
        }

        $userId = Auth::id();
        $group  = $request->get('group', 'main');

        // ✅ Usa el servicio, NO Database::fetchAll() estático
        $pages     = $this->menuService->getAllPagesForSelect($userId);
        $menuItems = $this->menuService->getFlatMenu($group);
        $groups    = $this->menuService->getMenuGroups();

        Response::render(View::render('menus.create', [
            'pages' => $pages, 'menuItems' => $menuItems,
            'groups' => $groups, 'group' => $group,
        ]));
    }

    public function store(Request $request): void
    {
        CSRF::guard();
        if (!Auth::can('menus.create')) Response::abort(403);

        $title     = $request->post('title');
        $menuGroup = $request->post('menu_group', 'main');

        if (empty($title)) {
            $this->handleError($request, 'El título es obligatorio', 'admin/menus/create?group=' . $menuGroup);
            return;
        }

        try {
            $data = [
                'title'        => $title,
                'slug'         => $request->post('slug') ?: null,
                'url'          => $request->post('url'),
                'icon'         => $request->post('icon'),
                'page_id'      => $request->post('page_id') ?: null,
                'parent_id'    => $request->post('parent_id') ?: null,
                'menu_group'   => $menuGroup,
                'is_published' => (int)$request->post('is_published', 1),
                'status'       => $request->post('status', 'published'),
                'target'       => $request->post('target', '_self'),
                'order'        => $request->post('order') ?: $this->menuService->getNextOrder($menuGroup),
            ];

            $result = $this->menuService->createPage(Auth::id(), $data);

            if ($result['success']) {
                $this->invalidateCacheAndLog('menu_item_created', "Item creado: {$title}");
                $this->handleSuccess($request, $result['message'], 'admin/menus?group=' . $menuGroup, $result['id'] ?? null);
            } else {
                $this->handleError($request, $result['error'], 'admin/menus/create?group=' . $menuGroup);
            }
        } catch (Throwable $e) {
            $this->handleException($request, $e, 'crear el item', 'admin/menus/create?group=' . $menuGroup);
        }
    }

    public function edit(Request $request, array $params): void
    {
        if (!Auth::can('menus.edit')) Response::abort(403);

        $id     = (int)$params['id'];
        $userId = Auth::id();
        $page   = $this->menuService->getPageById($id);

        if (!$page) Response::abort(404, 'Página no encontrada');
        if ((int)$page['user_id'] !== $userId) Response::abort(403);

        $pages     = $this->menuService->getAllPagesForSelect($userId);
        $menuItems = $this->menuService->getFlatMenu($page['menu_group'] ?? 'main');
        // ✅ Usa el servicio
        $groups    = $this->menuService->getMenuGroups();

        Response::render(View::render('menus.edit', [
            'page' => $page, 'pages' => $pages, 'menuItems' => $menuItems,
            'groups' => $groups, 'group' => $page['menu_group'] ?? 'main',
        ]));
    }

    public function update(Request $request, array $params): void
    {
        CSRF::guard();
        if (!Auth::can('menus.edit')) Response::abort(403);

        $id        = (int)$params['id'];
        $menuGroup = $request->post('menu_group', 'main');

        try {
            $data = [
                'title' => $request->post('title'), 'slug' => $request->post('slug'),
                'url' => $request->post('url'), 'icon' => $request->post('icon'),
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
                $this->invalidateCacheAndLog('menu_item_updated', "Item actualizado ID: {$id}");
                $this->handleSuccess($request, $result['message'], 'admin/menus?group=' . $menuGroup);
            } else {
                $this->handleError($request, $result['error'], "admin/menus/{$id}/edit");
            }
        } catch (Throwable $e) {
            $this->handleException($request, $e, 'actualizar', "admin/menus/{$id}/edit");
        }
    }

    public function delete(Request $request, array $params): void
    {
        CSRF::guard();
        if (!Auth::can('menus.delete')) Response::abort(403);

        $id = (int)$params['id'];

        try {
            $page   = $this->menuService->getPageById($id);
            $result = $this->menuService->deletePage($id, Auth::id());

            if ($result['success']) {
                $this->invalidateCacheAndLog('menu_item_deleted', "Item eliminado: " . ($page['title'] ?? $id));
            }

            if ($request->isAjax()) {
                Response::json($result);
            } else {
                Session::flash($result['success'] ? 'flash.success' : 'flash.error', $result['message'] ?? $result['error']);
                Response::redirect(url('admin/menus'));
            }
        } catch (Throwable $e) {
            $this->handleException($request, $e, 'eliminar', null, false);
            if (!$request->isAjax()) Response::redirect(url('admin/menus'));
        }
    }

    public function reorder(Request $request): void
    {
        CSRF::guard();
        if (!Auth::can('menus.edit')) Response::abort(403);

        $items = $request->post('items');
        if (is_string($items)) $items = json_decode($items, true);

        if (!$items || !is_array($items)) {
            Response::json(['success' => false, 'error' => 'Datos inválidos'], 400);
            return;
        }

        try {
            $result = $this->menuService->updateOrder($items, Auth::id());
            if ($result['success']) {
                $this->invalidateCacheAndLog('menu_reordered', 'Menú reordenado');
            }
            Response::json($result);
        } catch (Throwable $e) {
            error_log('[MenuController] Error al reordenar: ' . $e->getMessage());
            Response::json(['success' => false, 'error' => 'Error al reordenar'], 500);
        }
    }

    public function toggleStatus(Request $request, array $params): void
    {
        CSRF::guard();
        if (!Auth::can('menus.edit')) Response::abort(403);

        $id   = (int)$params['id'];
        $page = $this->menuService->getPageById($id);

        if (!$page) {
            Response::json(['success' => false, 'error' => 'Página no encontrada']);
            return;
        }

        try {
            $newStatus = $page['is_published'] ? 0 : 1;
            $result = $this->menuService->updatePage($id, Auth::id(), ['is_published' => $newStatus]);
            if ($result['success']) {
                $this->invalidateCacheAndLog('menu_toggle_status', "Estado ID {$id} cambiado");
            }
            Response::json($result);
        } catch (Throwable $e) {
            Response::json(['success' => false, 'error' => 'Error al cambiar estado']);
        }
    }

    public function regenerateCache(Request $request): void
    {
        CSRF::guard();
        if (!Auth::can('menus.edit')) {
            if ($request->isAjax()) {
                Response::json(['success' => false, 'error' => 'Sin permisos'], 403);
                return;
            }
            Response::abort(403);
        }

        try {
            // ✅ Usa instancias inyectadas
            $this->cacheService->invalidateAllPages();
            $this->auditService->log(Auth::id(), 'menu_cache_regenerated', 'Caché regenerada', 'menus');
            $this->logger->logCurrentUser('menu_cache_regenerated', 'Regeneró la caché del menú');

            if ($request->isAjax()) {
                Response::json(['success' => true, 'message' => 'Caché regenerada']);
            } else {
                Session::flash('flash.success', 'Caché regenerada');
                Response::redirect(url('admin/menus'));
            }
        } catch (Throwable $e) {
            error_log('[MenuController] Error regenerar: ' . $e->getMessage());
            if ($request->isAjax()) {
                Response::json(['success' => false, 'error' => 'Error'], 500);
            } else {
                Session::flash('flash.error', 'Error al regenerar');
                Response::redirect(url('admin/menus'));
            }
        }
    }

    public function getMenuJson(Request $request): void
    {
        $group = $request->get('group', 'main');
        try {
            $menu = $this->menuService->getNavigationMenu($group);
            Response::json(['success' => true, 'menu' => $menu, 'group' => $group]);
        } catch (Throwable $e) {
            Response::json(['success' => false, 'error' => 'Error']);
        }
    }

    public function getBreadcrumb(Request $request, array $params): void
    {
        $id = (int)$params['id'];
        try {
            $breadcrumb = $this->menuService->getBreadcrumb($id);
            Response::json(['success' => true, 'breadcrumb' => $breadcrumb]);
        } catch (Throwable $e) {
            Response::json(['success' => false, 'error' => 'Error']);
        }
    }

    public function stats(Request $request): void
    {
        if (!Auth::can('menus.view')) Response::abort(403);
        try {
            $stats = $this->menuService->getStats(Auth::id());
            Response::json(['success' => true, 'stats' => $stats]);
        } catch (Throwable $e) {
            Response::json(['success' => false, 'error' => 'Error']);
        }
    }

    public function duplicate(Request $request, array $params): void
    {
        CSRF::guard();
        if (!Auth::can('menus.create')) Response::abort(403);

        $id = (int)$params['id'];
        try {
            $result = $this->menuService->duplicatePage($id, Auth::id());
            if ($result['success']) {
                $this->invalidateCacheAndLog('menu_item_duplicated', "Item duplicado ID: {$id}");
                $this->handleSuccess($request, $result['message'], 'admin/menus', $result['id'] ?? null);
            } else {
                $this->handleError($request, $result['error'], 'admin/menus');
            }
        } catch (Throwable $e) {
            $this->handleException($request, $e, 'duplicar', 'admin/menus');
        }
    }

    // ──────────────────────────────────────────────────────────────
    //  HELPERS
    // ──────────────────────────────────────────────────────────────

    private function invalidateCacheAndLog(string $action, string $description): void
    {
        try {
            $this->cacheService->invalidateAllPages();
            $this->auditService->log(Auth::id(), $action, $description, 'menus');
            $this->logger->logCurrentUser($action, $description);
        } catch (Throwable $e) {
            error_log('[MenuController] Error invalidateCacheAndLog: ' . $e->getMessage());
        }
    }

    private function handleSuccess(Request $request, string $message, string $redirect, ?int $id = null): void
    {
        if ($request->isAjax()) {
            $response = ['success' => true, 'message' => $message];
            if ($id !== null) $response['id'] = $id;
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

    private function handleException(Request $request, Throwable $e, string $action, ?string $redirect, bool $redirectOnError = true): void
    {
        error_log("[MenuController] Error al {$action}: " . $e->getMessage());
        if ($request->isAjax()) {
            Response::json(['success' => false, 'error' => "Error al {$action}"]);
        } elseif ($redirectOnError && $redirect) {
            Session::flash('flash.error', "Error al {$action}");
            Response::redirect(url($redirect));
        } else {
            Session::flash('flash.error', "Error al {$action}");
        }
    }
}
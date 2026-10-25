<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\{Request, Response, View, Session, CSRF, Auth, Database};
use App\Services\{MenuService, CacheService, AuditService, ActivityLoggerService};
use Throwable;

/**
 * Controlador del sistema de menús.
 *
 * Dos niveles:
 *  - Menús (catálogo `menus`): listar, crear, editar, activar, marcar como
 *    automático o principal, eliminar.
 *  - Ítems (`page_menu`): añadir, mover, eliminar y reordenar dentro de un menú.
 *
 * Además, «Sincronizar» reconstruye el menú automático a partir de las páginas
 * publicadas.
 *
 * @package App\Controllers
 */
class MenuController
{
    public function __construct(
        private readonly MenuService $menuService,
        private readonly CacheService $cacheService,
        private readonly AuditService $auditService,
        private readonly ActivityLoggerService $logger
    ) {
    }

    // ═══════════════════════════════════════════════════════════════
    //  MENÚS
    // ═══════════════════════════════════════════════════════════════

    /** GET /admin/menus */
    public function index(Request $request): void
    {
        if (!Auth::can('menus.view')) {
            Response::abort(403, 'No tienes permisos para ver los menús');
        }

        $menus = $this->menuService->listMenus();
        $stats = $this->menuService->getStats();

        $tree = [];
        foreach ($menus as $menu) {
            $tree[(int) $menu['id']] = $this->menuService->getTree((int) $menu['id'], false);
        }

        Response::render(View::render('menus.index', [
            'menus' => $menus,
            'menusTree' => $tree,
            'stats' => $stats,
            'locations' => MenuService::LOCATIONS,
        ]));
    }

    /** GET /admin/menus/create */
    public function create(Request $request): void
    {
        if (!Auth::can('menus.create')) {
            Response::abort(403, 'No tienes permisos para crear menús');
        }

        Response::render(View::render('menus.edit', [
            'menu' => null,
            'items' => [],
            'pages' => $this->menuService->getAllPagesForSelect(),
            'locations' => MenuService::LOCATIONS,
        ]));
    }

    /** POST /admin/menus */
    public function store(Request $request): void
    {
        CSRF::guard();
        if (!Auth::can('menus.create')) {
            Response::abort(403, 'No tienes permisos para crear menús');
        }

        $result = $this->menuService->createMenu([
            'name' => (string) $request->post('name', ''),
            'slug' => (string) $request->post('slug', ''),
            'location' => (string) $request->post('location', 'header'),
            'is_active' => (int) $request->post('is_active', 1),
        ]);

        if (!$result['success']) {
            $this->fail($request, (string) $result['error'], 'admin/menus/create');
            return;
        }

        $this->touch();

        if ($request->isAjax()) {
            Response::json($result);
            return;
        }

        Session::flash('flash.success', (string) $result['message']);
        Response::redirect(url('admin/menus/' . (int) $result['id'] . '/edit'));
    }

    /** GET /admin/menus/{id}/edit */
    public function edit(Request $request, array $params): void
    {
        if (!Auth::can('menus.edit')) {
            Response::abort(403, 'No tienes permisos para editar menús');
        }

        $id = (int) $params['id'];
        $menu = $this->menuService->findMenu($id);

        if (!$menu) {
            Response::abort(404, 'Menú no encontrado');
        }

        Response::render(View::render('menus.edit', [
            'menu' => $menu,
            'items' => $this->menuService->getTree($id, false),
            'pages' => $this->menuService->getAllPagesForSelect(),
            'locations' => MenuService::LOCATIONS,
        ]));
    }

    /** POST /admin/menus/{id} */
    public function update(Request $request, array $params): void
    {
        CSRF::guard();
        if (!Auth::can('menus.edit')) {
            Response::abort(403, 'No tienes permisos para editar menús');
        }

        $id = (int) $params['id'];

        $data = [
            'name' => (string) $request->post('name', ''),
            'slug' => (string) $request->post('slug', ''),
            'location' => (string) $request->post('location', 'header'),
            'is_primary' => (int) $request->post('is_primary', 0),
        ];
        // El menú automático no se puede desactivar: su estado lo manda el sistema.
        $data['is_active'] = $request->post('is_active') !== null
            ? (int) $request->post('is_active')
            : 1;

        $result = $this->menuService->updateMenu($id, $data);

        if (!$result['success']) {
            $this->fail($request, (string) $result['error'], "admin/menus/{$id}/edit");
            return;
        }

        $this->touch();

        if ($request->isAjax()) {
            Response::json($result);
            return;
        }

        Session::flash('flash.success', (string) $result['message']);
        Response::redirect(url("admin/menus/{$id}/edit"));
    }

    /** POST /admin/menus/{id}/toggle */
    public function toggleStatus(Request $request, array $params): void
    {
        CSRF::guard();
        if (!Auth::can('menus.edit')) {
            Response::abort(403);
        }

        $result = $this->menuService->toggleMenu((int) $params['id']);
        if ($result['success']) {
            $this->touch();
        }

        $this->respond($request, $result, 'admin/menus');
    }

    /** POST /admin/menus/{id}/delete */
    public function delete(Request $request, array $params): void
    {
        CSRF::guard();
        if (!Auth::can('menus.delete')) {
            Response::abort(403, 'No tienes permisos para eliminar menús');
        }

        $result = $this->menuService->deleteMenu((int) $params['id']);
        if ($result['success']) {
            $this->touch();
        }

        $this->respond($request, $result, 'admin/menus');
    }

    /** POST /admin/menus/{id}/default */
    public function setDefault(Request $request, array $params): void
    {
        CSRF::guard();
        if (!Auth::can('menus.edit')) {
            Response::abort(403);
        }

        $result = $this->menuService->setDefault((int) $params['id']);
        if ($result['success']) {
            $this->touch();
        }

        $this->respond($request, $result, 'admin/menus');
    }

    /** POST /admin/menus/{id}/duplicate */
    public function duplicate(Request $request, array $params): void
    {
        CSRF::guard();
        if (!Auth::can('menus.create')) {
            Response::abort(403);
        }

        $source = $this->menuService->findMenu((int) $params['id']);
        if (!$source) {
            $this->respond($request, ['success' => false, 'error' => 'Menú no encontrado'], 'admin/menus');
            return;
        }

        $created = $this->menuService->createMenu([
            'name' => $source['name'] . ' (copia)',
            'slug' => $source['slug'] . '-copia',
            'location' => $source['location'],
            'is_active' => (int) $source['is_active'],
        ]);

        if (!$created['success']) {
            $this->respond($request, $created, 'admin/menus');
            return;
        }

        // Copiar los ítems conservando la jerarquía: primero los padres.
        $newId = (int) $created['id'];
        $items = $this->menuService->getItems((int) $source['id'], false);
        $map = [];
        foreach ($items as $item) {
            $parentNew = $item['parent_id'] !== null ? ($map[(int) $item['parent_id']] ?? null) : null;
            $result = $this->menuService->addItem($newId, [
                'page_id' => $item['page_id'],
                'title' => $item['title'],
                'url' => (string) ($item['url'] ?? ''),
                'icon' => (string) ($item['icon'] ?? ''),
                'parent_id' => $parentNew,
                'order' => (int) $item['order'],
                'status' => (string) $item['status'],
                'is_published' => (int) $item['is_published'],
                'target' => (string) $item['target'],
            ]);
            if ($result['success']) {
                $map[(int) $item['id']] = (int) $result['id'];
            }
        }

        $this->touch();
        $this->respond($request, ['success' => true, 'message' => 'Menú duplicado.'], 'admin/menus');
    }

    // ═══════════════════════════════════════════════════════════════
    //  ÍTEMS
    // ═══════════════════════════════════════════════════════════════

    /** POST /admin/menus/{id}/items */
    public function addItem(Request $request, array $params): void
    {
        CSRF::guard();
        if (!Auth::can('menus.edit')) {
            Response::abort(403, 'No tienes permisos para editar menús');
        }

        $menuId = (int) $params['id'];

        $result = $this->menuService->addItem($menuId, [
            'page_id' => $request->post('page_id'),
            'title' => (string) $request->post('title', ''),
            'url' => (string) $request->post('url', ''),
            'icon' => (string) $request->post('icon', ''),
            'parent_id' => $request->post('parent_id'),
            'order' => $request->post('order'),
            'status' => (string) $request->post('status', 'published'),
            'target' => (string) $request->post('target', '_self'),
        ]);

        if ($result['success']) {
            $this->touch();
        }

        $this->respond($request, $result, "admin/menus/{$menuId}/edit");
    }

    /** POST /admin/menus/{id}/items/{item}/delete */
    public function deleteItem(Request $request, array $params): void
    {
        CSRF::guard();
        if (!Auth::can('menus.edit')) {
            Response::abort(403);
        }

        $menuId = (int) $params['id'];
        $result = $this->menuService->deleteItem($menuId, (int) $params['item']);

        if ($result['success']) {
            $this->touch();
        }

        $this->respond($request, $result, "admin/menus/{$menuId}/edit");
    }

    /** POST /admin/menus/{id}/items/{item}/move */
    public function moveItem(Request $request, array $params): void
    {
        CSRF::guard();
        if (!Auth::can('menus.edit')) {
            Response::abort(403);
        }

        $menuId = (int) $params['id'];
        $direction = (string) $request->post('direction', 'up');
        $result = $this->menuService->moveItem($menuId, (int) $params['item'], $direction);

        if ($result['success']) {
            $this->touch();
        }

        $this->respond($request, $result, "admin/menus/{$menuId}/edit");
    }

    /** POST /admin/menus/{id}/items/reorder  (AJAX) */
    public function reorderItems(Request $request, array $params): void
    {
        CSRF::guard();
        if (!Auth::can('menus.edit')) {
            Response::json(['success' => false, 'error' => 'Sin permisos'], 403);
            return;
        }

        $menuId = (int) $params['id'];
        $rows = $request->post('items');
        if (is_string($rows)) {
            $rows = json_decode($rows, true);
        }
        if (!is_array($rows) || $rows === []) {
            Response::json(['success' => false, 'error' => 'Datos inválidos'], 400);
            return;
        }

        $result = $this->menuService->reorderItems($menuId, $rows);
        if ($result['success']) {
            $this->touch();
        }

        Response::json($result);
    }

    // ═══════════════════════════════════════════════════════════════
    //  SINCRONIZACIÓN Y UTILIDADES
    // ═══════════════════════════════════════════════════════════════

    /** POST /admin/menus/sync */
    public function sync(Request $request): void
    {
        CSRF::guard();
        if (!Auth::can('menus.edit')) {
            Response::abort(403);
        }

        $result = $this->menuService->syncDefaultMenu();

        if ($result['success']) {
            $this->touch();
            Session::flash(
                'flash.success',
                sprintf(
                    'Menú automático sincronizado: %d añadido(s), %d actualizado(s), %d retirado(s).',
                    (int) $result['added'],
                    (int) $result['updated'],
                    (int) $result['removed']
                )
            );
        } else {
            Session::flash('flash.error', 'No se pudo sincronizar: ' . (string) $result['error']);
        }

        if ($request->isAjax()) {
            Response::json($result);
            return;
        }

        Response::redirect(url('admin/menus'));
    }

    /** POST /admin/menus/repair */
    public function repair(Request $request): void
    {
        CSRF::guard();
        if (!Auth::can('menus.edit')) {
            Response::abort(403);
        }

        $result = $this->menuService->repair();
        $this->touch();

        Session::flash(
            'flash.success',
            $result['changes'] === []
                ? 'No había nada que reparar.'
                : 'Reparado: ' . implode(' ', $result['changes'])
        );

        if ($request->isAjax()) {
            Response::json($result);
            return;
        }

        Response::redirect(url('admin/menus'));
    }

    /** POST /admin/menus/regenerate  (compatibilidad: caché + sync) */
    public function regenerateCache(Request $request): void
    {
        CSRF::guard();
        if (!Auth::can('menus.edit')) {
            Response::abort(403);
        }

        $this->cacheService->invalidateAllPages();
        $this->auditService->log(Auth::id(), 'menu_cache_regenerated', 'Caché de páginas invalidada desde menús', 'menus');
        Session::flash('flash.success', 'Caché invalidada.');

        if ($request->isAjax()) {
            Response::json(['success' => true, 'message' => 'Caché invalidada']);
            return;
        }

        Response::redirect(url('admin/menus'));
    }

    /** GET /admin/menus/json?menu={id}|?slug={slug} */
    public function getMenuJson(Request $request): void
    {
        $menuId = (int) $request->get('menu', 0);
        $slug = (string) $request->get('slug', '');

        $menu = $menuId > 0
            ? $this->menuService->findMenu($menuId)
            : ($slug !== '' ? $this->menuService->findMenuBySlug($slug) : null);

        if (!$menu) {
            Response::json(['success' => false, 'error' => 'Menú no encontrado'], 404);
            return;
        }

        Response::json([
            'success' => true,
            'menu' => [
                'id' => (int) $menu['id'],
                'name' => $menu['name'],
                'slug' => $menu['slug'],
                'location' => $menu['location'],
                'is_active' => (int) $menu['is_active'],
                'is_default' => (int) $menu['is_default'],
                'is_primary' => (int) $menu['is_primary'],
            ],
            'items' => $this->menuService->getTree((int) $menu['id'], false),
        ]);
    }

    /** GET /admin/menus/{id}/breadcrumb (migas de una página) */
    public function getBreadcrumb(Request $request, array $params): void
    {
        Response::json([
            'success' => true,
            'breadcrumb' => $this->menuService->getBreadcrumb((int) $params['id']),
        ]);
    }

    /** GET /admin/menus/stats */
    public function stats(Request $request): void
    {
        if (!Auth::can('menus.view')) {
            Response::abort(403);
        }

        Response::json(['success' => true, 'stats' => $this->menuService->getStats()]);
    }

    // ═══════════════════════════════════════════════════════════════
    //  HELPERS
    // ═══════════════════════════════════════════════════════════════

    /**
     * Invalida la caché de páginas y registra el cambio.
     *
     * El menú se pinta en el layout público en cada petición, pero la caché de
     * páginas guarda el HTML completo: si no se invalida, un cambio de menú no
     * se vería hasta que caducara.
     */
    private function touch(): void
    {
        try {
            $this->cacheService->invalidateAllPages();
            $this->auditService->log(Auth::id(), 'menu_changed', 'Cambio en menús', 'menus');
            $this->logger->logCurrentUser('menu_changed', 'Cambio en menús');
        } catch (Throwable $e) {
            error_log('[MenuController] Error invalidando caché: ' . $e->getMessage());
        }
    }

    /**
     * @param array{success:bool,message?:string,error?:string} $result
     */
    private function respond(Request $request, array $result, string $redirect): void
    {
        if ($request->isAjax()) {
            Response::json($result);
            return;
        }

        Session::flash($result['success'] ? 'flash.success' : 'flash.error', (string) ($result['message'] ?? $result['error'] ?? ''));
        Response::redirect(url($redirect));
    }

    private function fail(Request $request, string $error, string $redirect): void
    {
        if ($request->isAjax()) {
            Response::json(['success' => false, 'error' => $error], 422);
            return;
        }

        Session::flash('flash.error', $error);
        Response::redirect(url($redirect));
    }
}

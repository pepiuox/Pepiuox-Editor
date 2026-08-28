<?php
namespace App\Controllers;

use App\Core\{Request, Response, View, Auth, CSRF, Session};
use App\Services\CacheService;
use Throwable;

/**
 * Controlador de gestión de caché
 *
 * @package App\Controllers
 */
class CacheController
{
    private CacheService $cacheService;

    public function __construct(CacheService $cacheService)
    {
        $this->cacheService = $cacheService;
    }

    /**
     * Muestra el panel de caché con estadísticas
     */
    public function index(Request $request): void
    {
        if (!Auth::can('cache.view')) {
            Response::abort(403, 'No tienes permisos para ver la caché');
        }

        try {
            // ✅ Usa getCacheStats() (no stats())
            $stats = $this->cacheService->getCacheStats();

            Response::render(View::render('cache.index', [
                'stats' => $stats,
            ]));
        } catch (Throwable $e) {
            error_log('[CacheController] Error en index: ' . $e->getMessage());
            Response::render(View::render('cache.index', [
                'stats' => [
                    'files' => 0, 'size' => 0, 'enabled' => false, 'ttl' => 0,
                    'database' => ['total' => 0, 'active' => 0, 'expired' => 0],
                ],
            ]));
        }
    }

    /**
     * Limpia toda la caché
     */
    public function clear(Request $request): void
    {
        CSRF::guard();
        if (!Auth::can('cache.clear')) {
            Response::abort(403, 'No tienes permisos para limpiar la caché');
        }

        try {
            $success = $this->cacheService->clearAllCache();
            Session::flash(
                $success ? 'flash.success' : 'flash.warning',
                $success ? 'Caché limpiada correctamente' : 'Caché limpiada parcialmente'
            );
        } catch (Throwable $e) {
            error_log('[CacheController] Error en clear: ' . $e->getMessage());
            Session::flash('flash.error', 'Error al limpiar la caché');
        }

        Response::redirect(url('admin/cache'));
    }

    /**
     * Regenera toda la caché
     */
    public function regenerate(Request $request): void
    {
        CSRF::guard();
        if (!Auth::can('cache.clear')) {
            Response::abort(403, 'No tienes permisos para regenerar la caché');
        }

        try {
            $this->cacheService->clearAllCache();
            $count = method_exists($this->cacheService, 'regenerateAll')
                ? $this->cacheService->regenerateAll()
                : 0;

            Session::flash('flash.success', "Caché regenerada: {$count} páginas procesadas");
        } catch (Throwable $e) {
            error_log('[CacheController] Error en regenerate: ' . $e->getMessage());
            Session::flash('flash.error', 'Error al regenerar la caché');
        }

        Response::redirect(url('admin/cache'));
    }
}
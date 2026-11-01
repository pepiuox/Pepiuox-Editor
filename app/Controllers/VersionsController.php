<?php
namespace App\Controllers;
use App\Core\{Request, Response, View, Session, CSRF, Auth};
use App\Models\{PageVersion, Page};
use App\Services\{CacheService, AuditService};

/**
 * Versiones de una página.
 *
 * ── Por qué este controlador comprueba permisos ahora ───────────────
 * Hasta el 2026-10-31 no comprobaba ninguno: bastaba una sesión abierta para
 * listar, previsualizar, restaurar o borrar cualquier versión de cualquier
 * página. Restaurar contenido publicado es una operación sensible y no puede
 * depender solo de que alguien haya iniciado sesión (constitución, principio 21;
 * deuda 14: el middleware de permisos no está aplicado en ninguna ruta, así que
 * la comprobación se hace aquí, en el servidor y de forma explícita).
 *
 * Permisos usados, que ya existen en el catálogo:
 *   index / preview  → `pages.view`
 *   restore          → `pages.restore`
 *   delete           → `pages.delete`
 *
 * @package App\Controllers
 * @since   2026-10-31 (permisos añadidos)
 */
class VersionsController
{
    public function index(Request $req, array $params): void
    {
        $this->guard('pages.view');

        $pageId = (int)$params['id'];
        $page = Page::find($pageId);
        if (!$page) Response::abort(404);
        $versions = PageVersion::forPage($pageId);
        Response::render(View::render('pages.versions', compact('page','versions')));
    }

    public function preview(Request $req, array $params): void
    {
        $this->guard('pages.view');

        $v = PageVersion::find((int)$params['vid']);
        if (!$v) Response::abort(404);
        echo "<!doctype html><html><head><meta charset='utf-8'><style>" . $v['content_css'] . "</style></head><body>" . $v['content_html'] . "<script>" . $v['content_js'] . "</script></body></html>";
        exit;
    }

    public function restore(Request $req, array $params): void
    {
        $this->guard('pages.restore');
        CSRF::guard();
        $vid = (int)$params['vid'];
        $v = PageVersion::find($vid);
        if (!$v) Response::abort(404);
        PageVersion::restore($vid);
        CacheService::invalidatePage((int)$v['page_id']);
        \App\Services\SitemapService::generate();
        AuditService::log(Auth::id(), 'page_restored', "Versión #$vid restaurada", 'pages', (int)$v['page_id']);
        Session::flash('flash.success', 'Versión restaurada.');
        Response::redirect(url("admin/pages/{$v['page_id']}/edit"));
    }

    public function delete(Request $req, array $params): void
    {
        $this->guard('pages.delete');
        CSRF::guard();
        $v = PageVersion::find((int)$params['vid']);
        if ($v) PageVersion::delete((int)$params['vid']);
        Session::flash('flash.success', 'Versión eliminada.');
        Response::redirect(url("admin/pages/{$v['page_id']}/versions"));
    }

    /**
     * Exige un permiso. Si falta, 403 y fin.
     *
     * `Auth::can()` ya devuelve `true` para el rol `Super admin` sin consultar
     * la tabla de permisos, así que el administrador no queda bloqueado.
     */
    private function guard(string $permiso): void
    {
        if (Auth::can($permiso)) {
            return;
        }

        Response::abort(403, 'No tienes permiso para gestionar las versiones de esta página.');
    }
}
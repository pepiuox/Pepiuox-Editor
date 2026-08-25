<?php
namespace App\Controllers;
use App\Core\{Request, Response, Database};
use App\Models\{Page, ApiApplication, ApiToken};

class ApiController
{
    /** GET /api/v1/pages */
    public function pagesIndex(Request $req): void
    {
        $ctx = Request::$apiContext;
        $scopes = json_decode($ctx['scopes'] ?? '[]', true) ?: [];
        if (!in_array('pages:read', $scopes) && !in_array('*', $scopes)) {
            Response::json(['success'=>false,'message'=>'Insufficient scope'], 403);
        }
        $status = $req->get('status');
        $pages = Database::fetchAll(
            "SELECT id, title, slug, description, status, is_published, created_at, updated_at
               FROM pages" . ($status ? " WHERE status = ?" : "") . " ORDER BY updated_at DESC LIMIT 100",
            $status ? [$status] : []
        );
        Response::json(['success'=>true,'data'=>$pages,'total'=>count($pages)]);
    }

    /** GET /api/v1/pages/{slug} */
    public function pagesShow(Request $req, array $params): void
    {
        $page = Page::findBySlug($params['slug']);
        if (!$page) Response::json(['success'=>false,'message'=>'Not found'], 404);
        Response::json([
            'success' => true,
            'data' => [
                'id' => $page['id'],
                'title' => $page['title'],
                'slug' => $page['slug'],
                'description' => $page['description'],
                'content_html' => $page['content_html'],
                'content_css' => $page['content_css'],
                'status' => $page['status'],
                'created_at' => $page['created_at'],
                'updated_at' => $page['updated_at'],
            ]
        ]);
    }

    /** GET /api/v1/menus/{slug} */
    public function menusShow(Request $req, array $params): void
    {
        $items = \App\Services\MenuService::byGroup($params['slug']);
        Response::json(['success'=>true,'data'=>$items]);
    }

    /** GET /api/v1/me */
    public function me(Request $req): void
    {
        $ctx = Request::$apiContext;
        Response::json([
            'success' => true,
            'data' => [
                'application' => $ctx['app_name'],
                'scopes' => json_decode($ctx['scopes'] ?? '[]', true),
                'expires_at' => $ctx['expires_at'],
            ]
        ]);
    }
}
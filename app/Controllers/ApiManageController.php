<?php
namespace App\Controllers;
use App\Core\{Request, Response, View, Session, CSRF, Auth};
use App\Models\{ApiApplication, ApiToken};
use App\Services\{AuditService, NotificationService};

class ApiManageController
{
    public function index(Request $req): void
    {
        $apps = ApiApplication::all();
        Response::render(View::render('api.index', compact('apps')));
    }

    public function create(Request $req): void
    {
        Response::render(View::render('api.create'));
    }

    public function store(Request $req): void
    {
        CSRF::guard();
        $result = ApiApplication::create([
            'app_name' => $req->post('app_name'),
            'description' => $req->post('description'),
            'allowed_ips' => $req->post('allowed_ips'),
            'rate_limit' => (int)$req->post('rate_limit', 100),
            'permissions' => $req->post('permissions') ?? [],
            'status' => 'active',
            'expires_at' => $req->post('expires_at') ?: null,
        ]);
        // Firma real: log(?int $userId, string $action, ?string $details, ?string $context).
        AuditService::log(Auth::id(), 'api_app_created', 'API app: ' . $req->post('app_name') . " (ID {$result['id']})", 'api');
        Session::flash('flash.success', 'API application created.');
        Response::redirect(url("admin/api/{$result['id']}"));
    }

    public function show(Request $req, array $params): void
    {
        $app = ApiApplication::find((int)$params['id']);
        if (!$app) Response::abort(404);
        $tokens = ApiToken::forApplication((int)$app['id']);
        Response::render(View::render('api.show', compact('app','tokens')));
    }

    public function update(Request $req, array $params): void
    {
        CSRF::guard();
        ApiApplication::update((int)$params['id'], [
            'app_name' => $req->post('app_name'),
            'description' => $req->post('description'),
            'allowed_ips' => $req->post('allowed_ips'),
            'rate_limit' => (int)$req->post('rate_limit', 100),
            'status' => $req->post('status'),
            'expires_at' => $req->post('expires_at') ?: null,
            'permissions' => $req->post('permissions') ?? [],
        ]);
        Session::flash('flash.success', 'Updated.');
        Response::redirect(url("admin/api/{$params['id']}"));
    }

    public function delete(Request $req, array $params): void
    {
        CSRF::guard();
        ApiApplication::delete((int)$params['id']);
        Session::flash('flash.success', 'Deleted.');
        Response::redirect(url('admin/api'));
    }

    public function regenerate(Request $req, array $params): void
    {
        CSRF::guard();
        $keys = ApiApplication::regenerateKeys((int)$params['id']);
        Session::set('new_api_secret', $keys['api_secret']);
        Session::flash('flash.success', 'API keys regenerated. Save the secret now, it won\'t be shown again.');
        Response::redirect(url("admin/api/{$params['id']}"));
    }

    public function createToken(Request $req, array $params): void
    {
        CSRF::guard();
        $scopes = $req->post('scopes') ?? ['*'];
        $expiresIn = (int)$req->post('expires_in', 3600);
        $data = ApiToken::create((int)$params['id'], $scopes, $expiresIn);
        Session::set('new_api_token', $data['token']);
        Session::set('new_api_refresh', $data['refresh_token']);
        Session::flash('flash.success', 'Token created. Save it now.');
        Response::redirect(url("admin/api/{$params['id']}"));
    }

    public function revokeToken(Request $req, array $params): void
    {
        CSRF::guard();
        ApiToken::revoke((int)$params['tid']);
        Session::flash('flash.success', 'Token revoked.');
        Response::redirect(url("admin/api/{$params['id']}"));
    }
}
<?php
namespace App\Middleware;

use App\Core\{Auth, Response, Request};

class PermissionMiddleware
{
    private string $permission;

    public function __construct(string $permission)
    {
        $this->permission = $permission;
    }

    public function handle(Request $request): void
    {
        if (!Auth::can($this->permission)) {
            if ($request->isAjax()) {
                Response::json([
                    'success' => false,
                    'message' => "Sin permisos: {$this->permission}"
                ], 403);
            }
            Response::abort(403, 'No tienes permisos para esta acción.');
        }
    }
}
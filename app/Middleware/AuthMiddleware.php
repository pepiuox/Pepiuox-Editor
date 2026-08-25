<?php
namespace App\Middleware;

use App\Core\{Auth, Session, Response, Request};
use App\Controllers\AuthController;

class AuthMiddleware
{
    public function handle(Request $request): void
    {
        // Si no hay sesión activa, intentar autologin
        if (!Auth::check()) {
            AuthController::checkRemember();
        }

        // Verificar nuevamente después del autologin
        if (!Auth::check()) {
            if ($request->isAjax()) {
                Response::json(['success' => false, 'message' => 'No autenticado'], 401);
            }
            Session::flash('flash.error', 'Debes iniciar sesión.');
            Response::redirect(url('login'));
        }
    }
}
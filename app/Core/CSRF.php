<?php
namespace App\Core;

class CSRF
{
    public static function token(): string
    {
        if (empty($_SESSION['_csrf'])) {
            $_SESSION['_csrf'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['_csrf'];
    }

    public static function field(): string
    {
        $cfg = require __DIR__ . '/../../config/config.php';
        $name = $cfg['csrf']['token_name'];
        return '<input type="hidden" name="' . $name . '" value="' . self::token() . '">';
    }

    public static function verify(?string $token = null): bool
    {
        $cfg = require __DIR__ . '/../../config/config.php';
        $name = $cfg['csrf']['token_name'];
        $token = $token ?? ($_POST[$name] ?? null);
        if (!$token || empty($_SESSION['_csrf'])) return false;
        return hash_equals($_SESSION['_csrf'], $token);
    }

    public static function guard(): void
    {
        if (!self::verify()) {
            if ((new Request())->isAjax()) {
                Response::json(['success'=>false,'message'=>'Token CSRF inválido.'], 403);
            }
            Response::abort(403, 'Sesión inválida. Recarga la página.');
        }
    }
}
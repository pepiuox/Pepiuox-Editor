<?php
namespace App\Core;

use App\Services\TranslationService;
use App\Controllers\AuthController;

class App
{
    public static function boot(): void
    {
        $cfg = require CONFIG_PATH . '/config.php';
        date_default_timezone_set($cfg['timezone'] ?? 'UTC');

        if ($cfg['debug'] ?? false) {
            error_reporting(E_ALL);
            ini_set('display_errors', '1');
        } else {
            error_reporting(0);
            ini_set('display_errors', '0');
        }

        Session::start();

        // ═══ AUTOLOGIN (remember me) ═══
        if (!Auth::check()) {
            AuthController::checkRemember();
        }

        // Aplicar idioma del usuario
        $user = Auth::user();
        if ($user && !Session::has('user_locale')) {
            Session::set('user_locale', $user['locale'] ?? 'es');
        }
        
        TranslationService::setLang(Session::get('user_locale', 'es'));
    }
}
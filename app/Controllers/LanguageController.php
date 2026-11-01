<?php
namespace App\Controllers;
use App\Core\{Request, Response, Session, Auth};
use App\Services\TranslationService;

class LanguageController
{
    public function switch(Request $req, array $params): void
    {
        $code = $params['code'];
        $valid = TranslationService::languages();
        $codes = array_column($valid, 'code');
        if (!in_array($code, $codes, true)) $code = 'es';

        TranslationService::setLang($code);

        // Actualizar preferencia del usuario si está logueado
        $user = Auth::user();
        if ($user) {
            \App\Core\Database::execute("UPDATE users SET locale = ? WHERE id = ?", [$code, $user['id']]);
        }

        $referer = $_SERVER['HTTP_REFERER'] ?? url('admin');
        Response::redirect($referer);
    }
}
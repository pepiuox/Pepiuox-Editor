<?php
namespace App\Controllers;
use App\Core\{Request, Response, View, Session, CSRF, Database};
use App\Services\{TranslationService, AuditService};

class TranslationsController
{
    public function index(Request $req): void
    {
        $lang = $req->get('lang', 'es');
        $group = $req->get('group', 'app');
        $search = $req->get('search');
        $languages = TranslationService::languages();
        $groups = Database::fetchAll("SELECT DISTINCT `group` FROM translations ORDER BY `group`");

        $sql = "SELECT * FROM translations WHERE lang_code = ? AND `group` = ?";
        $params = [$lang, $group];
        if ($search) { $sql .= " AND (`key` LIKE ? OR value LIKE ?)"; $params[] = "%$search%"; $params[] = "%$search%"; }
        $sql .= " ORDER BY `key`";
        $translations = Database::fetchAll($sql, $params);

        Response::render(View::render('translations.index', compact('languages','groups','lang','group','translations','search')));
    }

    public function save(Request $req): void
    {
        CSRF::guard();
        $lang = $req->post('lang');
        $group = $req->post('group');
        $entries = $req->post('entries') ?? [];
        foreach ($entries as $key => $value) {
            TranslationService::set($key, (string)$value, $lang, $group);
        }
        AuditService::log('translations_updated', "Traducciones actualizadas ($lang/$group)", 'translations');
        Session::flash('flash.success', t('saved_successfully'));
        Response::redirect(url("admin/translations?lang=$lang&group=$group"));
    }

    public function add(Request $req): void
    {
        CSRF::guard();
        TranslationService::set($req->post('key'), $req->post('value'), $req->post('lang'), $req->post('group'));
        Session::flash('flash.success', t('saved_successfully'));
        Response::redirect(url("admin/translations?lang=".$req->post('lang')."&group=".$req->post('group')));
    }
}
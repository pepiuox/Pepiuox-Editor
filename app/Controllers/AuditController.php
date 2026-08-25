<?php
namespace App\Controllers;
use App\Core\{Request, Response, View, Database};

class AuditController
{
    public function index(Request $req): void
    {
        $page = max(1, (int)$req->get('page', 1));
        $limit = 50;
        $offset = ($page - 1) * $limit;
        $logs = Database::fetchAll(
            "SELECT a.*, u.username FROM activity_log a LEFT JOIN users u ON u.id = a.user_id ORDER BY a.created_at DESC LIMIT $limit OFFSET $offset"
        );
        $total = (int)Database::fetch("SELECT COUNT(*) AS c FROM activity_log")['c'];
        Response::render(View::render('audit.index', compact('logs','page','total','limit')));
    }
}
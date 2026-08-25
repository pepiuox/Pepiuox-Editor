<?php
namespace App\Controllers;
use App\Core\{Request, Response, View, Database};

class DashboardController
{
    public function index(Request $req): void
    {
        $stats = [
            'users'         => (int)Database::fetch("SELECT COUNT(*) AS c FROM users")['c'],
            'pages'         => (int)Database::fetch("SELECT COUNT(*) AS c FROM pages")['c'],
            'published'     => (int)Database::fetch("SELECT COUNT(*) AS c FROM pages WHERE status='published'")['c'],
            'drafts'        => (int)Database::fetch("SELECT COUNT(*) AS c FROM pages WHERE status='draft'")['c'],
            'templates'     => (int)Database::fetch("SELECT COUNT(*) AS c FROM templates")['c'],
            'logins_today'  => (int)Database::fetch("SELECT COUNT(*) AS c FROM user_logins WHERE login_date=CURDATE() AND login_success=1")['c'],
        ];

        $recentPages = Database::fetchAll(
            "SELECT p.id, p.title, p.status, p.updated_at, u.username
               FROM pages p LEFT JOIN users u ON u.id = p.user_id
              ORDER BY p.updated_at DESC LIMIT 6"
        );

        $recentActivity = Database::fetchAll(
            "SELECT a.*, u.username
               FROM activity_log a LEFT JOIN users u ON u.id = a.user_id
              ORDER BY a.created_at DESC LIMIT 10"
        );

        Response::render(View::render('dashboard.index', compact('stats','recentPages','recentActivity')));
    }
}
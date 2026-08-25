<?php
namespace App\Controllers;
use App\Core\{Request, Response, View, Auth};
use App\Services\NotificationService;

class NotificationsController
{
    public function index(Request $req): void
    {
        $user = Auth::user();
        if (!$user) Response::redirect(url('login'));
        $notifications = NotificationService::forUser((int)$user['id'], 50);
        Response::render(View::render('notifications.index', compact('notifications')));
    }

    public function list(Request $req): void
    {
        $user = Auth::user();
        if (!$user) Response::json([], 401);
        $notifications = NotificationService::forUser((int)$user['id'], 10);
        $unread = NotificationService::unreadCount((int)$user['id']);
        Response::json(['success'=>true,'data'=>$notifications,'unread'=>$unread]);
    }

    public function markRead(Request $req, array $params): void
    {
        $user = Auth::user();
        if (!$user) Response::json(['success'=>false], 401);
        NotificationService::markRead((int)$params['id'], (int)$user['id']);
        if ($req->isAjax()) Response::json(['success'=>true]);
        Response::redirect(url('admin/notifications'));
    }

    public function markAllRead(Request $req): void
    {
        $user = Auth::user();
        if (!$user) Response::json(['success'=>false], 401);
        NotificationService::markAllRead((int)$user['id']);
        if ($req->isAjax()) Response::json(['success'=>true]);
        Response::redirect(url('admin/notifications'));
    }

    public function delete(Request $req, array $params): void
    {
        $user = Auth::user();
        if (!$user) Response::json(['success'=>false], 401);
        NotificationService::delete((int)$params['id'], (int)$user['id']);
        if ($req->isAjax()) Response::json(['success'=>true]);
        Response::redirect(url('admin/notifications'));
    }

    public function clearAll(Request $req): void
    {
        $user = Auth::user();
        if (!$user) Response::json(['success'=>false], 401);
        NotificationService::clearAll((int)$user['id']);
        if ($req->isAjax()) Response::json(['success'=>true]);
        Response::redirect(url('admin/notifications'));
    }
}
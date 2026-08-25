<?php
namespace App\Services;
use App\Core\Database;

class NotificationService
{
    public static function notify(int $userId, string $type, string $title, ?string $message = null, ?string $link = null, ?string $icon = null): int
    {
        return Database::insert(
            "INSERT INTO notifications (user_id, type, title, message, link, icon) VALUES (?,?,?,?,?,?)",
            [$userId, $type, $title, $message, $link, $icon ?? self::defaultIcon($type)]
        );
    }

    public static function notifyMany(array $userIds, string $type, string $title, ?string $message = null, ?string $link = null): void
    {
        foreach ($userIds as $uid) self::notify((int)$uid, $type, $title, $message, $link);
    }

    public static function forUser(int $userId, int $limit = 20): array
    {
        return Database::fetchAll(
            "SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT ?",
            [$userId, $limit]
        );
    }

    public static function unreadCount(int $userId): int
    {
        return (int)Database::fetch(
            "SELECT COUNT(*) AS c FROM notifications WHERE user_id = ? AND is_read = 0",
            [$userId]
        )['c'];
    }

    public static function markRead(int $id, int $userId): int
    {
        return Database::execute(
            "UPDATE notifications SET is_read = 1 WHERE id = ? AND user_id = ?",
            [$id, $userId]
        );
    }

    public static function markAllRead(int $userId): int
    {
        return Database::execute(
            "UPDATE notifications SET is_read = 1 WHERE user_id = ? AND is_read = 0",
            [$userId]
        );
    }

    public static function delete(int $id, int $userId): int
    {
        return Database::execute(
            "DELETE FROM notifications WHERE id = ? AND user_id = ?",
            [$id, $userId]
        );
    }

    public static function clearAll(int $userId): int
    {
        return Database::execute("DELETE FROM notifications WHERE user_id = ?", [$userId]);
    }

    private static function defaultIcon(string $type): string
    {
        return match($type) {
            'success' => 'check-circle',
            'error'   => 'exclamation-triangle',
            'warning' => 'exclamation-circle',
            'info'    => 'info-circle',
            default   => 'bell',
        };
    }
}
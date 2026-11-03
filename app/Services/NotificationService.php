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
        $filas = Database::fetchAll(
            "SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT ?",
            [$userId, $limit]
        );

        foreach ($filas as $i => $fila) {
            $filas[$i]['link'] = self::resolverEnlace((string) ($fila['link'] ?? ''));
        }

        return $filas;
    }

    /**
     * Convierte el enlace guardado en una URL que funcione **ahora**.
     *
     * ── Por qué hace falta ──────────────────────────────────────────────
     * `url()` devuelve una URL absoluta y quien crea la notificación la guarda
     * tal cual. El enlace queda congelado con el host que hubiera en ese
     * momento: una notificación creada en desarrollo se queda apuntando a
     * `http://localhost:170` para siempre y, ya en producción, al pulsarla no
     * lleva a ninguna parte. El enlace «sí funciona» si se pega la dirección
     * buena, y por eso el fallo parece del botón cuando es del dato.
     *
     * Las notificaciones nuevas pueden guardar una ruta (`admin/pages`) o la
     * URL completa; las viejas llevan la URL completa. Aquí se aceptan las dos.
     *
     * @param string $enlace Valor guardado en `notifications.link`.
     * @return string URL utilizable, o cadena vacía si no había enlace.
     */
    private static function resolverEnlace(string $enlace): string
    {
        $enlace = trim($enlace);

        if ($enlace === '') {
            return '';
        }

        if (preg_match('#^https?://#i', $enlace) === 1) {
            $host = strtolower((string) (parse_url($enlace, PHP_URL_HOST) ?? ''));

            // Solo se reescriben los hosts que no pueden ser el servidor actual:
            // una notificación vieja creada en una máquina de desarrollo.
            $esDeDesarrollo = $host === 'localhost'
                || $host === '127.0.0.1'
                || $host === '::1'
                || str_starts_with($host, '192.168.')
                || str_starts_with($host, '10.')
                || preg_match('/^172\.(1[6-9]|2\d|3[01])\./', $host) === 1;

            if (!$esDeDesarrollo) {
                // Dominio propio o enlace externo: se respeta tal cual.
                return $enlace;
            }

            $ruta = (string) (parse_url($enlace, PHP_URL_PATH) ?? '/');

            return url($ruta === '' ? '/' : $ruta);
        }

        // Ruta relativa: se resuelve contra la base del sitio, así funciona
        // igual desde cualquier pantalla y con el CMS en un subdirectorio.
        return url($enlace);
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
<?php
namespace App\Controllers;

use App\Core\{Request, Response, View, Session, CSRF, Auth, Database};
use App\Models\User;
use App\Services\{AuditService, NotificationService};

class ProfileController
{
    /**
     * Muestra el perfil del usuario actual
     */
    public function index(Request $req): void
    {
        $user = Auth::user();
        if (!$user) {
            Response::redirect(url('login'));
        }

        // Cargar idiomas disponibles (con fallback)
        try {
            $languages = Database::fetchAll(
                "SELECT code, name, native_name, flag FROM languages WHERE is_active = 1 ORDER BY sort_order"
            );
        } catch (\Throwable $e) {
            error_log('[Profile] Error cargando idiomas: ' . $e->getMessage());
            $languages = [];
        }

        Response::render(View::render('profile.index', compact('user', 'languages')));
    }

    /**
     * Actualiza el perfil del usuario
     */
    public function update(Request $req): void
    {
        CSRF::guard();

        $user = Auth::user();
        if (!$user) {
            Response::redirect(url('login'));
        }

        $userId = (int)$user['id'];

        // ═══════════════════════════════════════════════════════════════
        // RECOPILAR DATOS (solo columnas que existen)
        // ═══════════════════════════════════════════════════════════════
        $data = [
            'full_name' => trim($req->post('full_name', '')),
            'bio'       => trim($req->post('bio', '')),
        ];

        // Columnas opcionales (pueden no existir en la BD)
        $optionalFields = ['locale', 'theme', 'timezone'];
        foreach ($optionalFields as $field) {
            $value = $req->post($field);
            if ($value !== null) {
                $data[$field] = trim($value);
            }
        }

        // ═══════════════════════════════════════════════════════════════
        // MANEJO DE AVATAR
        // ═══════════════════════════════════════════════════════════════
        if (!empty($_FILES['avatar']['tmp_name']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
            $avatarPath = $this->handleAvatar($_FILES['avatar'], $userId);
            if ($avatarPath) {
                $data['avatar'] = $avatarPath;
            } else {
                Session::flash('flash.warning', 'No se pudo subir el avatar. Verifica el formato y tamaño.');
            }
        }

        // ═══════════════════════════════════════════════════════════════
        // ACTUALIZAR EN BD (con manejo de columnas inexistentes)
        // ═══════════════════════════════════════════════════════════════
        try {
            $this->updateUserSafely($userId, $data);

            // Actualizar sesión
            if (isset($data['locale'])) Session::set('user_locale', $data['locale']);
            if (isset($data['theme'])) Session::set('user_theme', $data['theme']);

            AuditService::log('profile_updated', 'Perfil actualizado', 'users', $userId);
            Session::flash('flash.success', 'Perfil actualizado correctamente.');
        } catch (\Throwable $e) {
            error_log('[Profile] Error actualizando perfil: ' . $e->getMessage());
            Session::flash('flash.error', 'Error al actualizar el perfil: ' . $e->getMessage());
        }

        Response::redirect(url('admin/profile'));
    }

    /**
     * Cambia la contraseña del usuario
     */
    public function changePassword(Request $req): void
    {
        CSRF::guard();

        $user = Auth::user();
        if (!$user) {
            Response::redirect(url('login'));
        }

        $userId = (int)$user['id'];
        $current = $req->post('current_password', '');
        $new = $req->post('new_password', '');
        $confirm = $req->post('confirm_password', '');

        // ═══════════════════════════════════════════════════════════════
        // VALIDACIONES
        // ═══════════════════════════════════════════════════════════════
        if (!password_verify($current, $user['password_hash'])) {
            Session::flash('flash.error', 'La contraseña actual es incorrecta.');
            Response::redirect(url('admin/profile'));
        }

        if (strlen($new) < 8) {
            Session::flash('flash.error', 'La nueva contraseña debe tener al menos 8 caracteres.');
            Response::redirect(url('admin/profile'));
        }

        if ($new !== $confirm) {
            Session::flash('flash.error', 'Las contraseñas no coinciden.');
            Response::redirect(url('admin/profile'));
        }

        if (password_verify($new, $user['password_hash'])) {
            Session::flash('flash.error', 'La nueva contraseña debe ser diferente a la actual.');
            Response::redirect(url('admin/profile'));
        }

        // ═══════════════════════════════════════════════════════════════
        // ACTUALIZAR CONTRASEÑA
        // ═══════════════════════════════════════════════════════════════
        try {
            $cfg = require CONFIG_PATH . '/config.php';
            $hash = password_hash($new, $cfg['password']['algo'] ?? PASSWORD_DEFAULT, $cfg['password']['options'] ?? ['cost' => 12]);

            Database::execute("UPDATE users SET password_hash = ? WHERE id = ?", [$hash, $userId]);

            // Invalidar TODAS las sesiones excepto la actual
            try {
                Database::execute(
                    "DELETE FROM user_sessions WHERE user_id = ? AND id <> ?",
                    [$userId, session_id()]
                );
                User::clearRememberToken($userId);
            } catch (\Throwable $e) {
                error_log('[Profile] Error invalidando sesiones: ' . $e->getMessage());
            }

            AuditService::log('password_changed', 'Contraseña cambiada desde perfil', 'users', $userId);
            Session::flash('flash.success', 'Contraseña cambiada. Las otras sesiones han sido cerradas por seguridad.');
        } catch (\Throwable $e) {
            error_log('[Profile] Error cambiando contraseña: ' . $e->getMessage());
            Session::flash('flash.error', 'Error al cambiar la contraseña.');
        }

        Response::redirect(url('admin/profile'));
    }

    // ═══════════════════════════════════════════════════════════════
    // MÉTODOS PRIVADOS
    // ═══════════════════════════════════════════════════════════════

    /**
     * Actualiza el usuario de forma segura (maneja columnas inexistentes)
     */
    private function updateUserSafely(int $userId, array $data): void
    {
        if (empty($data)) return;

        // Obtener columnas existentes de la tabla users
        $columns = Database::fetchAll("SHOW COLUMNS FROM users");
        $existingColumns = array_column($columns, 'Field');

        // Filtrar solo las columnas que existen
        $filteredData = [];
        foreach ($data as $key => $value) {
            if (in_array($key, $existingColumns, true)) {
                $filteredData[$key] = $value;
            }
        }

        if (empty($filteredData)) return;

        // Construir UPDATE dinámico
        $fields = [];
        $params = [];
        foreach ($filteredData as $key => $value) {
            $fields[] = "`$key` = ?";
            $params[] = $value;
        }
        $params[] = $userId;

        $sql = "UPDATE users SET " . implode(', ', $fields) . " WHERE id = ?";
        Database::execute($sql, $params);
    }

    /**
     * Maneja la subida de avatar
     */
    private function handleAvatar(array $file, int $userId): ?string
    {
        // Validar tamaño (2MB máximo)
        if ($file['size'] > 2 * 1024 * 1024) {
            return null;
        }

        // Validar extensión
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        if (!in_array($ext, $allowed, true)) {
            return null;
        }

        // Validar MIME real
        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mime = $finfo->file($file['tmp_name']);
        if (!str_starts_with($mime, 'image/')) {
            return null;
        }

        // Determinar directorio de uploads
        $uploadPath = defined('UPLOADS_PATH') ? UPLOADS_PATH : (ROOT_PATH . '/uploads');
        $avatarsDir = $uploadPath . '/avatars';

        if (!is_dir($avatarsDir)) {
            if (!@mkdir($avatarsDir, 0775, true)) {
                error_log('[Profile] No se pudo crear directorio avatars: ' . $avatarsDir);
                return null;
            }
        }

        // Generar nombre único
        $filename = 'u' . $userId . '_' . bin2hex(random_bytes(8)) . '.' . $ext;
        $dest = $avatarsDir . '/' . $filename;

        if (!move_uploaded_file($file['tmp_name'], $dest)) {
            return null;
        }

        // Redimensionar si GD está disponible
        $this->resizeImage($dest, 256, 256);

        return 'avatars/' . $filename;
    }

    /**
     * Redimensiona una imagen a las dimensiones especificadas
     */
    private function resizeImage(string $path, int $w, int $h): void
    {
        if (!function_exists('imagecreatetruecolor')) return;

        $info = @getimagesize($path);
        if (!$info) return;

        [$ow, $oh, $type] = $info;
        $src = match ($type) {
            IMAGETYPE_JPEG => @imagecreatefromjpeg($path),
            IMAGETYPE_PNG  => @imagecreatefrompng($path),
            IMAGETYPE_GIF  => @imagecreatefromgif($path),
            IMAGETYPE_WEBP => function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($path) : null,
            default => null,
        };

        if (!$src) return;

        $dst = imagecreatetruecolor($w, $h);
        if ($type === IMAGETYPE_PNG) {
            imagealphablending($dst, false);
            imagesavealpha($dst, true);
        }

        imagecopyresampled($dst, $src, 0, 0, 0, 0, $w, $h, $ow, $oh);

        match ($type) {
            IMAGETYPE_JPEG => imagejpeg($dst, $path, 85),
            IMAGETYPE_PNG  => imagepng($dst, $path, 8),
            IMAGETYPE_GIF  => imagegif($dst, $path),
            IMAGETYPE_WEBP => function_exists('imagewebp') ? imagewebp($dst, $path, 85) : null,
            default => null,
        };

        imagedestroy($src);
        imagedestroy($dst);
    }
}
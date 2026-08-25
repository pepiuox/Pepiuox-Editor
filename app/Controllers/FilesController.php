<?php
namespace App\Controllers;

use App\Core\{Request, Response, View, Session, CSRF, Database, Auth};
use App\Services\{FileService, AuditService};

class FilesController
{
    public function index(Request $req): void
    {
        $cat = $req->get('category');
        $files = FileService::all($cat);
        Response::render(View::render('files.index', compact('files', 'cat')));
    }

    /**
     * Sube archivos al gestor
     * CORREGIDO: Sube directamente a uploads/ raíz
     */
    public function upload(Request $req): void
    {
        CSRF::guard();
        
        $uploaded = $_FILES['files'] ?? null;
        if (!$uploaded) {
            Session::flash('flash.error', 'No se seleccionaron archivos.');
            Response::redirect(url('admin/files'));
        }

        $count = 0;
        $errors = [];
        $names = (array)$uploaded['name'];

        for ($i = 0; $i < count($names); $i++) {
            $single = [
                'name' => $names[$i],
                'type' => is_array($uploaded['type']) ? $uploaded['type'][$i] : $uploaded['type'],
                'tmp_name' => is_array($uploaded['tmp_name']) ? $uploaded['tmp_name'][$i] : $uploaded['tmp_name'],
                'error' => is_array($uploaded['error']) ? $uploaded['error'][$i] : $uploaded['error'],
                'size' => is_array($uploaded['size']) ? $uploaded['size'][$i] : $uploaded['size'],
            ];

            if ($single['error'] !== UPLOAD_ERR_OK) {
                $errors[] = 'Error al subir: ' . $single['name'];
                continue;
            }

            // Validar tamaño (10MB máximo)
            if ($single['size'] > 10 * 1024 * 1024) {
                $errors[] = 'Archivo demasiado grande (máx 10MB): ' . $single['name'];
                continue;
            }

            // Validar extensión
            $ext = strtolower(pathinfo($single['name'], PATHINFO_EXTENSION));
            $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg', 'pdf', 'doc', 'docx', 'txt', 'zip'];
            if (!in_array($ext, $allowed, true)) {
                $errors[] = 'Tipo no permitido: ' . $single['name'];
                continue;
            }

            // Validar MIME real
            $finfo = new \finfo(FILEINFO_MIME_TYPE);
            $mime = $finfo->file($single['tmp_name']);
            
            // Determinar categoría
            $category = 'other';
            if (str_starts_with($mime, 'image/')) $category = 'image';
            elseif (str_starts_with($mime, 'video/')) $category = 'video';
            elseif (str_starts_with($mime, 'audio/')) $category = 'audio';
            elseif (in_array($ext, ['pdf', 'doc', 'docx', 'txt'], true)) $category = 'document';
            elseif (in_array($ext, ['zip', 'rar', '7z'], true)) $category = 'archive';

            // ═══════════════════════════════════════════════════════════════
            // CORREGIDO: Subir directamente a uploads/ raíz
            // ═══════════════════════════════════════════════════════════════
            $dir = UPLOADS_PATH;
            if (!is_dir($dir)) {
                @mkdir($dir, 0775, true);
            }

            // Generar nombre único
            $filename = bin2hex(random_bytes(16)) . '.' . $ext;
            $dest = $dir . '/' . $filename;

            if (!move_uploaded_file($single['tmp_name'], $dest)) {
                $errors[] = 'No se pudo mover: ' . $single['name'];
                continue;
            }

            // Guardar en BD
            $relPath = $filename; // ← Solo el nombre del archivo
            try {
                Database::insert(
                    "INSERT INTO files (user_id, filename, original_name, file_path, file_size, file_type, mime_type, category)
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?)",
                    [
                        Auth::id(),
                        $filename,
                        $single['name'],
                        $relPath,
                        $single['size'],
                        $ext,
                        $mime,
                        $category,
                    ]
                );
                $count++;
            } catch (\Throwable $e) {
                $errors[] = 'Error al guardar en BD: ' . $single['name'];
                @unlink($dest);
            }
        }

        if ($count > 0) {
            AuditService::log('files_uploaded', "$count archivos subidos", 'files');
            Session::flash('flash.success', "$count archivo(s) subido(s) correctamente.");
        }

        if (!empty($errors)) {
            Session::flash('flash.error', implode('<br>', $errors));
        }

        Response::redirect(url('admin/files'));
    }

    public function download(Request $req, array $params): void
    {
        $id = (int)$params['id'];
        $file = FileService::find($id);
        
        if (!$file) {
            Response::abort(404, 'Archivo no encontrado');
        }

        // Verificar permisos
        $uid = Auth::id();
        $canDownload = false;
        
        if ((int)$file['user_id'] === $uid) $canDownload = true;
        elseif ((int)($file['is_public'] ?? 0)) $canDownload = true;
        else {
            try {
                $shared = Database::fetch(
                    "SELECT * FROM file_shares 
                      WHERE file_id = ? 
                        AND (shared_with = ? OR shared_with IS NULL)
                        AND (expires_at IS NULL OR expires_at > NOW())
                      LIMIT 1",
                    [$id, $uid]
                );
                if ($shared) $canDownload = true;
            } catch (\Throwable $e) {}
        }

        if (!$canDownload) {
            Response::abort(403, 'No tienes permisos para descargar este archivo');
        }

        $path = UPLOADS_PATH . '/' . $file['file_path'];
        
        if (!is_file($path)) {
            Response::abort(404, 'Archivo físico no encontrado');
        }

        try {
            Database::execute("UPDATE files SET download_count = download_count + 1 WHERE id = ?", [$id]);
        } catch (\Throwable $e) {}

        header('Content-Type: ' . ($file['mime_type'] ?? 'application/octet-stream'));
        header('Content-Disposition: attachment; filename="' . basename($file['original_name']) . '"');
        header('Content-Length: ' . filesize($path));
        readfile($path);
        exit;
    }

    public function shareView(Request $req, array $params): void
    {
        $file = FileService::find((int)$params['id']);
        if (!$file) Response::abort(404);
        
        $shares = FileService::sharesOf((int)$file['id']);
        $users = Database::fetchAll("SELECT id, username, email FROM users WHERE is_active = 1 ORDER BY username");
        
        Response::render(View::render('files.share', compact('file', 'shares', 'users')));
    }

    public function share(Request $req, array $params): void
    {
        CSRF::guard();
        $id = (int)$params['id'];
        
        FileService::share(
            $id,
            (int)$req->post('shared_with'),
            $req->post('access_level', 'view'),
            $req->post('days') ?: null
        );
        
        AuditService::log('file_shared', "Archivo $id compartido", 'files', $id);
        Session::flash('flash.success', 'Archivo compartido.');
        Response::redirect(url("admin/files/$id/share"));
    }

    public function delete(Request $req, array $params): void
    {
        CSRF::guard();
        $id = (int)$params['id'];
        $file = FileService::find($id);
        
        if (!$file) {
            Session::flash('flash.error', 'Archivo no encontrado.');
            Response::redirect(url('admin/files'));
        }

        // Solo el propietario o admin puede eliminar
        if ((int)$file['user_id'] !== Auth::id() && !Auth::can('files.delete')) {
            Session::flash('flash.error', 'No tienes permisos para eliminar este archivo.');
            Response::redirect(url('admin/files'));
        }

        FileService::delete($id);
        AuditService::log('file_deleted', "Archivo eliminado: {$file['original_name']}", 'files', $id);
        Session::flash('flash.success', 'Archivo eliminado.');
        Response::redirect(url('admin/files'));
    }
}
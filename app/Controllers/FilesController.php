<?php
namespace App\Controllers;

use App\Core\{Request, Response, View, Session, CSRF, Database, Auth};
use App\Services\{FileManagerService, AuditService};
use App\Services\FileService;
use finfo;
use Throwable;

/**
 * Controlador de gestión de archivos
 * 
 * @package App\Controllers
 */
class FilesController
{
    private FileManagerService $fileManager;
    private array $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg', 'pdf', 'doc', 'docx', 'txt', 'zip', 'rar', '7z'];
    private int $maxFileSize = 10 * 1024 * 1024; // 10MB

    public function __construct(FileManagerService $fileManager)
    {
        $this->fileManager = $fileManager;
    }

    /**
     * Lista de archivos con filtros
     */
    public function index(Request $request): void
    {
        if (!Auth::can('files.view')) {
            Response::abort(403, 'No tienes permisos para ver archivos');
        }

        $filters = [
            'category' => $request->get('category'),
            'search' => $request->get('search'),
            'is_public' => $request->get('is_public'),
            'date_from' => $request->get('date_from'),
            'date_to' => $request->get('date_to'),
        ];

        // Limpiar filtros vacíos
        $filters = array_filter($filters, function($value) {
            return $value !== '' && $value !== null;
        });

        $userId = Auth::id();
        $files = $this->fileManager->getUserFiles($userId, $filters);
        $stats = $this->fileManager->getStats($userId);
        $categories = $this->fileManager->getCategories();

        // Obtener usuarios para compartir (si está habilitado)
        $users = [];
        if (Auth::can('files.share')) {
            $users = Database::fetchAll(
                "SELECT id, username, email FROM users WHERE is_active = 1 ORDER BY username"
            );
        }

        Response::render(View::render('admin.files.index', [
            'files' => $files,
            'stats' => $stats,
            'categories' => $categories,
            'filters' => $filters,
            'users' => $users,
        ]));
    }

    /**
     * Sube archivos al gestor (soporte multi-archivo)
     */
    public function upload(Request $request): void
    {
        CSRF::guard();

        if (!Auth::can('files.upload')) {
            Response::abort(403, 'No tienes permisos para subir archivos');
        }

        $uploadedFiles = $_FILES['files'] ?? null;
        
        // Si es una subida individual (WYSIWYG o AJAX)
        if (isset($_FILES['file'])) {
            $this->handleSingleUpload($request);
            return;
        }

        // Si no hay archivos
        if (!$uploadedFiles) {
            if ($request->isAjax()) {
                Response::json(['success' => false, 'error' => 'No se seleccionaron archivos']);
            } else {
                Session::flash('flash.error', 'No se seleccionaron archivos.');
                Response::redirect(url('admin/files'));
            }
            return;
        }

        // Procesar múltiples archivos
        $result = $this->handleMultipleUploads($uploadedFiles, $request);
        
        if ($request->isAjax()) {
            Response::json($result);
        } else {
            if ($result['success']) {
                Session::flash('flash.success', $result['message']);
            }
            if (!empty($result['errors'])) {
                Session::flash('flash.error', implode('<br>', $result['errors']));
            }
            Response::redirect(url('admin/files'));
        }
    }

    /**
     * Maneja la subida de un solo archivo (AJAX/WYSIWYG)
     */
    private function handleSingleUpload(Request $request): void
    {
        $file = $_FILES['file'];
        
        if ($file['error'] !== UPLOAD_ERR_OK) {
            Response::json([
                'success' => false,
                'error' => 'Error al subir el archivo: ' . $this->getUploadErrorMessage($file['error'])
            ]);
            return;
        }

        $result = $this->fileManager->upload(
            $file,
            Auth::id(),
            [
                'description' => $request->post('description'),
                'is_public' => (int)$request->post('is_public', 0),
                'category' => $request->post('category'),
                'check_duplicate' => true
            ]
        );

        Response::json($result);
    }

    /**
     * Maneja la subida de múltiples archivos
     */
    private function handleMultipleUploads(array $uploadedFiles, Request $request): array
    {
        $count = 0;
        $errors = [];
        $uploaded = [];

        $names = (array)$uploadedFiles['name'];
        $userId = Auth::id();
        $description = $request->post('description');
        $isPublic = (int)$request->post('is_public', 0);
        $category = $request->post('category');

        for ($i = 0; $i < count($names); $i++) {
            $single = [
                'name' => $names[$i],
                'type' => is_array($uploadedFiles['type']) ? $uploadedFiles['type'][$i] : $uploadedFiles['type'],
                'tmp_name' => is_array($uploadedFiles['tmp_name']) ? $uploadedFiles['tmp_name'][$i] : $uploadedFiles['tmp_name'],
                'error' => is_array($uploadedFiles['error']) ? $uploadedFiles['error'][$i] : $uploadedFiles['error'],
                'size' => is_array($uploadedFiles['size']) ? $uploadedFiles['size'][$i] : $uploadedFiles['size'],
            ];

            // Validar error
            if ($single['error'] !== UPLOAD_ERR_OK) {
                $errors[] = 'Error al subir: ' . $single['name'] . ' - ' . $this->getUploadErrorMessage($single['error']);
                continue;
            }

            // Validar tamaño
            if ($single['size'] > $this->maxFileSize) {
                $errors[] = 'Archivo demasiado grande (máx ' . $this->fileManager->formatFileSize($this->maxFileSize) . '): ' . $single['name'];
                continue;
            }

            // Validar extensión
            $ext = strtolower(pathinfo($single['name'], PATHINFO_EXTENSION));
            if (!in_array($ext, $this->allowedExtensions, true)) {
                $errors[] = 'Tipo de archivo no permitido: ' . $single['name'] . ' (.' . $ext . ')';
                continue;
            }

            // Validar MIME real
            $finfo = new finfo(FILEINFO_MIME_TYPE);
            $mime = $finfo->file($single['tmp_name']);

            // Usar el servicio para subir
            $result = $this->fileManager->upload(
                $single,
                $userId,
                [
                    'description' => $description,
                    'is_public' => $isPublic,
                    'category' => $category,
                    'check_duplicate' => true
                ]
            );

            if ($result['success']) {
                $count++;
                $uploaded[] = $result;
            } else {
                $errors[] = 'Error al subir ' . $single['name'] . ': ' . $result['error'];
            }
        }

        // Registrar auditoría
        if ($count > 0) {
            AuditService::log(
                'files_uploaded',
                "Subió {$count} archivo(s) al sistema",
                'files'
            );
        }

        return [
            'success' => $count > 0,
            'message' => $count > 0 ? "{$count} archivo(s) subido(s) correctamente." : 'No se pudo subir ningún archivo.',
            'count' => $count,
            'errors' => $errors,
            'uploaded' => $uploaded
        ];
    }

    /**
     * Descarga un archivo
     */
    public function download(Request $request, array $params): void
    {
        $id = (int)$params['id'];
        $userId = Auth::id();

        // Obtener archivo con permisos
        $file = $this->fileManager->getFile($id, $userId);

        if (!$file) {
            Response::abort(404, 'Archivo no encontrado');
        }

        // Verificar permisos adicionales (compartido)
        $canDownload = $this->checkDownloadPermissions($file, $userId);

        if (!$canDownload) {
            Response::abort(403, 'No tienes permisos para descargar este archivo');
        }

        // Verificar existencia física
        $path = $file['file_path'];
        if (!is_file($path)) {
            Response::abort(404, 'Archivo físico no encontrado en el servidor');
        }

        // Incrementar contador de descargas
        $this->fileManager->incrementDownloadCount($id);

        // Registrar auditoría
        AuditService::log(
            'file_downloaded',
            "Descargó el archivo: {$file['original_name']}",
            'files',
            $id
        );

        // Servir archivo
        $this->serveFile($path, $file);
    }

    /**
     * Verifica permisos de descarga
     */
    private function checkDownloadPermissions(array $file, int $userId): bool
    {
        // Propietario
        if ((int)$file['user_id'] === $userId) {
            return true;
        }

        // Archivo público
        if ((int)($file['is_public'] ?? 0) === 1) {
            return true;
        }

        // Compartido con usuario
        try {
            $shared = Database::fetch(
                "SELECT * FROM file_shares 
                 WHERE file_id = ? 
                 AND (shared_with = ? OR shared_with IS NULL)
                 AND (expires_at IS NULL OR expires_at > NOW())
                 LIMIT 1",
                [$file['id'], $userId]
            );
            return $shared !== null;
        } catch (Throwable $e) {
            return false;
        }
    }

    /**
     * Sirve el archivo para descarga
     */
    private function serveFile(string $path, array $file): void
    {
        $mimeType = $file['mime_type'] ?? mime_content_type($path) ?: 'application/octet-stream';
        $originalName = basename($file['original_name']);
        $fileSize = filesize($path);

        // Headers para descarga
        header('Content-Type: ' . $mimeType);
        header('Content-Disposition: attachment; filename="' . $originalName . '"');
        header('Content-Length: ' . $fileSize);
        header('Content-Transfer-Encoding: binary');
        header('Cache-Control: private, no-cache, must-revalidate');
        header('Pragma: no-cache');

        // Para archivos grandes
        if ($fileSize > 10 * 1024 * 1024) { // 10MB
            header('X-Accel-Redirect: ' . $path); // Para Nginx
            header('X-Sendfile: ' . $path); // Para Apache con mod_xsendfile
        }

        readfile($path);
        exit;
    }

    /**
     * Vista para compartir archivo
     */
    public function shareView(Request $request, array $params): void
    {
        if (!Auth::can('files.share')) {
            Response::abort(403, 'No tienes permisos para compartir archivos');
        }

        $id = (int)$params['id'];
        $file = $this->fileManager->getFile($id, Auth::id());

        if (!$file) {
            Response::abort(404, 'Archivo no encontrado');
        }

        // Obtener shares existentes
        $shares = Database::fetchAll(
            "SELECT * FROM file_shares WHERE file_id = ? ORDER BY created_at DESC",
            [$id]
        );

        // Obtener usuarios para compartir
        $users = Database::fetchAll(
            "SELECT id, username, email FROM users WHERE is_active = 1 ORDER BY username"
        );

        Response::render(View::render('admin.files.share', [
            'file' => $file,
            'shares' => $shares,
            'users' => $users,
        ]));
    }

    /**
     * Comparte un archivo con otro usuario
     */
    public function share(Request $request, array $params): void
    {
        CSRF::guard();

        if (!Auth::can('files.share')) {
            Response::abort(403, 'No tienes permisos para compartir archivos');
        }

        $id = (int)$params['id'];
        $file = $this->fileManager->getFile($id, Auth::id());

        if (!$file) {
            Response::abort(404, 'Archivo no encontrado');
        }

        $sharedWith = (int)$request->post('shared_with');
        $accessLevel = $request->post('access_level', 'view');
        $days = $request->post('days') ? (int)$request->post('days') : null;

        // Validar usuario destino
        $user = Database::fetch(
            "SELECT id FROM users WHERE id = ? AND is_active = 1",
            [$sharedWith]
        );

        if (!$user) {
            Session::flash('flash.error', 'Usuario destino no válido o inactivo');
            Response::redirect(url("admin/files/{$id}/share"));
            return;
        }

        // No compartir consigo mismo
        if ($sharedWith === Auth::id()) {
            Session::flash('flash.error', 'No puedes compartir el archivo contigo mismo');
            Response::redirect(url("admin/files/{$id}/share"));
            return;
        }

        // Guardar share
        $expiresAt = $days ? date('Y-m-d H:i:s', strtotime("+{$days} days")) : null;

        try {
            Database::insert(
                "INSERT INTO file_shares (file_id, shared_by, shared_with, access_level, expires_at, created_at)
                 VALUES (?, ?, ?, ?, ?, NOW())",
                [Auth::id(), $sharedWith, $accessLevel, $expiresAt]
            );

            AuditService::log(
                'file_shared',
                "Compartió el archivo ID {$id} con el usuario ID {$sharedWith}",
                'files',
                $id
            );

            Session::flash('flash.success', 'Archivo compartido correctamente');

        } catch (Throwable $e) {
            Session::flash('flash.error', 'Error al compartir el archivo: ' . $e->getMessage());
        }

        Response::redirect(url("admin/files/{$id}/share"));
    }

    /**
     * Revoca un compartir
     */
    public function revokeShare(Request $request, array $params): void
    {
        CSRF::guard();

        if (!Auth::can('files.share')) {
            Response::abort(403, 'No tienes permisos para revocar compartidos');
        }

        $shareId = (int)$params['share_id'];
        
        try {
            Database::execute(
                "DELETE FROM file_shares WHERE id = ? AND shared_by = ?",
                [$shareId, Auth::id()]
            );

            AuditService::log(
                'file_share_revoked',
                "Revocó compartido ID {$shareId}",
                'files'
            );

            Session::flash('flash.success', 'Compartido revocado correctamente');

        } catch (Throwable $e) {
            Session::flash('flash.error', 'Error al revocar el compartido: ' . $e->getMessage());
        }

        Response::redirect(url('admin/files'));
    }

    /**
     * Elimina un archivo (soft delete)
     */
    public function delete(Request $request, array $params): void
    {
        CSRF::guard();

        if (!Auth::can('files.delete')) {
            Response::abort(403, 'No tienes permisos para eliminar archivos');
        }

        $id = (int)$params['id'];
        $file = $this->fileManager->getFile($id, Auth::id());

        if (!$file) {
            if ($request->isAjax()) {
                Response::json(['success' => false, 'error' => 'Archivo no encontrado']);
            } else {
                Session::flash('flash.error', 'Archivo no encontrado');
                Response::redirect(url('admin/files'));
            }
            return;
        }

        // Solo propietario o admin puede eliminar
        if ((int)$file['user_id'] !== Auth::id() && !Auth::can('files.delete_all')) {
            if ($request->isAjax()) {
                Response::json(['success' => false, 'error' => 'No tienes permisos para eliminar este archivo']);
            } else {
                Session::flash('flash.error', 'No tienes permisos para eliminar este archivo');
                Response::redirect(url('admin/files'));
            }
            return;
        }

        $result = $this->fileManager->delete($id, Auth::id());

        if ($result['success']) {
            AuditService::log(
                'file_deleted',
                "Eliminó el archivo: {$file['original_name']}",
                'files',
                $id
            );
        }

        if ($request->isAjax()) {
            Response::json($result);
        } else {
            if ($result['success']) {
                Session::flash('flash.success', $result['message']);
            } else {
                Session::flash('flash.error', $result['error']);
            }
            Response::redirect(url('admin/files'));
        }
    }

    /**
     * Eliminación permanente (solo admin)
     */
    public function hardDelete(Request $request, array $params): void
    {
        CSRF::guard();

        if (!Auth::can('files.delete_all')) {
            Response::abort(403, 'No tienes permisos para eliminar archivos permanentemente');
        }

        $id = (int)$params['id'];
        $result = $this->fileManager->hardDelete($id);

        if ($request->isAjax()) {
            Response::json($result);
        } else {
            if ($result['success']) {
                AuditService::log(
                    'file_permanently_deleted',
                    "Eliminó permanentemente el archivo ID: {$id}",
                    'files',
                    $id
                );
                Session::flash('flash.success', $result['message']);
            } else {
                Session::flash('flash.error', $result['error']);
            }
            Response::redirect(url('admin/files'));
        }
    }

    /**
     * Obtiene archivos para el editor WYSIWYG
     */
    public function getEditorFiles(Request $request): void
    {
        if (!Auth::can('files.view')) {
            Response::abort(403);
        }

        $userId = Auth::id();
        $filters = [
            'category' => $request->get('category', 'image'),
            'is_public' => 1, // Mostrar solo públicos para el editor
        ];

        $files = $this->fileManager->getUserFiles($userId, $filters);

        // Formatear para el editor
        $editorFiles = array_map(function($file) {
            return [
                'id' => $file['id'],
                'name' => $file['original_name'],
                'url' => $file['url'] ?? url('admin/files/' . $file['id'] . '/download'),
                'thumbnail' => $file['url'] ?? '',
                'size' => $this->fileManager->formatFileSize($file['file_size']),
                'category' => $file['category'],
                'uploaded_at' => $file['created_at'],
            ];
        }, $files);

        Response::json([
            'success' => true,
            'files' => $editorFiles
        ]);
    }

    /**
     * Obtiene mensaje de error de upload
     */
    private function getUploadErrorMessage(int $errorCode): string
    {
        $errors = [
            UPLOAD_ERR_INI_SIZE => 'El archivo excede el tamaño máximo permitido por el servidor',
            UPLOAD_ERR_FORM_SIZE => 'El archivo excede el tamaño máximo permitido por el formulario',
            UPLOAD_ERR_PARTIAL => 'El archivo fue subido parcialmente',
            UPLOAD_ERR_NO_FILE => 'No se seleccionó ningún archivo',
            UPLOAD_ERR_NO_TMP_DIR => 'No hay directorio temporal disponible',
            UPLOAD_ERR_CANT_WRITE => 'Error al escribir en el disco',
            UPLOAD_ERR_EXTENSION => 'Una extensión de PHP detuvo la subida'
        ];

        return $errors[$errorCode] ?? 'Error desconocido al subir el archivo';
    }

    /**
     * Obtiene estadísticas de archivos
     */
    public function stats(Request $request): void
    {
        if (!Auth::can('files.view')) {
            Response::abort(403);
        }

        $userId = Auth::id();
        $stats = $this->fileManager->getStats($userId);
        $downloads = $this->fileManager->getDownloadStats(30);
        $categories = $this->fileManager->getCategories();

        Response::json([
            'success' => true,
            'stats' => $stats,
            'downloads' => $downloads,
            'categories' => $categories
        ]);
    }

    /**
     * Busca archivos por término
     */
    public function search(Request $request): void
    {
        if (!Auth::can('files.view')) {
            Response::abort(403);
        }

        $term = $request->get('q', '');
        if (strlen($term) < 2) {
            Response::json(['success' => true, 'files' => []]);
            return;
        }

        $filters = [
            'search' => $term
        ];

        $files = $this->fileManager->getUserFiles(Auth::id(), $filters);

        Response::json([
            'success' => true,
            'files' => $files
        ]);
    }
}
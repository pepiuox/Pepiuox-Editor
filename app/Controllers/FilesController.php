<?php
namespace App\Controllers;

use App\Core\{Request, Response, View, Auth, CSRF, Session, Database, Security};
use App\Services\{
    ActivityLoggerService,
    CacheService,
    AuditService,
    FileManagerService
};
use Throwable;

/**
 * Controlador de gestión de archivos
 *
 * @package App\Controllers
 */
class FilesController
{
    private Database $db;
    private ActivityLoggerService $logger;
    private CacheService $cacheService;
    private AuditService $auditService;
    
    // ✅ Nullable para permitir null
    private ?FileManagerService $fileManager = null;
    
    // ✅ Propiedades para validación
    private int $maxFileSize = 10485760; // 10MB por defecto
    private array $allowedExtensions;

    /**
     * Constructor con todas las dependencias inyectadas
     */
    public function __construct(
        Database $db,
        ActivityLoggerService $logger,
        CacheService $cacheService,
        AuditService $auditService,
        ?FileManagerService $fileManager = null
    ) {
        $this->db = $db;
        $this->logger = $logger;
        $this->cacheService = $cacheService;
        $this->auditService = $auditService;
        $this->fileManager = $fileManager;
        
        // ✅ Cargar extensiones permitidas desde la constante definida en paths.php
        $this->allowedExtensions = defined('ALLOWED_EXTENSIONS') 
            ? ALLOWED_EXTENSIONS 
            : [
                'jpg', 'jpeg', 'png', 'gif', 'webp', 'svg',
                'pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx',
                'txt', 'csv', 'zip', 'rar', 'tar', 'gz', '7z',
                'mp3', 'wav', 'ogg', 'm4a',
                'mp4', 'mpeg', 'webm', 'mov', 'avi'
            ];
        
        // ✅ Si FileManager es null, crear uno de emergencia
        if ($this->fileManager === null) {
            $this->fileManager = $this->createEmergencyFileManager();
        }
    }

    /**
     * Crea un FileManagerService de emergencia si no está disponible
     */
    private function createEmergencyFileManager(): FileManagerService
    {
        try {
            $fileModel = new \App\Models\FileModel($this->db);
            $mediaModel = new \App\Models\MediaModel($this->db);
            
            // ✅ Security requiere Database en su constructor
            $security = new Security($this->db);

            return new FileManagerService(
                $this->db,
                $fileModel,
                $mediaModel,
                $security,
                $this->logger
            );
        } catch (Throwable $e) {
            error_log('[FilesController] Error creando FileManagerService de emergencia: ' . $e->getMessage());
            
            // Si falla, crear un proxy/mock que implemente la interfaz
            return $this->createMockFileManager();
        }
    }

    /**
     * Crea un mock de FileManagerService para evitar errores fatales
     */
    private function createMockFileManager(): FileManagerService
    {
        // Crear una clase anónima que extienda FileManagerService
        return new class($this->db, $this->logger) extends FileManagerService {
            private $db;
            private $logger;
            
            public function __construct($db, $logger) {
                $this->db = $db;
                $this->logger = $logger;
                
                // ✅ Security requiere Database
                $fileModel = new \App\Models\FileModel($db);
                $mediaModel = new \App\Models\MediaModel($db);
                $security = new \App\Core\Security($db);
                
                parent::__construct(
                    $db,
                    $fileModel,
                    $mediaModel,
                    $security,
                    $logger
                );
            }
            
            public function getUserFiles(int $userId, array $filters = []): array {
                try {
                    $query = "SELECT * FROM files WHERE user_id = ? AND deleted_at IS NULL";
                    $params = [$userId];
                    
                    if (!empty($filters['category'])) {
                        $query .= " AND category = ?";
                        $params[] = $filters['category'];
                    }
                    
                    $query .= " ORDER BY created_at DESC";
                    
                    return $this->db->fetchAll($query, $params);
                } catch (Throwable $e) {
                    return [];
                }
            }
            
            public function upload(array $file, int $userId, array $options = []): array {
                return ['success' => false, 'error' => 'Servicio de archivos no disponible temporalmente'];
            }
            
            public function getFile(int $fileId, ?int $userId = null): ?array {
                try {
                    $query = "SELECT * FROM files WHERE id = ? AND deleted_at IS NULL";
                    $params = [$fileId];
                    
                    if ($userId !== null) {
                        $query .= " AND (user_id = ? OR is_public = 1)";
                        $params[] = $userId;
                    }
                    
                    return $this->db->fetch($query, $params);
                } catch (Throwable $e) {
                    return null;
                }
            }
            
            public function incrementDownloadCount(int $fileId): bool {
                try {
                    return $this->db->execute(
                        "UPDATE files SET download_count = download_count + 1 WHERE id = ?",
                        [$fileId]
                    );
                } catch (Throwable $e) {
                    return false;
                }
            }
            
            public function delete(int $fileId, int $userId): array {
                return ['success' => false, 'error' => 'Servicio de archivos no disponible temporalmente'];
            }
            
            public function uploadBase64(string $base64, int $userId, array $options = []): array {
                return ['success' => false, 'error' => 'Servicio de archivos no disponible temporalmente'];
            }
            
            public function getCategories(): array {
                return defined('FILE_CATEGORIES') ? FILE_CATEGORIES : ['document', 'image', 'audio', 'video', 'archive', 'other'];
            }
            
            public function formatFileSize(int $bytes): string {
                if ($bytes >= 1073741824) {
                    return number_format($bytes / 1073741824, 2) . ' GB';
                } elseif ($bytes >= 1048576) {
                    return number_format($bytes / 1048576, 2) . ' MB';
                } elseif ($bytes >= 1024) {
                    return number_format($bytes / 1024, 2) . ' KB';
                } else {
                    return $bytes . ' bytes';
                }
            }
            
            public function getDownloadStats(int $days = 30): array { return []; }
            public function getPublicFiles(array $filters = []): array { return []; }
            public function getStats(?int $userId = null): array { return ['total_files' => 0]; }
            public function hardDelete(int $fileId): array { return ['success' => false]; }
            public function update(int $fileId, int $userId, array $data): array { return ['success' => false]; }
            public function findDuplicate(string $filename, int $userId): ?array { return null; }
            public function cleanTempFiles(): int { return 0; }
            public function getFileIcon(string $fileType): string { return 'bi bi-file'; }
            public function getCategoryColor(string $category): string { return 'secondary'; }
        };
    }

    /**
     * Muestra la lista de archivos con filtro por categoría
     */
    public function index(Request $request): void
    {
        if (!Auth::can('files.view')) {
            Response::abort(403, 'No tienes permisos para ver archivos');
        }

        try {
            $cat = $request->get('category');
            $userId = Auth::id();

            // ✅ Validar categoría usando la constante definida en paths.php
            $validCategories = defined('FILE_CATEGORIES') ? FILE_CATEGORIES : ['image', 'document', 'audio', 'video', 'archive', 'other'];
            
            $filters = [];
            if ($cat && in_array($cat, $validCategories, true)) {
                $filters['category'] = $cat;
            }

            // Búsqueda por nombre o descripción (antes solo existía la avanzada).
            $search = trim((string) $request->get('q', ''));
            if ($search !== '') {
                $filters['search'] = $search;
            }

            // ✅ Usar FileManagerService
            // Quien puede administrar archivos ve TODO uploads/; el resto, solo
            // los suyos. Antes se filtraba siempre por propietario y los archivos
            // subidos desde el editor visual no aparecían para otros usuarios.
            $canManageAll = Auth::can('files.manage');
            $files = $this->fileManager->getUserFiles($canManageAll ? null : $userId, $filters);

            // Datos de presentación: ruta relativa, URL de vista previa, tamaño
            // legible y si el archivo físico existe realmente. Antes la vista
            // componía la URL con `file_path` tal cual, y las filas heredadas
            // guardan rutas absolutas de Windows (imagen rota).
            foreach ($files as &$file) {
                $stored = (string) ($file['file_path'] ?? '');
                $file['relative_path'] = $this->fileManager->relativePath($stored);
                $file['preview_url'] = $this->fileManager->urlFor($stored);
                $file['exists_on_disk'] = $this->fileManager->fileExists($stored);
                $file['size_human'] = $this->fileManager->formatFileSize((int) ($file['file_size'] ?? 0));
            }
            unset($file);

            // Contadores para el panel: lo que hay en la tabla y lo que hay en disco.
            $stats = [
                'in_db' => count($files),
                'on_disk' => $this->countDiskFiles(),
                'missing' => count(array_filter($files, static fn (array $f): bool => !$f['exists_on_disk'])),
            ];

            // ✅ Vista correcta
            Response::render(View::render('files.index', [
                'files' => $files,
                'cat' => $cat,
                'q' => $search,
                'stats' => $stats,
            ]));
        } catch (Throwable $e) {
            error_log('[FilesController] Error en index: ' . $e->getMessage());
            Response::render(View::render('files.index', [
                'files' => [],
                'cat' => null,
                'q' => '',
                'stats' => ['in_db' => 0, 'on_disk' => 0, 'missing' => 0],
            ]));
        }
    }

    /**
     * Sincroniza la tabla `files` con lo que hay en uploads/.
     *
     * Ruta: POST /admin/files/sync
     *
     * Sin esto, lo que se sube desde el editor visual (o se copia a mano a la
     * carpeta) no aparece en el gestor, porque el gestor lista la tabla y el
     * editor escribe en disco.
     */
    public function sync(Request $request): void
    {
        CSRF::guard();

        if (!Auth::can('files.upload')) {
            if ($request->isAjax()) {
                Response::json(['success' => false, 'error' => 'Sin permisos para sincronizar archivos'], 403);
                return;
            }
            Response::abort(403, 'No tienes permisos para sincronizar archivos');
        }

        $result = $this->fileManager->syncFromDisk(Auth::id());

        $message = sprintf(
            'Sincronizado: %d archivo(s) registrado(s), %d ruta(s) normalizada(s), %d registro(s) sin archivo.',
            (int) $result['registered'],
            (int) $result['normalized'],
            (int) $result['missing']
        );

        if ($request->isAjax()) {
            Response::json($result + ['message' => $message]);
            return;
        }

        Session::flash($result['success'] ? 'flash.success' : 'flash.error', $message);
        Response::redirect(url('admin/files'));
    }

    /**
     * Cuenta los archivos reales que hay en uploads/.
     */
    private function countDiskFiles(): int
    {
        $base = defined('UPLOADS_PATH') ? rtrim(UPLOADS_PATH, '/') : (ROOT_PATH . '/uploads');
        if (!is_dir($base)) {
            return 0;
        }

        $allowed = defined('ALLOWED_EXTENSIONS') ? ALLOWED_EXTENSIONS : ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg'];
        $count = 0;
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($base, \RecursiveDirectoryIterator::SKIP_DOTS)
        );
        foreach ($iterator as $file) {
            if ($file->isFile() && in_array(strtolower($file->getExtension()), $allowed, true)) {
                $count++;
            }
        }
        return $count;
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

        // ✅ Subida individual (AJAX)
        if (isset($_FILES['file'])) {
            $this->handleSingleUpload($request);
            return;
        }

        // ✅ Subida múltiple
        if (isset($_FILES['files'])) {
            $result = $this->handleMultipleUploads($_FILES['files'], $request);
            
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
            return;
        }

        // ❌ No se recibieron archivos
        if ($request->isAjax()) {
            Response::json(['success' => false, 'error' => 'No se seleccionaron archivos']);
        } else {
            Session::flash('flash.error', 'No se seleccionaron archivos.');
            Response::redirect(url('admin/files'));
        }
    }

    /**
     * Maneja subida individual (AJAX/WYSIWYG)
     */
    private function handleSingleUpload(Request $request): void
    {
        $file = $_FILES['file'];

        if ($file['error'] !== UPLOAD_ERR_OK) {
            Response::json([
                'success' => false,
                'error' => 'Error al subir: ' . $this->getUploadErrorMessage($file['error'])
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
     * Maneja subida múltiple
     */
    private function handleMultipleUploads(array $uploadedFiles, Request $request): array
    {
        $count = 0;
        $errors = [];
        $uploaded = [];
        $userId = Auth::id();
        $description = $request->post('description');
        $isPublic = (int)$request->post('is_public', 0);
        $category = $request->post('category');

        // ✅ Normalizar array de archivos
        $files = $this->normalizeFilesArray($uploadedFiles);

        foreach ($files as $single) {
            // ✅ Validar error de subida
            if ($single['error'] !== UPLOAD_ERR_OK) {
                $errors[] = 'Error al subir: ' . $single['name'] . ' - ' . $this->getUploadErrorMessage($single['error']);
                continue;
            }

            // ✅ Validar tamaño
            if ($single['size'] > $this->maxFileSize) {
                $errors[] = 'Archivo demasiado grande (máx ' . $this->fileManager->formatFileSize($this->maxFileSize) . '): ' . $single['name'];
                continue;
            }

            // ✅ Validar extensión usando la constante definida en paths.php
            $ext = strtolower(pathinfo($single['name'], PATHINFO_EXTENSION));
            if (!in_array($ext, $this->allowedExtensions, true)) {
                $errors[] = 'Tipo de archivo no permitido: ' . $single['name'] . ' (.' . $ext . ')';
                continue;
            }

            // ✅ Usar el servicio para subir
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
                $errors[] = 'Error al subir ' . $single['name'] . ': ' . ($result['error'] ?? 'desconocido');
            }
        }

        if ($count > 0) {
            $this->auditService->log(
                Auth::id(),
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
     * Normaliza el array de archivos para subida múltiple
     */
    private function normalizeFilesArray(array $files): array
    {
        // ✅ Si es un solo archivo, convertirlo a array
        if (!is_array($files['name'])) {
            return [$files];
        }

        $result = [];
        $count = count($files['name']);

        for ($i = 0; $i < $count; $i++) {
            $result[] = [
                'name' => $files['name'][$i],
                'type' => $files['type'][$i],
                'tmp_name' => $files['tmp_name'][$i],
                'error' => $files['error'][$i],
                'size' => $files['size'][$i],
            ];
        }

        return $result;
    }

    /**
     * Descarga un archivo
     */
    public function download(Request $request, array $params): void
    {
        $id = (int)($params['id'] ?? 0);
        if (!$id) {
            Response::abort(404, 'ID de archivo no válido');
        }

        $userId = Auth::id();
        // Quien administra archivos puede descargar cualquiera; el resto, solo
        // los suyos o los públicos.
        $file = $this->fileManager->getFile($id, Auth::can('files.manage') ? null : $userId);

        if (!$file) {
            Response::abort(404, 'Archivo no encontrado');
        }

        // ✅ Verificar permisos (quien administra archivos puede descargar todo)
        if (!$this->checkDownloadPermissions($file, $userId) && !Auth::can('files.manage')) {
            Response::abort(403, 'No tienes permisos para descargar este archivo');
        }

        // ✅ Verificar que el archivo físico existe (resolviendo rutas heredadas)
        $path = $this->fileManager->absolutePath((string) ($file['file_path'] ?? ''));
        if ($path === '' || !is_file($path)) {
            Response::abort(404, 'Archivo físico no encontrado');
        }

        // ✅ Incrementar contador de descargas
        $this->fileManager->incrementDownloadCount($id);

        // ✅ Registrar auditoría
        $this->auditService->log(
            Auth::id(),
            'file_downloaded',
            "Descargó el archivo: {$file['original_name']}",
            'files'
        );

        // ✅ Servir archivo
        $this->serveFile($path, $file);
    }

    /**
     * Verifica permisos de descarga
     */
    private function checkDownloadPermissions(array $file, int $userId): bool
    {
        // ✅ Propietario
        if ((int)$file['user_id'] === $userId) {
            return true;
        }

        // ✅ Archivo público
        if ((int)($file['is_public'] ?? 0) === 1) {
            return true;
        }

        // ✅ Compartido con el usuario
        try {
            $shared = $this->db->fetch(
                "SELECT * FROM file_shares
                 WHERE file_id = ?
                 AND shared_with = ?
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
        $mimeType = $file['mime_type'] ?? (mime_content_type($path) ?: 'application/octet-stream');
        $originalName = basename($file['original_name'] ?? 'archivo');
        $fileSize = filesize($path);

        // ✅ Limpiar buffers
        if (ob_get_level()) {
            ob_end_clean();
        }

        // ✅ Headers
        header('Content-Type: ' . $mimeType);
        header('Content-Disposition: attachment; filename="' . $originalName . '"');
        header('Content-Length: ' . $fileSize);
        header('Content-Transfer-Encoding: binary');
        header('Cache-Control: private, no-cache, must-revalidate');
        header('Pragma: no-cache');

        // ✅ Leer y enviar archivo
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

        $id = (int)($params['id'] ?? 0);
        if (!$id) {
            Response::abort(404, 'ID de archivo no válido');
        }

        $file = $this->fileManager->getFile($id, Auth::id());
        if (!$file) {
            Response::abort(404, 'Archivo no encontrado');
        }

        // ✅ Obtener compartidos
        $shares = $this->db->fetchAll(
            "SELECT s.*, u.username, u.email 
             FROM file_shares s 
             LEFT JOIN users u ON u.id = s.shared_with 
             WHERE s.file_id = ? 
             ORDER BY s.created_at DESC",
            [$id]
        );

        // ✅ Obtener usuarios activos para compartir
        $users = $this->db->fetchAll(
            "SELECT id, username, email FROM users WHERE status = 'active' ORDER BY username"
        );

        Response::render(View::render('files.share', [
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

        $id = (int)($params['id'] ?? 0);
        if (!$id) {
            Session::flash('flash.error', 'ID de archivo no válido');
            Response::redirect(url('admin/files'));
            return;
        }

        $file = $this->fileManager->getFile($id, Auth::id());
        if (!$file) {
            Session::flash('flash.error', 'Archivo no encontrado');
            Response::redirect(url('admin/files'));
            return;
        }

        $sharedWith = (int)$request->post('shared_with', 0);
        $accessLevel = $request->post('access_level', 'view');
        $days = (int)$request->post('days', 0);

        // ✅ Validar usuario destino
        if ($sharedWith <= 0) {
            Session::flash('flash.error', 'Debes seleccionar un usuario destino');
            Response::redirect(url("admin/files/{$id}/share"));
            return;
        }

        if ($sharedWith === Auth::id()) {
            Session::flash('flash.error', 'No puedes compartir el archivo contigo mismo');
            Response::redirect(url("admin/files/{$id}/share"));
            return;
        }

        // ✅ Verificar que el usuario existe
        $user = $this->db->fetch(
            "SELECT id FROM users WHERE id = ? AND status = 'active'",
            [$sharedWith]
        );

        if (!$user) {
            Session::flash('flash.error', 'Usuario destino no válido o inactivo');
            Response::redirect(url("admin/files/{$id}/share"));
            return;
        }

        // ✅ Calcular expiración
        $expiresAt = $days > 0 ? date('Y-m-d H:i:s', strtotime("+{$days} days")) : null;

        try {
            $this->db->insert(
                "INSERT INTO file_shares 
                 (file_id, shared_by, shared_with, access_level, expires_at, created_at) 
                 VALUES (?, ?, ?, ?, ?, NOW())",
                [$id, Auth::id(), $sharedWith, $accessLevel, $expiresAt]
            );

            $this->auditService->log(
                Auth::id(),
                'file_shared',
                "Compartió el archivo {$file['original_name']} con el usuario ID {$sharedWith}",
                'files'
            );

            Session::flash('flash.success', 'Archivo compartido correctamente');
        } catch (Throwable $e) {
            Session::flash('flash.error', 'Error al compartir: ' . $e->getMessage());
        }

        Response::redirect(url("admin/files/{$id}/share"));
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

        $id = (int)($params['id'] ?? 0);
        if (!$id) {
            if ($request->isAjax()) {
                Response::json(['success' => false, 'error' => 'ID no válido']);
            } else {
                Session::flash('flash.error', 'ID de archivo no válido');
                Response::redirect(url('admin/files'));
            }
            return;
        }

        // Quien administra archivos puede eliminar los de cualquier usuario:
        // se borra con el id del propietario para respetar la regla del modelo.
        $canManageAll = Auth::can('files.manage');
        $file = $this->fileManager->getFile($id, $canManageAll ? null : Auth::id());

        if (!$file) {
            if ($request->isAjax()) {
                Response::json(['success' => false, 'error' => 'Archivo no encontrado']);
            } else {
                Session::flash('flash.error', 'Archivo no encontrado');
                Response::redirect(url('admin/files'));
            }
            return;
        }

        $ownerId = $canManageAll ? (int) $file['user_id'] : Auth::id();
        $result = $this->fileManager->delete($id, $ownerId);

        if ($result['success']) {
            $this->auditService->log(
                Auth::id(),
                'file_deleted',
                "Eliminó el archivo ID: {$id}",
                'files'
            );
        }

        if ($request->isAjax()) {
            Response::json($result);
        } else {
            Session::flash(
                $result['success'] ? 'flash.success' : 'flash.error',
                $result['message'] ?? $result['error'] ?? 'Error al eliminar'
            );
            Response::redirect(url('admin/files'));
        }
    }

    /**
     * Subida WYSIWYG (base64 o archivo)
     */
    public function wysiwygUpload(Request $request): void
    {
        CSRF::guard();
        if (!Auth::can('files.upload')) {
            Response::abort(403);
        }

        // Base64 desde editor
        $base64 = $request->post('base64');
        if ($base64) {
            $result = $this->fileManager->uploadBase64(
                $base64,
                Auth::id(),
                ['name' => $request->post('filename', 'editor_image.jpg')]
            );
            Response::json($result);
            return;
        }

        // Archivo normal
        if (isset($_FILES['file'])) {
            $this->handleSingleUpload($request);
            return;
        }

        Response::json(['success' => false, 'error' => 'No se recibió archivo'], 400);
    }

    /**
     * Obtiene mensaje de error de upload
     */
    private function getUploadErrorMessage(int $errorCode): string
    {
        $errors = [
            UPLOAD_ERR_INI_SIZE => 'El archivo excede el tamaño máximo del servidor',
            UPLOAD_ERR_FORM_SIZE => 'El archivo excede el tamaño máximo del formulario',
            UPLOAD_ERR_PARTIAL => 'El archivo fue subido parcialmente',
            UPLOAD_ERR_NO_FILE => 'No se seleccionó ningún archivo',
            UPLOAD_ERR_NO_TMP_DIR => 'No hay directorio temporal disponible',
            UPLOAD_ERR_CANT_WRITE => 'Error al escribir en el disco',
            UPLOAD_ERR_EXTENSION => 'Una extensión de PHP detuvo la subida'
        ];

        return $errors[$errorCode] ?? 'Error desconocido al subir el archivo';
    }
    

/**
 * Búsqueda avanzada con filtros de metadata
 */
public function advancedSearch(Request $request): void
{
    if (!Auth::can('files.view')) {
        Response::abort(403, 'No tienes permisos para ver archivos');
    }

    try {
        $filters = [
            'search' => $request->get('search'),
            'category' => $request->get('category'),
            'metadata_search' => $request->get('metadata_search'),
            'min_width' => $request->get('min_width'),
            'max_width' => $request->get('max_width'),
            'exif_date_from' => $request->get('exif_date_from'),
            'order_by_metadata' => $request->get('order_by_metadata'),
            'order_direction' => $request->get('order_direction'),
        ];

        // ✅ Eliminar filtros vacíos
        $filters = array_filter($filters, function($value) {
            return $value !== null && $value !== '';
        });

        $userId = Auth::id();
        $files = $this->fileManager->searchFiles($filters, $userId);

        Response::render(View::render('files.advanced_search', [
            'files' => $files,
            'filters' => $filters,
            'categories' => $this->fileManager->getCategories()
        ]));
    } catch (Throwable $e) {
        error_log('[FilesController] Error en advancedSearch: ' . $e->getMessage());
        Response::render(View::render('files.advanced_search', [
            'files' => [],
            'filters' => [],
            'categories' => []
        ]));
    }
}

/**
 * Actualiza metadata de un archivo
 */
public function updateMetadata(Request $request, array $params): void
{
    CSRF::guard();
    if (!Auth::can('files.edit')) {
        Response::abort(403, 'No tienes permisos para editar archivos');
    }

    $id = (int)($params['id'] ?? 0);
    if (!$id) {
        if ($request->isAjax()) {
            Response::json(['success' => false, 'error' => 'ID no válido']);
        } else {
            Session::flash('flash.error', 'ID de archivo no válido');
            Response::redirect(url('admin/files'));
        }
        return;
    }

    // ✅ Obtener metadata del request
    $metadata = [
        'description' => $request->post('description'),
        'tags' => $request->post('tags'),
        'custom_fields' => $request->post('custom_fields'),
        'exif' => [
            'make' => $request->post('exif_make'),
            'model' => $request->post('exif_model'),
            'datetime' => $request->post('exif_datetime'),
        ]
    ];

    // ✅ Eliminar campos vacíos
    $metadata = array_filter($metadata, function($value) {
        return $value !== null && $value !== '';
    });

    $result = $this->fileManager->updateMetadata($id, Auth::id(), $metadata);

    if ($request->isAjax()) {
        Response::json($result);
    } else {
        Session::flash(
            $result['success'] ? 'flash.success' : 'flash.error',
            $result['message'] ?? $result['error'] ?? 'Error al actualizar metadata'
        );
        Response::redirect(url("admin/files/{$id}/edit"));
    }
}

/**
 * Exporta archivos con metadata a CSV
 */
public function export(Request $request): void
{
    if (!Auth::can('files.view')) {
        Response::abort(403, 'No tienes permisos para exportar archivos');
    }

    try {
        $userId = Auth::id();
        $files = $this->fileManager->getUserFiles($userId);

        // ✅ Preparar datos para exportación
        $headers = ['ID', 'Nombre', 'Categoría', 'Tamaño', 'Descargas', 'Público', 'Fecha', 'Metadatos'];
        $rows = [];

        foreach ($files as $file) {
            $metadata = json_decode($file['metadata'] ?? '{}', true) ?? [];
            $exif = $metadata['exif'] ?? [];

            $rows[] = [
                $file['id'],
                $file['original_name'],
                $file['category'],
                $this->fileManager->formatFileSize($file['file_size']),
                $file['download_count'],
                $file['is_public'] ? 'Sí' : 'No',
                $file['created_at'],
                json_encode($metadata)
            ];
        }

        // ✅ Generar CSV
        $filename = 'archivos_' . date('Y-m-d_H-i-s') . '.csv';
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');

        $output = fopen('php://output', 'w');
        fputcsv($output, $headers);

        foreach ($rows as $row) {
            fputcsv($output, $row);
        }

        fclose($output);
        exit;

    } catch (Throwable $e) {
        error_log('[FilesController] Error en export: ' . $e->getMessage());
        Session::flash('flash.error', 'Error al exportar archivos');
        Response::redirect(url('admin/files'));
    }
}
}
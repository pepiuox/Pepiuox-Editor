<?php
namespace App\Services;

use App\Core\Database;
use App\Core\Auth;
use App\Core\Security;
use App\Models\FileModel;
use App\Models\MediaModel;
use App\Services\ActivityLoggerService;
use PDO;
use Exception;
use Throwable;

/**
 * Servicio de gestión de archivos y medios
 * 
 * @package App\Services
 */
class FileManagerService
{
    private Database $db;
    private FileModel $fileModel;
    private MediaModel $mediaModel;
    private Security $security;
    private ActivityLoggerService $logger;
    private string $uploadsDir;
    private array $allowedTypes = [
        'image/jpeg', 'image/png', 'image/gif', 'image/webp', 'image/svg+xml',
        'application/pdf', 'application/msword',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'application/vnd.ms-excel',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'application/vnd.ms-powerpoint',
        'application/vnd.openxmlformats-officedocument.presentationml.presentation',
        'text/plain', 'text/csv',
        'application/zip', 'application/x-rar-compressed', 'application/x-tar',
        'application/x-7z-compressed', 'application/gzip',
        'audio/mpeg', 'audio/ogg', 'audio/wav', 'audio/mp4',
        'video/mp4', 'video/mpeg', 'video/webm', 'video/quicktime'
    ];

    private array $maxFileSizes = [
        'image' => 10 * 1024 * 1024,    // 10MB
        'document' => 20 * 1024 * 1024, // 20MB
        'audio' => 50 * 1024 * 1024,    // 50MB
        'video' => 100 * 1024 * 1024,   // 100MB
        'archive' => 50 * 1024 * 1024,  // 50MB
        'default' => 10 * 1024 * 1024   // 10MB
    ];

    public function __construct(
        Database $db,
        FileModel $fileModel,
        MediaModel $mediaModel,
        Security $security,
        ActivityLoggerService $logger
    ) {
        $this->db = $db;
        $this->fileModel = $fileModel;
        $this->mediaModel = $mediaModel;
        $this->security = $security;
        $this->logger = $logger;
        $this->uploadsDir = ROOT_PATH . '/uploads/';
        $this->initializeDirectories();
    }

    /**
     * Inicializa los directorios de uploads
     */
    private function initializeDirectories(): void
    {
        $categories = ['documents', 'images', 'audio', 'video', 'archives', 'others', 'media', 'temp'];
        
        foreach ($categories as $category) {
            $path = $this->uploadsDir . $category . '/';
            if (!is_dir($path)) {
                mkdir($path, 0755, true);
            }
        }
    }

    /**
     * Sube un archivo al sistema
     */
    public function upload(array $file, int $userId, array $options = []): array
    {
        try {
            // Validar archivo
            if ($file['error'] !== UPLOAD_ERR_OK) {
                throw new Exception($this->getUploadErrorMessage($file['error']));
            }

            // Validar tamaño
            $size = $file['size'];
            $category = $options['category'] ?? $this->getFileCategory($file['type']);
            $maxSize = $this->maxFileSizes[$category] ?? $this->maxFileSizes['default'];

            if ($size > $maxSize) {
                throw new Exception(sprintf(
                    'El archivo excede el tamaño máximo permitido para la categoría "%s" (%s)',
                    $category,
                    $this->formatFileSize($maxSize)
                ));
            }

            // Validar tipo
            $mimeType = mime_content_type($file['tmp_name']) ?: $file['type'];
            if (!$this->isAllowedType($mimeType)) {
                throw new Exception('Tipo de archivo no permitido: ' . $mimeType);
            }

            // Validar extensión
            $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            if (!$this->isAllowedExtension($extension)) {
                throw new Exception('Extensión de archivo no permitida: ' . $extension);
            }

            // Verificar duplicados (opcional)
            if (!empty($options['check_duplicate'])) {
                $existing = $this->findDuplicate($file['name'], $userId);
                if ($existing) {
                    return [
                        'success' => true,
                        'id' => $existing['id'],
                        'filename' => $existing['filename'],
                        'original_name' => $existing['original_name'],
                        'path' => $existing['file_path'],
                        'duplicate' => true,
                        'message' => 'El archivo ya existe en el sistema'
                    ];
                }
            }

            // Generar nombre único
            $uniqueName = $this->generateUniqueFilename($extension);
            $categoryDir = $this->getCategoryDirectory($category);
            $uploadPath = $categoryDir . '/' . $uniqueName;

            // Mover archivo
            if (!move_uploaded_file($file['tmp_name'], $uploadPath)) {
                throw new Exception('Error al guardar el archivo en el servidor');
            }

            // Establecer permisos
            chmod($uploadPath, 0644);

            // Procesar imagen si es necesario
            if (str_starts_with($mimeType, 'image/')) {
                $this->processImage($uploadPath, $options);
            }

            // Guardar en base de datos
            $data = [
                'user_id' => $userId,
                'filename' => $uniqueName,
                'original_name' => $file['name'],
                'file_path' => $uploadPath,
                'file_size' => $size,
                'file_type' => $extension,
                'mime_type' => $mimeType,
                'category' => $category,
                'description' => $options['description'] ?? null,
                'is_public' => $options['is_public'] ?? 0,
                'metadata' => $this->extractMetadata($file, $uploadPath)
            ];

            $fileId = $this->fileModel->create($data);

            // Registrar actividad
            $this->logger->logCurrentUser(
                'file_uploaded',
                "Subió el archivo: {$file['name']} ({$this->formatFileSize($size)})"
            );

            return [
                'success' => true,
                'id' => $fileId,
                'filename' => $uniqueName,
                'original_name' => $file['name'],
                'path' => $uploadPath,
                'url' => $this->getFileUrl($uniqueName, $category),
                'category' => $category,
                'size' => $size,
                'size_formatted' => $this->formatFileSize($size),
                'mime_type' => $mimeType,
                'message' => 'Archivo subido correctamente'
            ];

        } catch (Throwable $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'code' => $e->getCode()
            ];
        }
    }

    /**
     * Sube un archivo base64 (desde editor WYSIWYG)
     */
    public function uploadBase64(string $base64, int $userId, array $options = []): array
    {
        try {
            // Decodificar base64
            $base64 = preg_replace('/^data:image\/\w+;base64,/', '', $base64);
            $binaryData = base64_decode($base64, true);

            if ($binaryData === false) {
                throw new Exception('Datos base64 inválidos');
            }

            // Detectar tipo MIME
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mimeType = finfo_buffer($finfo, $binaryData);
            finfo_close($finfo);

            if (!$this->isAllowedType($mimeType)) {
                throw new Exception('Tipo de archivo no permitido: ' . $mimeType);
            }

            // Obtener extensión
            $extension = $this->getExtensionFromMime($mimeType);
            if (!$extension) {
                $extension = 'jpg';
            }

            // Generar nombre único
            $uniqueName = $this->generateUniqueFilename($extension);
            $category = 'images';
            $categoryDir = $this->getCategoryDirectory($category);
            $uploadPath = $categoryDir . '/' . $uniqueName;

            // Guardar archivo
            if (file_put_contents($uploadPath, $binaryData) === false) {
                throw new Exception('Error al guardar el archivo');
            }

            chmod($uploadPath, 0644);

            // Guardar en base de datos
            $data = [
                'user_id' => $userId,
                'filename' => $uniqueName,
                'original_name' => $options['name'] ?? 'base64_image_' . time() . '.' . $extension,
                'file_path' => $uploadPath,
                'file_size' => strlen($binaryData),
                'file_type' => $extension,
                'mime_type' => $mimeType,
                'category' => $category,
                'description' => $options['description'] ?? null,
                'is_public' => $options['is_public'] ?? 1,
                'metadata' => ['source' => 'base64']
            ];

            $fileId = $this->fileModel->create($data);

            return [
                'success' => true,
                'id' => $fileId,
                'filename' => $uniqueName,
                'url' => $this->getFileUrl($uniqueName, $category),
                'message' => 'Archivo subido correctamente'
            ];

        } catch (Throwable $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Obtiene archivos de un usuario
     */
    public function getUserFiles(int $userId, array $filters = []): array
    {
        $query = "SELECT * FROM files WHERE user_id = ? AND deleted_at IS NULL";
        $params = [$userId];

        if (!empty($filters['category'])) {
            $query .= " AND category = ?";
            $params[] = $filters['category'];
        }

        if (!empty($filters['search'])) {
            $query .= " AND (original_name LIKE ? OR description LIKE ?)";
            $searchTerm = '%' . $filters['search'] . '%';
            $params[] = $searchTerm;
            $params[] = $searchTerm;
        }

        if (!empty($filters['is_public'])) {
            $query .= " AND is_public = ?";
            $params[] = $filters['is_public'];
        }

        if (!empty($filters['date_from'])) {
            $query .= " AND created_at >= ?";
            $params[] = $filters['date_from'] . ' 00:00:00';
        }

        if (!empty($filters['date_to'])) {
            $query .= " AND created_at <= ?";
            $params[] = $filters['date_to'] . ' 23:59:59';
        }

        $query .= " ORDER BY created_at DESC";

        return $this->db->fetchAll($query, $params);
    }

    /**
     * Obtiene archivos públicos
     */
    public function getPublicFiles(array $filters = []): array
    {
        $query = "SELECT f.*, u.username as owner_name 
                  FROM files f
                  LEFT JOIN users u ON f.user_id = u.id
                  WHERE f.is_public = 1 AND f.deleted_at IS NULL";
        $params = [];

        if (!empty($filters['category'])) {
            $query .= " AND f.category = ?";
            $params[] = $filters['category'];
        }

        if (!empty($filters['search'])) {
            $query .= " AND (f.original_name LIKE ? OR f.description LIKE ?)";
            $searchTerm = '%' . $filters['search'] . '%';
            $params[] = $searchTerm;
            $params[] = $searchTerm;
        }

        $query .= " ORDER BY f.download_count DESC, f.created_at DESC";

        return $this->db->fetchAll($query, $params);
    }

    /**
     * Obtiene un archivo por ID
     */
    public function getFile(int $fileId, ?int $userId = null): ?array
    {
        $query = "SELECT f.*, u.username as owner_name, u.email as owner_email
                  FROM files f
                  LEFT JOIN users u ON f.user_id = u.id
                  WHERE f.id = ? AND f.deleted_at IS NULL";

        if ($userId !== null) {
            $query .= " AND (f.user_id = ? OR f.is_public = 1)";
            $file = $this->db->fetch($query, [$fileId, $userId]);
        } else {
            $file = $this->db->fetch($query, [$fileId]);
        }

        if ($file) {
            $file['size_formatted'] = $this->formatFileSize($file['file_size']);
            $file['icon'] = $this->getFileIcon($file['file_type']);
            $file['color'] = $this->getCategoryColor($file['category']);
            $file['url'] = $this->getFileUrl($file['filename'], $file['category']);
        }

        return $file;
    }

    /**
     * Elimina un archivo (soft delete)
     */
    public function delete(int $fileId, int $userId): array
    {
        try {
            $file = $this->getFile($fileId, $userId);

            if (!$file) {
                return ['success' => false, 'error' => 'Archivo no encontrado'];
            }

            if ($file['user_id'] !== $userId) {
                return ['success' => false, 'error' => 'No tienes permisos para eliminar este archivo'];
            }

            $deleted = $this->fileModel->softDelete($fileId, $userId);

            if (!$deleted) {
                throw new Exception('Error al eliminar el archivo');
            }

            $this->logger->logCurrentUser(
                'file_deleted',
                "Eliminó el archivo: {$file['original_name']}"
            );

            return [
                'success' => true,
                'message' => 'Archivo eliminado correctamente'
            ];

        } catch (Throwable $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Elimina un archivo permanentemente (solo admin)
     */
    public function hardDelete(int $fileId): array
    {
        try {
            $file = $this->getFile($fileId);

            if (!$file) {
                return ['success' => false, 'error' => 'Archivo no encontrado'];
            }

            // Eliminar archivo físico
            if (is_file($file['file_path'])) {
                unlink($file['file_path']);
            }

            // Eliminar de la base de datos
            $deleted = $this->fileModel->hardDelete($fileId);

            if (!$deleted) {
                throw new Exception('Error al eliminar el archivo de la base de datos');
            }

            $this->logger->logCurrentUser(
                'file_permanently_deleted',
                "Eliminó permanentemente el archivo: {$file['original_name']}"
            );

            return [
                'success' => true,
                'message' => 'Archivo eliminado permanentemente'
            ];

        } catch (Throwable $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Actualiza información de un archivo
     */
    public function update(int $fileId, int $userId, array $data): array
    {
        try {
            $allowedFields = ['description', 'is_public', 'category'];
            $updateData = [];

            foreach ($data as $key => $value) {
                if (in_array($key, $allowedFields)) {
                    $updateData[$key] = $value;
                }
            }

            if (empty($updateData)) {
                return ['success' => false, 'error' => 'No hay campos para actualizar'];
            }

            $updated = $this->fileModel->update($fileId, $userId, $updateData);

            if (!$updated) {
                throw new Exception('Error al actualizar el archivo');
            }

            $this->logger->logCurrentUser(
                'file_updated',
                "Actualizó el archivo ID: {$fileId}"
            );

            return [
                'success' => true,
                'message' => 'Archivo actualizado correctamente'
            ];

        } catch (Throwable $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Incrementa el contador de descargas
     */
    public function incrementDownloadCount(int $fileId): bool
    {
        return $this->fileModel->incrementDownloads($fileId);
    }

    /**
     * Obtiene estadísticas de archivos
     */
    public function getStats(?int $userId = null): array
    {
        $query = "SELECT 
                    COUNT(*) as total_files,
                    SUM(file_size) as total_size,
                    COUNT(CASE WHEN category = 'image' THEN 1 END) as images,
                    COUNT(CASE WHEN category = 'document' THEN 1 END) as documents,
                    COUNT(CASE WHEN category = 'audio' THEN 1 END) as audio,
                    COUNT(CASE WHEN category = 'video' THEN 1 END) as video,
                    COUNT(CASE WHEN category = 'archive' THEN 1 END) as archives,
                    COUNT(CASE WHEN is_public = 1 THEN 1 END) as public_files
                  FROM files
                  WHERE deleted_at IS NULL";

        $params = [];

        if ($userId !== null) {
            $query .= " AND user_id = ?";
            $params[] = $userId;
        }

        $stats = $this->db->fetch($query, $params);

        if ($stats) {
            $stats['total_size_formatted'] = $this->formatFileSize($stats['total_size'] ?? 0);
            $stats['avg_size'] = $stats['total_files'] > 0
                ? $this->formatFileSize($stats['total_size'] / $stats['total_files'])
                : '0 bytes';
        }

        return $stats ?? [
            'total_files' => 0,
            'total_size' => 0,
            'total_size_formatted' => '0 bytes',
            'images' => 0,
            'documents' => 0,
            'audio' => 0,
            'video' => 0,
            'archives' => 0,
            'public_files' => 0,
            'avg_size' => '0 bytes'
        ];
    }

    /**
     * Obtiene estadísticas de descargas
     */
    public function getDownloadStats(int $days = 30): array
    {
        return $this->db->fetchAll(
            "SELECT 
                f.id,
                f.original_name,
                f.download_count,
                f.category,
                DATE(f.created_at) as upload_date
             FROM files f
             WHERE f.download_count > 0
             AND f.created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
             ORDER BY f.download_count DESC
             LIMIT 20",
            [$days]
        );
    }

    /**
     * Busca archivos duplicados
     */
    public function findDuplicate(string $filename, int $userId): ?array
    {
        return $this->db->fetch(
            "SELECT * FROM files 
             WHERE original_name = ? AND user_id = ? AND deleted_at IS NULL
             LIMIT 1",
            [$filename, $userId]
        );
    }

    /**
     * Procesa una imagen (redimensiona, optimiza)
     */
    private function processImage(string $path, array $options): void
    {
        if (!extension_loaded('gd')) {
            return;
        }

        $maxWidth = $options['max_width'] ?? 1920;
        $maxHeight = $options['max_height'] ?? 1080;
        $quality = $options['quality'] ?? 85;

        $imageInfo = getimagesize($path);
        if (!$imageInfo) {
            return;
        }

        [$width, $height] = $imageInfo;

        // Redimensionar si es necesario
        if ($width > $maxWidth || $height > $maxHeight) {
            $ratio = min($maxWidth / $width, $maxHeight / $height);
            $newWidth = (int)($width * $ratio);
            $newHeight = (int)($height * $ratio);

            $src = $this->createImageFromFile($path, $imageInfo[2]);
            if (!$src) {
                return;
            }

            $dst = imagecreatetruecolor($newWidth, $newHeight);

            // Mantener transparencia
            if ($imageInfo[2] === IMAGETYPE_PNG) {
                imagealphablending($dst, false);
                imagesavealpha($dst, true);
                $transparent = imagecolorallocatealpha($dst, 255, 255, 255, 127);
                imagefilledrectangle($dst, 0, 0, $newWidth, $newHeight, $transparent);
            }

            imagecopyresampled($dst, $src, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
            imagedestroy($src);

            // Guardar imagen redimensionada
            $this->saveImage($dst, $path, $imageInfo[2], $quality);
            imagedestroy($dst);
        }
    }

    /**
     * Crea un recurso de imagen desde un archivo
     */
    private function createImageFromFile(string $path, int $type)
    {
        return match ($type) {
            IMAGETYPE_JPEG => imagecreatefromjpeg($path),
            IMAGETYPE_PNG => imagecreatefrompng($path),
            IMAGETYPE_GIF => imagecreatefromgif($path),
            IMAGETYPE_WEBP => imagecreatefromwebp($path),
            default => null
        };
    }

    /**
     * Guarda una imagen
     */
    private function saveImage($resource, string $path, int $type, int $quality): void
    {
        match ($type) {
            IMAGETYPE_JPEG => imagejpeg($resource, $path, $quality),
            IMAGETYPE_PNG => imagepng($resource, $path, 9),
            IMAGETYPE_GIF => imagegif($resource, $path),
            IMAGETYPE_WEBP => imagewebp($resource, $path, $quality),
            default => imagejpeg($resource, $path, $quality)
        };
    }

    /**
     * Extrae metadatos del archivo
     */
    private function extractMetadata(array $file, string $path): array
    {
        $metadata = [
            'uploaded_at' => date('Y-m-d H:i:s'),
            'mime_type' => $file['type'],
            'extension' => strtolower(pathinfo($file['name'], PATHINFO_EXTENSION))
        ];

        // Extraer EXIF para imágenes
        if (str_starts_with($file['type'], 'image/') && extension_loaded('exif')) {
            $exif = @exif_read_data($path);
            if ($exif) {
                $metadata['exif'] = [
                    'width' => $exif['COMPUTED']['Width'] ?? null,
                    'height' => $exif['COMPUTED']['Height'] ?? null,
                    'make' => $exif['Make'] ?? null,
                    'model' => $exif['Model'] ?? null,
                    'datetime' => $exif['DateTime'] ?? null,
                ];
            }
        }

        return $metadata;
    }

    /**
     * Genera un nombre de archivo único
     */
    private function generateUniqueFilename(string $extension): string
    {
        return uniqid() . '_' . time() . '.' . $extension;
    }

    /**
     * Obtiene el directorio de una categoría
     */
    private function getCategoryDirectory(string $category): string
    {
        $dirs = [
            'image' => 'images',
            'document' => 'documents',
            'audio' => 'audio',
            'video' => 'video',
            'archive' => 'archives',
            'other' => 'others'
        ];

        $dir = $dirs[$category] ?? 'others';
        $path = $this->uploadsDir . $dir . '/';

        if (!is_dir($path)) {
            mkdir($path, 0755, true);
        }

        return $path;
    }

    /**
     * Obtiene la URL de un archivo
     */
    private function getFileUrl(string $filename, string $category): string
    {
        $dirs = [
            'image' => 'images',
            'document' => 'documents',
            'audio' => 'audio',
            'video' => 'video',
            'archive' => 'archives',
            'other' => 'others'
        ];

        $dir = $dirs[$category] ?? 'others';
        return rtrim(BASE_URL, '/') . '/uploads/' . $dir . '/' . $filename;
    }

    /**
     * Determina la categoría de un archivo
     */
    private function getFileCategory(string $mimeType): string
    {
        if (str_starts_with($mimeType, 'image/')) {
            return 'image';
        } elseif (str_starts_with($mimeType, 'audio/')) {
            return 'audio';
        } elseif (str_starts_with($mimeType, 'video/')) {
            return 'video';
        } elseif (in_array($mimeType, [
            'application/pdf',
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'application/vnd.ms-excel',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'application/vnd.ms-powerpoint',
            'application/vnd.openxmlformats-officedocument.presentationml.presentation',
            'text/plain',
            'text/csv'
        ])) {
            return 'document';
        } elseif (in_array($mimeType, [
            'application/zip',
            'application/x-rar-compressed',
            'application/x-tar',
            'application/x-7z-compressed',
            'application/gzip'
        ])) {
            return 'archive';
        } else {
            return 'other';
        }
    }

    /**
     * Verifica si un tipo MIME está permitido
     */
    private function isAllowedType(string $mimeType): bool
    {
        return in_array($mimeType, $this->allowedTypes);
    }

    /**
     * Verifica si una extensión está permitida
     */
    private function isAllowedExtension(string $extension): bool
    {
        $allowedExtensions = [
            'jpg', 'jpeg', 'png', 'gif', 'webp', 'svg',
            'pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx',
            'txt', 'csv',
            'zip', 'rar', 'tar', 'gz', '7z',
            'mp3', 'wav', 'ogg', 'm4a',
            'mp4', 'mpeg', 'webm', 'mov', 'avi'
        ];

        return in_array($extension, $allowedExtensions);
    }

    /**
     * Obtiene la extensión desde un tipo MIME
     */
    private function getExtensionFromMime(string $mimeType): ?string
    {
        $map = [
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/gif' => 'gif',
            'image/webp' => 'webp',
            'image/svg+xml' => 'svg',
            'application/pdf' => 'pdf',
            'text/plain' => 'txt',
            'audio/mpeg' => 'mp3',
            'video/mp4' => 'mp4',
        ];

        return $map[$mimeType] ?? null;
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
     * Formatea el tamaño de un archivo
     */
    public function formatFileSize(int $bytes): string
    {
        if ($bytes >= 1073741824) {
            return number_format($bytes / 1073741824, 2) . ' GB';
        } elseif ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 2) . ' MB';
        } elseif ($bytes >= 1024) {
            return number_format($bytes / 1024, 2) . ' KB';
        } elseif ($bytes > 1) {
            return $bytes . ' bytes';
        } elseif ($bytes === 1) {
            return '1 byte';
        } else {
            return '0 bytes';
        }
    }

    /**
     * Obtiene el icono según tipo de archivo
     */
    public function getFileIcon(string $fileType): string
    {
        $icons = [
            'pdf' => 'bi bi-file-pdf',
            'doc' => 'bi bi-file-word',
            'docx' => 'bi bi-file-word',
            'xls' => 'bi bi-file-excel',
            'xlsx' => 'bi bi-file-excel',
            'ppt' => 'bi bi-file-ppt',
            'pptx' => 'bi bi-file-ppt',
            'txt' => 'bi bi-file-text',
            'csv' => 'bi bi-file-spreadsheet',
            'jpg' => 'bi bi-file-image',
            'jpeg' => 'bi bi-file-image',
            'png' => 'bi bi-file-image',
            'gif' => 'bi bi-file-image',
            'webp' => 'bi bi-file-image',
            'svg' => 'bi bi-file-image',
            'zip' => 'bi bi-file-zip',
            'rar' => 'bi bi-file-zip',
            'tar' => 'bi bi-file-zip',
            'gz' => 'bi bi-file-zip',
            '7z' => 'bi bi-file-zip',
            'mp3' => 'bi bi-file-music',
            'wav' => 'bi bi-file-music',
            'ogg' => 'bi bi-file-music',
            'mp4' => 'bi bi-file-play',
            'webm' => 'bi bi-file-play',
            'mov' => 'bi bi-file-play',
            'avi' => 'bi bi-file-play',
            'default' => 'bi bi-file'
        ];

        return $icons[strtolower($fileType)] ?? $icons['default'];
    }

    /**
     * Obtiene el color según categoría
     */
    public function getCategoryColor(string $category): string
    {
        $colors = [
            'document' => 'primary',
            'image' => 'success',
            'audio' => 'info',
            'video' => 'warning',
            'archive' => 'secondary',
            'other' => 'dark'
        ];

        return $colors[$category] ?? 'secondary';
    }

    /**
     * Obtiene todas las categorías disponibles
     */
    public function getCategories(): array
    {
        return ['document', 'image', 'audio', 'video', 'archive', 'other'];
    }

    /**
     * Limpia archivos temporales
     */
    public function cleanTempFiles(): int
    {
        $tempDir = $this->uploadsDir . 'temp/';
        if (!is_dir($tempDir)) {
            return 0;
        }

        $count = 0;
        $files = glob($tempDir . '*');

        foreach ($files as $file) {
            if (is_file($file) && (time() - filemtime($file)) > 86400) { // 24 horas
                unlink($file);
                $count++;
            }
        }

        return $count;
    }
}
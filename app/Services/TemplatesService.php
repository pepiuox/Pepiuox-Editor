<?php
namespace App\Services;

use App\Core\{Database, Auth};
use Throwable;

/**
 * Servicio de gestión de templates
 */
class TemplatesService
{
    private Database $db;
    private AuditService $auditService;
    private string $table = 'templates';
    private string $versionsTable = 'template_versions';

    public function __construct(Database $db, AuditService $auditService)
    {
        $this->db = $db;
        $this->auditService = $auditService;
    }

    public function getAll(): array
    {
        try {
            return $this->db->fetchAll("SELECT * FROM {$this->table} ORDER BY name ASC");
        } catch (Throwable $e) {
            return [];
        }
    }

    public function find(int $id): ?array
    {
        try {
            return $this->db->fetch("SELECT * FROM {$this->table} WHERE id = ?", [$id]);
        } catch (Throwable $e) {
            return null;
        }
    }

    public function create(array $data): array
    {
        try {
            if (empty($data['name'])) {
                throw new \Exception('El nombre es obligatorio');
            }

            if (!empty($data['is_default'])) {
                $this->db->execute("UPDATE {$this->table} SET is_default = 0");
            }

            $id = $this->db->insert(
                "INSERT INTO {$this->table}
                 (name, description, content_html, content_css, content_js, content_php,
                  grapesjs_data, is_system_template, is_active, is_default, created_by)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
                [
                    $data['name'],
                    $data['description'] ?? '',
                    $data['content_html'] ?? '',
                    $data['content_css'] ?? '',
                    $data['content_js'] ?? '',
                    $data['content_php'] ?? '',
                    $data['grapesjs_data'] ?? null,
                    $data['is_system_template'] ?? 0,
                    $data['is_active'] ?? 1,
                    $data['is_default'] ?? 0,
                    Auth::id(),
                ]
            );

            $this->auditService->log(Auth::id(), 'template_created', "Template creado: {$data['name']}", 'templates');

            return ['success' => true, 'id' => $id, 'message' => 'Template creado'];
        } catch (Throwable $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    public function update(int $id, array $data): array
    {
        try {
            $template = $this->find($id);
            if (!$template) throw new \Exception('Template no encontrado');

            if (!empty($data['is_default'])) {
                $this->db->execute("UPDATE {$this->table} SET is_default = 0");
            }

            $fields = [];
            $params = [];
            $allowed = ['name','description','content_html','content_css','content_js','content_php','grapesjs_data','is_active','is_default'];

            foreach ($allowed as $field) {
                if (array_key_exists($field, $data)) {
                    $fields[] = "`$field` = ?";
                    $params[] = $data[$field];
                }
            }

            if (empty($fields)) throw new \Exception('No hay campos para actualizar');

            $params[] = $id;
            $this->db->execute("UPDATE {$this->table} SET " . implode(', ', $fields) . " WHERE id = ?", $params);

            $this->auditService->log(Auth::id(), 'template_updated', "Template actualizado ID: {$id}", 'templates');

            return ['success' => true, 'message' => 'Template actualizado'];
        } catch (Throwable $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    public function delete(int $id): array
    {
        try {
            $template = $this->find($id);
            if (!$template) throw new \Exception('Template no encontrado');
            if ((int)$template['is_system_template'] === 1) throw new \Exception('No se puede eliminar template del sistema');

            $this->db->execute("DELETE FROM {$this->versionsTable} WHERE template_id = ?", [$id]);
            $this->db->execute("DELETE FROM {$this->table} WHERE id = ?", [$id]);

            $this->auditService->log(Auth::id(), 'template_deleted', "Template eliminado: {$template['name']}", 'templates');

            return ['success' => true, 'message' => 'Template eliminado'];
        } catch (Throwable $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    public function duplicate(int $id): array
    {
        try {
            $template = $this->find($id);
            if (!$template) throw new \Exception('Template no encontrado');

            $newId = $this->db->insert(
                "INSERT INTO {$this->table}
                 (name, description, content_html, content_css, content_js, content_php,
                  grapesjs_data, is_system_template, is_active, is_default, created_by)
                 VALUES (?, ?, ?, ?, ?, ?, ?, 0, 0, 0, ?)",
                [
                    $template['name'] . ' (copia)',
                    $template['description'],
                    $template['content_html'],
                    $template['content_css'],
                    $template['content_js'],
                    $template['content_php'],
                    $template['grapesjs_data'],
                    Auth::id(),
                ]
            );

            return ['success' => true, 'id' => $newId, 'message' => 'Template duplicado'];
        } catch (Throwable $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    // ═══ VERSIONES ═══

    public function getVersions(int $templateId): array
    {
        try {
            return $this->db->fetchAll(
                // `template_versions` no tiene columna created_by: el JOIN con
                // users lanzaba «Unknown column» y este método devolvía [] en
                // silencio, así que la lista de versiones siempre salía vacía.
                "SELECT tv.*
                 FROM {$this->versionsTable} tv
                 WHERE tv.template_id = ?
                 ORDER BY tv.created_at DESC",
                [$templateId]
            );
        } catch (Throwable $e) {
            return [];
        }
    }

    public function findVersion(int $versionId, int $templateId): ?array
    {
        try {
            return $this->db->fetch(
                "SELECT * FROM {$this->versionsTable} WHERE id = ? AND template_id = ?",
                [$versionId, $templateId]
            );
        } catch (Throwable $e) {
            return null;
        }
    }

    public function createVersion(int $templateId, array $data = []): array
    {
        try {
            $template = $this->find($templateId);
            if (!$template) throw new \Exception('Template no encontrado');

            $versionData = [
                'content_html' => $data['content_html'] ?? $template['content_html'],
                'content_css'  => $data['content_css'] ?? $template['content_css'],
                'content_js'   => $data['content_js'] ?? $template['content_js'],
                'content_php'  => $data['content_php'] ?? $template['content_php'],
            ];

            $versionId = $this->db->insert(
                // Sin created_by: la tabla template_versions no tiene esa
                // columna, el INSERT fallaba con «Unknown column» y por eso
                // «Guardar versión» no guardaba nunca nada.
                "INSERT INTO {$this->versionsTable}
                 (template_id, content_html, content_css, content_js, content_php, created_at)
                 VALUES (?, ?, ?, ?, ?, NOW())",
                [$templateId, $versionData['content_html'], $versionData['content_css'],
                 $versionData['content_js'], $versionData['content_php']]
            );

            return ['success' => true, 'version_id' => $versionId, 'message' => 'Versión creada'];
        } catch (Throwable $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    public function restoreVersion(int $templateId, int $versionId): array
    {
        try {
            $version = $this->findVersion($versionId, $templateId);
            if (!$version) throw new \Exception('Versión no encontrada');

            // Guardar estado actual como nueva versión
            $template = $this->find($templateId);
            if ($template) $this->createVersion($templateId);

            $this->db->execute(
                "UPDATE {$this->table} SET
                    content_html = ?, content_css = ?, content_js = ?, content_php = ?,
                    grapesjs_data = NULL, created_by = ?
                 WHERE id = ?",
                [$version['content_html'], $version['content_css'],
                 $version['content_js'], $version['content_php'], Auth::id(), $templateId]
            );

            return ['success' => true, 'message' => 'Versión restaurada'];
        } catch (Throwable $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    public function deleteVersion(int $templateId, int $versionId): array
    {
        try {
            $this->db->execute(
                "DELETE FROM {$this->versionsTable} WHERE id = ? AND template_id = ?",
                [$versionId, $templateId]
            );
            return ['success' => true, 'message' => 'Versión eliminada'];
        } catch (Throwable $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    public function hasContentChanged(int $templateId, array $newData): bool
    {
        $template = $this->find($templateId);
        if (!$template) return true;

        return (
            $template['content_html'] !== ($newData['content_html'] ?? '') ||
            $template['content_css'] !== ($newData['content_css'] ?? '') ||
            $template['content_js'] !== ($newData['content_js'] ?? '') ||
            $template['content_php'] !== ($newData['content_php'] ?? '')
        );
    }
}
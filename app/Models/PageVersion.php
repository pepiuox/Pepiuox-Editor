<?php
namespace App\Models;

use App\Core\{Database, Auth};

/**
 * Modelo estático para versiones de páginas
 */
class PageVersion
{
    public static function forPage(int $pageId): array
    {
        return Database::fetchAll(
            "SELECT v.*, u.username FROM page_versions v 
             LEFT JOIN users u ON u.id = v.created_by 
             WHERE v.page_id = ? 
             ORDER BY v.version_number DESC",
            [$pageId]
        );
    }

    public static function find(int $id): ?array
    {
        return Database::fetch("SELECT * FROM page_versions WHERE id = ?", [$id]);
    }

    public static function create(array $data): int
    {
        $last = Database::fetch(
            "SELECT MAX(version_number) AS m FROM page_versions WHERE page_id = ?",
            [$data['page_id']]
        );
        $n = ((int)($last['m'] ?? 0)) + 1;
        
        return Database::insert(
            "INSERT INTO page_versions (
                page_id, version_number, title, content_html, content_css, 
                content_js, content_php, created_by, note
            ) VALUES (?,?,?,?,?,?,?,?,?)",
            [
                $data['page_id'],
                $n,
                $data['title'],
                $data['content_html'] ?? '',
                $data['content_css'] ?? '',
                $data['content_js'] ?? '',
                $data['content_php'] ?? '',
                Auth::id(),
                $data['note'] ?? null,
            ]
        );
    }

    /**
     * Restaura una versión: crea una nueva versión con los datos antiguos
     */
    public static function restore(int $versionId): bool
    {
        $v = self::find($versionId);
        if (!$v) return false;

        $page = Page::find((int)$v['page_id']);
        if (!$page) return false;

        // Actualizar página con datos de la versión
        Page::update((int)$v['page_id'], [
            'title'        => $v['title'],
            'content_html' => $v['content_html'],
            'content_css'  => $v['content_css'],
            'content_js'   => $v['content_js'],
            'content_php'  => $v['content_php'],
        ]);

        // Crear nueva versión marcando la restauración
        self::create([
            'page_id'      => (int)$v['page_id'],
            'title'        => $v['title'] . ' (restaurada)',
            'content_html' => $v['content_html'],
            'content_css'  => $v['content_css'],
            'content_js'   => $v['content_js'],
            'content_php'  => $v['content_php'],
            'note'         => 'Restauración desde versión #' . $v['version_number'],
        ]);

        return true;
    }

    public static function delete(int $id): int
    {
        return Database::execute("DELETE FROM page_versions WHERE id = ?", [$id]);
    }
}
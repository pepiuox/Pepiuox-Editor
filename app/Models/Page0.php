<?php
namespace App\Models;

use App\Core\Database;

class Page
{
    public static function find(int $id): ?array
    {
        return Database::fetch("SELECT * FROM pages WHERE id = ?", [$id]);
    }

    public static function findBySlug(string $slug): ?array
    {
        return Database::fetch(
            "SELECT p.*, u.username AS author_name, t.name AS template_name
               FROM pages p
               LEFT JOIN users u ON u.id = p.user_id
               LEFT JOIN templates t ON t.id = p.template_id
              WHERE p.slug = ?",
            [$slug]
        );
    }

    public static function all(?string $status = null): array
    {
        $sql = "SELECT p.*, u.username AS author_name
                  FROM pages p
                  LEFT JOIN users u ON u.id = p.user_id";
        $params = [];
        if ($status) { $sql .= " WHERE p.status = ?"; $params[] = $status; }
        $sql .= " ORDER BY p.updated_at DESC";
        return Database::fetchAll($sql, $params);
    }

    public static function create(array $data): int
    {
        return Database::insert(
            "INSERT INTO pages (user_id, template_id, parent_id, title, slug, description, h1,
                                content_html, content_css, content_js, content_php, grapesjs_data,
                                startpage, is_published, status, publish_at)
             VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)",
            [
                $data['user_id'],
                $data['template_id'] ?? null,
                $data['parent_id'] ?? null,
                $data['title'],
                $data['slug'],
                $data['description'] ?? '',
                $data['h1'] ?? null,
                $data['content_html'] ?? '',
                $data['content_css'] ?? '',
                $data['content_js'] ?? '',
                $data['content_php'] ?? '',
                $data['grapesjs_data'] ?? null,
                $data['startpage'] ?? 0,
                $data['is_published'] ?? 0,
                $data['status'] ?? 'draft',
                $data['publish_at'] ?? null,
            ]
        );
    }

    public static function update(int $id, array $data): int
    {
        $fields = [];
        $params = [];
        $allowed = ['template_id','parent_id','title','slug','description','h1',
                    'content_html','content_css','content_js','content_php','grapesjs_data',
                    'startpage','is_published','status','publish_at'];
        foreach ($allowed as $f) {
            if (array_key_exists($f, $data)) {
                $fields[] = "`$f` = ?";
                $params[] = $data[$f];
            }
        }
        if (!$fields) return 0;
        $params[] = $id;
        return Database::execute("UPDATE pages SET " . implode(',', $fields) . " WHERE id = ?", $params);
    }

    public static function delete(int $id): int
    {
        return Database::execute("DELETE FROM pages WHERE id = ?", [$id]);
    }

    /** Página de inicio (startpage=1 y publicada) */
    public static function home(): ?array
    {
        return Database::fetch(
            "SELECT * FROM pages WHERE startpage = 1 AND status = 'published' LIMIT 1"
        );
    }

    /** Páginas publicadas para sitemap */
    public static function publishedForSitemap(): array
    {
        return Database::fetchAll(
            "SELECT slug, updated_at FROM pages WHERE status='published' AND is_published=1 ORDER BY updated_at DESC"
        );
    }

    /** Páginas que usan un template concreto (para invalidación de cache) */
    public static function usingTemplate(int $templateId): array
    {
        return Database::fetchAll(
            "SELECT id, slug FROM pages WHERE template_id = ?",
            [$templateId]
        );
    }

    /** Genera un slug único */
    public static function uniqueSlug(string $base, ?int $ignoreId = null): string
    {
        $slug = self::slugify($base);
        $original = $slug;
        $i = 1;
        while (true) {
            $sql = "SELECT COUNT(*) AS c FROM pages WHERE slug = ?";
            $p = [$slug];
            if ($ignoreId) { $sql .= " AND id <> ?"; $p[] = $ignoreId; }
            $row = Database::fetch($sql, $p);
            if ((int)$row['c'] === 0) return $slug;
            $slug = $original . '-' . (++$i);
        }
    }

    public static function slugify(string $text): string
    {
        $text = mb_strtolower($text);
        $text = transliterator_transliterate('Any-Latin; Latin-ASCII; Lower()', $text) ?: $text;
        $text = preg_replace('/[^a-z0-9\s-]/', '', $text);
        $text = preg_replace('/[\s-]+/', '-', $text);
        return trim($text, '-') ?: 'page';
    }
}
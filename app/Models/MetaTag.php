<?php
namespace App\Models;
use App\Core\Database;

class MetaTag
{
    public static function forPage(int $pageId): array
    {
        $rows = Database::fetchAll(
            "SELECT tag_type, tag_name, tag_value FROM meta_tags WHERE page_id = ? AND is_active = 1",
            [$pageId]
        );
        $out = ['basic'=>[],'og'=>[],'twitter'=>[]];
        foreach ($rows as $r) $out[$r['tag_type']][$r['tag_name']] = $r['tag_value'];
        return $out;
    }

    public static function syncForPage(int $pageId, array $data): void
    {
        Database::execute("DELETE FROM meta_tags WHERE page_id = ?", [$pageId]);
        foreach (['basic','og','twitter'] as $type) {
            if (empty($data[$type])) continue;
            foreach ($data[$type] as $name => $value) {
                if ($value === null || $value === '') continue;
                Database::insert(
                    "INSERT INTO meta_tags (page_id, tag_type, tag_name, tag_value) VALUES (?,?,?,?)",
                    [$pageId, $type, $name, $value]
                );
            }
        }
    }

    /** Validaciones SEO */
    public static function validate(array $data): array
    {
        $errors = [];
        if (!empty($data['basic']['title']) && mb_strlen($data['basic']['title']) > 70) $errors[] = 'Meta title demasiado largo (>70)';
        if (!empty($data['basic']['description']) && mb_strlen($data['basic']['description']) > 160) $errors[] = 'Meta description demasiado larga (>160)';
        if (empty($data['basic']['description'])) $errors[] = 'Falta meta description';
        if (!empty($data['basic']['canonical']) && !filter_var($data['basic']['canonical'], FILTER_VALIDATE_URL)) $errors[] = 'Canonical URL inválida';
        return $errors;
    }
}
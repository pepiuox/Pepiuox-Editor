<?php
namespace App\Services;
use App\Core\Database;

class MenuService
{
    public static function allActive(): array
    {
        $items = Database::fetchAll(
            "SELECT * FROM page_menu WHERE is_published = 1 ORDER BY `order` ASC, sort ASC"
        );
        return self::buildTree($items);
    }

    public static function byGroup(string $group): array
    {
        $items = Database::fetchAll(
            "SELECT * FROM page_menu WHERE menu_group = ? ORDER BY `order` ASC, sort ASC",
            [$group]
        );
        return self::buildTree($items);
    }

    public static function allFlat(string $group = 'main'): array
    {
        return Database::fetchAll(
            "SELECT * FROM page_menu WHERE menu_group = ? ORDER BY `order` ASC, sort ASC",
            [$group]
        );
    }

    private static function buildTree(array $items, ?int $parent = null): array
    {
        $tree = [];
        foreach ($items as $it) {
            if (($it['parent_id'] ?? null) == $parent) {
                $it['children'] = self::buildTree($items, (int)$it['id']);
                $tree[] = $it;
            }
        }
        return $tree;
    }

    public static function create(array $data): int
    {
        return Database::insert(
            "INSERT INTO page_menu (page_id, title, slug, url, icon, `order`, sort, parent_id, level, is_published, status, target, menu_group)
             VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?)",
            [
                $data['page_id'] ?? null,
                $data['title'],
                $data['slug'] ?? \App\Models\Page::slugify($data['title']),
                $data['url'] ?? null,
                $data['icon'] ?? null,
                $data['order'] ?? 0,
                $data['sort'] ?? 0,
                $data['parent_id'] ?? null,
                $data['level'] ?? 0,
                $data['is_published'] ?? 1,
                $data['status'] ?? 'published',
                $data['target'] ?? '_self',
                $data['menu_group'] ?? 'main',
            ]
        );
    }

    public static function update(int $id, array $data): int
    {
        $fields = []; $params = [];
        foreach (['page_id','title','slug','url','icon','order','sort','parent_id','level','is_published','status','target','menu_group'] as $f) {
            if (array_key_exists($f, $data)) { $fields[] = "`$f` = ?"; $params[] = $data[$f]; }
        }
        if (!$fields) return 0;
        $params[] = $id;
        return Database::execute("UPDATE page_menu SET " . implode(',', $fields) . " WHERE id = ?", $params);
    }

    public static function delete(int $id): int
    {
        // Reasignar hijos al padre
        $item = Database::fetch("SELECT parent_id FROM page_menu WHERE id = ?", [$id]);
        if ($item) {
            Database::execute("UPDATE page_menu SET parent_id = ? WHERE parent_id = ?", [$item['parent_id'], $id]);
        }
        return Database::execute("DELETE FROM page_menu WHERE id = ?", [$id]);
    }

    /** Reordenar desde drag&drop: recibe array de {id, parent_id, order, level} */
    public static function reorder(array $items): void
    {
        Database::begin();
        try {
            foreach ($items as $i => $it) {
                Database::execute(
                    "UPDATE page_menu SET parent_id = ?, `order` = ?, `level` = ? WHERE id = ?",
                    [$it['parent_id'] ?? null, $i, $it['level'] ?? 0, $it['id']]
                );
            }
            Database::commit();
        } catch (\Throwable $e) {
            Database::rollback();
            throw $e;
        }
    }

    /** Renderiza un menú como HTML Bootstrap */
    public static function render(string $group = 'main'): string
    {
        $tree = self::byGroup($group);
        if (!$tree) return '';
        return '<ul class="navbar-nav">' . self::renderItems($tree) . '</ul>';
    }

    private static function renderItems(array $items): string
    {
        $out = '';
        foreach ($items as $it) {
            $url = $it['url'] ?? url($it['slug']);
            $hasChildren = !empty($it['children']);
            $liClass = $hasChildren ? 'nav-item dropdown' : 'nav-item';
            $aClass = $hasChildren ? 'nav-link dropdown-toggle' : 'nav-link';
            $out .= "<li class=\"$liClass\">";
            $attrs = $hasChildren ? 'role="button" data-bs-toggle="dropdown" aria-expanded="false"' : '';
            $target = $it['target'] ?? '_self';
            $out .= "<a class=\"$aClass\" href=\"".e($url)."\" target=\"$target\" $attrs>".e($it['title'])."</a>";
            if ($hasChildren) {
                $out .= '<ul class="dropdown-menu">' . self::renderDropdown($it['children']) . '</ul>';
            }
            $out .= '</li>';
        }
        return $out;
    }

    private static function renderDropdown(array $items): string
    {
        $out = '';
        foreach ($items as $it) {
            $url = $it['url'] ?? url($it['slug']);
            if (!empty($it['children'])) {
                $out .= '<li><h6 class="dropdown-header">'.e($it['title']).'</h6></li>';
                $out .= self::renderDropdown($it['children']);
            } else {
                $out .= '<li><a class="dropdown-item" href="'.e($url).'">'.e($it['title']).'</a></li>';
            }
        }
        return $out;
    }
}
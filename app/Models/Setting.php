<?php
namespace App\Models;
use App\Core\Database;

class Setting
{
    public static function all(): array
    {
        $rows = Database::fetchAll("SELECT `group`, `key`, `value`, `type` FROM settings ORDER BY `group`, `key`");
        $grouped = [];
        foreach ($rows as $row) {
            $grouped[$row['group']][$row['key']] = [
                'value' => $row['value'],
                'type'  => $row['type']
            ];
        }
        return $grouped;
    }

    public static function updateBatch(array $data): void
    {
        Database::begin();
        try {
            foreach ($data as $group => $settings) {
                foreach ($settings as $key => $item) {
                    Database::execute(
                        "INSERT INTO settings (`group`, `key`, `value`, `type`) VALUES (?, ?, ?, ?)
                         ON DUPLICATE KEY UPDATE `value` = VALUES(`value`), `type` = VALUES(`type`)",
                        [$group, $key, $item['value'], $item['type']]
                    );
                }
            }
            Database::commit();
        } catch (\Throwable $e) {
            Database::rollback();
            throw $e;
        }
    }
}
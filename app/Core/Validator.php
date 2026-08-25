<?php
namespace App\Core;

class Validator
{
    private array $errors = [];
    private array $data;

    public function __construct(array $data) { $this->data = $data; }

    public function validate(array $rules): bool
    {
        $this->errors = [];
        foreach ($rules as $field => $ruleSet) {
            $value = $this->data[$field] ?? null;
            $label = $ruleSet['label'] ?? $field;
            foreach ($ruleSet['rules'] as $rule) {
                $this->apply($field, $label, $value, $rule, $ruleSet['params'] ?? []);
            }
        }
        return empty($this->errors);
    }

    private function apply(string $field, string $label, $value, string $rule, array $params): void
    {
        switch ($rule) {
            case 'required':
                if ($value === null || $value === '') $this->errors[$field][] = "$label es obligatorio.";
                break;
            case 'email':
                if ($value !== null && $value !== '' && !filter_var($value, FILTER_VALIDATE_EMAIL))
                    $this->errors[$field][] = "$label no es un email válido.";
                break;
            case 'min':
                $min = $params[0] ?? 0;
                if (is_string($value) && mb_strlen($value) < $min)
                    $this->errors[$field][] = "$label debe tener al menos $min caracteres.";
                break;
            case 'max':
                $max = $params[0] ?? 255;
                if (is_string($value) && mb_strlen($value) > $max)
                    $this->errors[$field][] = "$label no puede superar $max caracteres.";
                break;
            case 'slug':
                if ($value !== null && $value !== '' && !preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $value))
                    $this->errors[$field][] = "$label solo admite minúsculas, números y guiones.";
                break;
            case 'unique':
                [$table, $column, $ignoreId] = [$params[0], $params[1] ?? $field, $params[2] ?? null];
                $sql = "SELECT COUNT(*) AS c FROM `$table` WHERE `$column` = ?";
                $p = [$value];
                if ($ignoreId !== null) { $sql .= " AND id <> ?"; $p[] = $ignoreId; }
                $row = Database::fetch($sql, $p);
                if ($row && (int)$row['c'] > 0) $this->errors[$field][] = "$label ya existe.";
                break;
        }
    }

    public function errors(): array { return $this->errors; }
    public function first(string $field): ?string { return $this->errors[$field][0] ?? null; }
}
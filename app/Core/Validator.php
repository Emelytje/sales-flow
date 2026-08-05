<?php
/**
 * Rule-based input validator.
 *
 * Rules: required, email, min:n, max:n, numeric, integer, in:a,b,c,
 * confirmed, date, url, boolean, unique:table,column.
 */

declare(strict_types=1);

namespace App\Core;

final class Validator
{
    /** @var array<string, array<int, string>> */
    private array $errors = [];

    /**
     * @param array<string, mixed> $data
     * @param array<string, string> $rules  field => 'rule1|rule2:param'
     * @param array<string, string> $labels field => human label
     */
    public function __construct(
        private array $data,
        private array $rules,
        private array $labels = []
    ) {
    }

    public function passes(): bool
    {
        foreach ($this->rules as $field => $ruleSet) {
            foreach (explode('|', $ruleSet) as $rule) {
                [$name, $param] = array_pad(explode(':', $rule, 2), 2, null);
                $this->applyRule($field, $name, $param);
            }
        }
        return $this->errors === [];
    }

    public function fails(): bool
    {
        return !$this->passes();
    }

    /** @return array<string, array<int, string>> */
    public function errors(): array
    {
        return $this->errors;
    }

    public function firstError(): ?string
    {
        foreach ($this->errors as $messages) {
            return $messages[0] ?? null;
        }
        return null;
    }

    private function label(string $field): string
    {
        return $this->labels[$field] ?? ucfirst(str_replace('_', ' ', $field));
    }

    private function value(string $field): mixed
    {
        return $this->data[$field] ?? null;
    }

    private function addError(string $field, string $message): void
    {
        $this->errors[$field][] = $message;
    }

    private function applyRule(string $field, string $name, ?string $param): void
    {
        $value = $this->value($field);
        $label = $this->label($field);

        switch ($name) {
            case 'required':
                if ($value === null || $value === '' || (is_array($value) && $value === [])) {
                    $this->addError($field, "{$label} is verplicht.");
                }
                break;

            case 'email':
                if ($value !== null && $value !== '' && !filter_var((string) $value, FILTER_VALIDATE_EMAIL)) {
                    $this->addError($field, "{$label} moet een geldig e-mailadres zijn.");
                }
                break;

            case 'min':
                if ($value !== null && $value !== '' && mb_strlen((string) $value) < (int) $param) {
                    $this->addError($field, "{$label} moet minstens {$param} tekens bevatten.");
                }
                break;

            case 'max':
                if ($value !== null && mb_strlen((string) $value) > (int) $param) {
                    $this->addError($field, "{$label} mag maximaal {$param} tekens bevatten.");
                }
                break;

            case 'numeric':
                if ($value !== null && $value !== '' && !is_numeric($value)) {
                    $this->addError($field, "{$label} moet een getal zijn.");
                }
                break;

            case 'integer':
                if ($value !== null && $value !== '' && filter_var($value, FILTER_VALIDATE_INT) === false) {
                    $this->addError($field, "{$label} moet een geheel getal zijn.");
                }
                break;

            case 'boolean':
                if ($value !== null && !in_array((string) $value, ['0', '1', 'true', 'false', ''], true)) {
                    $this->addError($field, "{$label} is ongeldig.");
                }
                break;

            case 'in':
                $allowed = explode(',', (string) $param);
                if ($value !== null && $value !== '' && !in_array((string) $value, $allowed, true)) {
                    $this->addError($field, "{$label} bevat een ongeldige waarde.");
                }
                break;

            case 'date':
                if ($value !== null && $value !== '' && strtotime((string) $value) === false) {
                    $this->addError($field, "{$label} moet een geldige datum zijn.");
                }
                break;

            case 'url':
                if ($value !== null && $value !== '' && !filter_var((string) $value, FILTER_VALIDATE_URL)) {
                    $this->addError($field, "{$label} moet een geldige URL zijn.");
                }
                break;

            case 'confirmed':
                if ($value !== ($this->data[$field . '_confirmation'] ?? null)) {
                    $this->addError($field, "{$label} komt niet overeen met de bevestiging.");
                }
                break;

            case 'unique':
                [$table, $column] = array_pad(explode(',', (string) $param), 2, $field);
                if ($value !== null && $value !== '') {
                    $count = Database::instance()->scalar(
                        "SELECT COUNT(*) FROM `{$table}` WHERE `{$column}` = ?",
                        [$value]
                    );
                    if ((int) $count > 0) {
                        $this->addError($field, "{$label} is al in gebruik.");
                    }
                }
                break;
        }
    }
}

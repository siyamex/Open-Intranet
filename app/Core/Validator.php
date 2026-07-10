<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Minimal validator: (new Validator($data, ['email' => 'required|email']))->fails().
 * Rules: required, email, url, numeric, int, min:n, max:n, confirmed, in:a,b,c, regex:#...#
 */
final class Validator
{
    /** @var array<string, string[]> */
    private array $errors = [];

    public function __construct(private array $data, array $rules, private array $labels = [])
    {
        foreach ($rules as $field => $ruleString) {
            $rulesList = is_array($ruleString) ? $ruleString : explode('|', $ruleString);
            foreach ($rulesList as $rule) {
                $this->apply($field, trim($rule));
            }
        }
    }

    public function fails(): bool
    {
        return $this->errors !== [];
    }

    public function passes(): bool
    {
        return $this->errors === [];
    }

    /**
     * @return array<string, string[]>
     */
    public function errors(): array
    {
        return $this->errors;
    }

    public function firstError(): ?string
    {
        foreach ($this->errors as $messages) {
            return $messages[0];
        }
        return null;
    }

    private function apply(string $field, string $rule): void
    {
        if ($rule === '') {
            return;
        }
        $param = null;
        if (str_contains($rule, ':')) {
            [$rule, $param] = explode(':', $rule, 2);
        }
        $value = $this->data[$field] ?? null;
        $label = $this->labels[$field] ?? ucfirst(str_replace('_', ' ', $field));
        $isEmpty = $value === null || $value === '' || $value === [];

        if ($rule === 'required') {
            if ($isEmpty) {
                $this->addError($field, "{$label} is required.");
            }
            return;
        }
        if ($isEmpty) {
            return; // other rules only apply when a value is present
        }
        $value = is_string($value) ? $value : (string) $value;

        switch ($rule) {
            case 'email':
                if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
                    $this->addError($field, "{$label} must be a valid email address.");
                }
                break;
            case 'url':
                if (!filter_var($value, FILTER_VALIDATE_URL) || !preg_match('#^https?://#i', $value)) {
                    $this->addError($field, "{$label} must be a valid http(s) URL.");
                }
                break;
            case 'numeric':
                if (!is_numeric($value)) {
                    $this->addError($field, "{$label} must be a number.");
                }
                break;
            case 'int':
                if (filter_var($value, FILTER_VALIDATE_INT) === false) {
                    $this->addError($field, "{$label} must be an integer.");
                }
                break;
            case 'min':
                if (mb_strlen($value) < (int) $param) {
                    $this->addError($field, "{$label} must be at least {$param} characters.");
                }
                break;
            case 'max':
                if (mb_strlen($value) > (int) $param) {
                    $this->addError($field, "{$label} must not exceed {$param} characters.");
                }
                break;
            case 'confirmed':
                if (($this->data[$field . '_confirmation'] ?? null) !== $value) {
                    $this->addError($field, "{$label} confirmation does not match.");
                }
                break;
            case 'in':
                $allowed = explode(',', (string) $param);
                if (!in_array($value, $allowed, true)) {
                    $this->addError($field, "{$label} must be one of: {$param}.");
                }
                break;
            case 'regex':
                if (!preg_match((string) $param, $value)) {
                    $this->addError($field, "{$label} format is invalid.");
                }
                break;
        }
    }

    private function addError(string $field, string $message): void
    {
        $this->errors[$field][] = $message;
    }
}

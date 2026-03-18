<?php

declare(strict_types=1);

namespace App\Core;

final class Validator
{
    private array $errors = [];

    public function validate(array $input, array $rules): bool
    {
        foreach ($rules as $field => $fieldRules) {
            $value = $input[$field] ?? null;

            foreach ($fieldRules as $rule) {
                $ruleName = $rule;
                $parameter = null;

                if (strpos($rule, ':') !== false) {
                    list($ruleName, $parameter) = explode(':', $rule, 2);
                }

                $method = 'validate' . ucfirst($ruleName);

                if (method_exists($this, $method)) {
                    $this->{$method}($field, $value, $parameter, $input);
                }
            }
        }

        return $this->errors === [];
    }

    public function errors(): array
    {
        return $this->errors;
    }

    private function addError(string $field, string $message): void
    {
        $this->errors[$field] ??= [];
        $this->errors[$field][] = $message;
    }

    private function validateRequired(string $field, $value): void
    {
        if ($value === null || trim((string) $value) === '') {
            $this->addError($field, 'O campo ' . $field . ' é obrigatório.');
        }
    }

    private function validateEmail(string $field, $value): void
    {
        if ($value !== null && trim((string) $value) !== '' && ! filter_var($value, FILTER_VALIDATE_EMAIL)) {
            $this->addError($field, 'Informe um e-mail válido.');
        }
    }

    private function validateMin(string $field, $value, ?string $parameter): void
    {
        if ($value !== null && mb_strlen(trim((string) $value)) < (int) $parameter) {
            $this->addError($field, 'O campo ' . $field . ' deve ter no mínimo ' . $parameter . ' caracteres.');
        }
    }

    private function validateMax(string $field, $value, ?string $parameter): void
    {
        if ($value !== null && mb_strlen(trim((string) $value)) > (int) $parameter) {
            $this->addError($field, 'O campo ' . $field . ' deve ter no máximo ' . $parameter . ' caracteres.');
        }
    }

    private function validateConfirmed(string $field, $value, ?string $parameter, array $input): void
    {
        $confirmationField = $parameter ?: $field . '_confirmation';

        if (($input[$confirmationField] ?? null) !== $value) {
            $this->addError($field, 'A confirmação do campo ' . $field . ' não confere.');
        }
    }

    private function validateAlphaNumDash(string $field, $value): void
    {
        if ($value !== null && trim((string) $value) !== '' && ! preg_match('/^[a-zA-Z0-9_-]+$/', (string) $value)) {
            $this->addError($field, 'Use apenas letras, números, hífen e underscore.');
        }
    }
}

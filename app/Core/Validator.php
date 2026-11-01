<?php

declare(strict_types=1);

namespace App\Core;

final class Validator
{
    private array $errors = [];

    public function required(string $field, mixed $value, string $message): self
    {
        if (trim((string) $value) === '') {
            $this->errors[$field] ??= $message;
        }

        return $this;
    }

    public function email(string $field, mixed $value, string $message): self
    {
        $value = trim((string) $value);

        if ($value !== '' && filter_var($value, FILTER_VALIDATE_EMAIL) === false) {
            $this->errors[$field] ??= $message;
        }

        return $this;
    }

    public function fails(): bool
    {
        return $this->errors !== [];
    }

    public function errors(): array
    {
        return $this->errors;
    }
}

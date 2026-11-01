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

    public function minLength(string $field, mixed $value, int $min, string $message): self
    {
        if ($this->length((string) $value) < $min) {
            $this->errors[$field] ??= $message;
        }

        return $this;
    }

    public function maxLength(string $field, mixed $value, int $max, string $message): self
    {
        if ($this->length((string) $value) > $max) {
            $this->errors[$field] ??= $message;
        }

        return $this;
    }

    public function in(string $field, mixed $value, array $allowed, string $message): self
    {
        if (!in_array((string) $value, $allowed, true)) {
            $this->errors[$field] ??= $message;
        }

        return $this;
    }

    public function confirmed(string $field, mixed $value, mixed $confirmation, string $message): self
    {
        if ((string) $value !== (string) $confirmation) {
            $this->errors[$field] ??= $message;
        }

        return $this;
    }

    public function decimal(string $field, mixed $value, int $scale, string $message): self
    {
        $normalized = str_replace(',', '.', trim((string) $value));

        if (!preg_match('/^\d+(\.\d{1,' . $scale . '})?$/', $normalized)) {
            $this->errors[$field] ??= $message;
        }

        return $this;
    }

    public function add(string $field, string $message): self
    {
        $this->errors[$field] ??= $message;

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

    private function length(string $value): int
    {
        return function_exists('mb_strlen') ? mb_strlen($value) : strlen($value);
    }
}

<?php

declare(strict_types=1);

namespace App\Core;

final class Request
{
    public static function method(): string
    {
        return strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
    }

    public static function path(): string
    {
        $path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
        $path = rawurldecode($path);

        return rtrim($path, '/') ?: '/';
    }

    public static function input(string $key, mixed $default = null): mixed
    {
        return $_POST[$key] ?? $_GET[$key] ?? $default;
    }

    public static function string(string $key, string $default = ''): string
    {
        $value = self::input($key, $default);

        return is_scalar($value) ? trim((string) $value) : $default;
    }

    public static function all(): array
    {
        return array_merge($_GET, $_POST);
    }
}

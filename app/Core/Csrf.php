<?php

declare(strict_types=1);

namespace App\Core;

final class Csrf
{
    private const SESSION_KEY = '_csrf_token';

    public static function token(): string
    {
        if (empty($_SESSION[self::SESSION_KEY])) {
            $_SESSION[self::SESSION_KEY] = bin2hex(random_bytes(32));
        }

        return (string) $_SESSION[self::SESSION_KEY];
    }

    public static function field(): string
    {
        return '<input type="hidden" name="_csrf" value="' . \e(self::token()) . '">';
    }

    public static function validate(mixed $token): bool
    {
        $expected = $_SESSION[self::SESSION_KEY] ?? null;

        return is_string($expected) && is_string($token) && hash_equals($expected, $token);
    }

    public static function verify(): void
    {
        if (!self::validate($_POST['_csrf'] ?? null)) {
            throw new \DomainException('Sessão expirada. Recarregue a página e tente novamente.');
        }
    }
}

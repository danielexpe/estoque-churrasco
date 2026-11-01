<?php

declare(strict_types=1);

namespace App\Core;

final class Auth
{
    private static bool $resolved = false;
    private static ?array $currentUser = null;

    public static function attempt(string $email, string $password): bool
    {
        $statement = Database::connection()->prepare(
            'SELECT id, name, email, password_hash, role, is_active
             FROM users
             WHERE email = :email
             LIMIT 1'
        );
        $statement->execute(['email' => $email]);
        $user = $statement->fetch();

        if (!$user || (int) $user['is_active'] !== 1) {
            return false;
        }

        if (!password_verify($password, (string) $user['password_hash'])) {
            return false;
        }

        session_regenerate_id(true);

        $_SESSION['user'] = [
            'id' => (int) $user['id'],
            'name' => (string) $user['name'],
            'email' => (string) $user['email'],
            'role' => (string) $user['role'],
        ];

        self::$resolved = true;
        self::$currentUser = $_SESSION['user'];

        return true;
    }

    public static function check(): bool
    {
        return self::user() !== null;
    }

    public static function user(): ?array
    {
        if (self::$resolved) {
            return self::$currentUser;
        }

        self::$resolved = true;

        if (!isset($_SESSION['user']['id'])) {
            return self::$currentUser = null;
        }

        $statement = Database::connection()->prepare(
            'SELECT id, name, email, role, is_active
             FROM users
             WHERE id = :id
             LIMIT 1'
        );
        $statement->execute(['id' => (int) $_SESSION['user']['id']]);
        $user = $statement->fetch();

        if (!$user || (int) $user['is_active'] !== 1) {
            unset($_SESSION['user']);

            return self::$currentUser = null;
        }

        $_SESSION['user'] = [
            'id' => (int) $user['id'],
            'name' => (string) $user['name'],
            'email' => (string) $user['email'],
            'role' => (string) $user['role'],
        ];

        return self::$currentUser = $_SESSION['user'];
    }

    public static function id(): ?int
    {
        return self::user()['id'] ?? null;
    }

    public static function role(): ?string
    {
        return self::user()['role'] ?? null;
    }

    public static function isAdmin(): bool
    {
        return self::role() === 'admin';
    }

    public static function logout(): void
    {
        unset($_SESSION['user']);

        self::$resolved = true;
        self::$currentUser = null;

        session_regenerate_id(true);
    }
}

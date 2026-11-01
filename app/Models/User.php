<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

final class User
{
    public static function all(): array
    {
        return Database::connection()
            ->query('SELECT id, name, email, role, is_active, created_at FROM users ORDER BY name')
            ->fetchAll();
    }

    public static function find(int $id): ?array
    {
        $statement = Database::connection()->prepare(
            'SELECT id, name, email, role, is_active, created_at, updated_at
             FROM users
             WHERE id = :id
             LIMIT 1'
        );
        $statement->execute(['id' => $id]);
        $user = $statement->fetch();

        return $user ?: null;
    }

    public static function emailExists(string $email, ?int $exceptId = null): bool
    {
        $sql = 'SELECT id FROM users WHERE email = :email';
        $parameters = ['email' => $email];

        if ($exceptId !== null) {
            $sql .= ' AND id <> :id';
            $parameters['id'] = $exceptId;
        }

        $statement = Database::connection()->prepare($sql . ' LIMIT 1');
        $statement->execute($parameters);

        return $statement->fetchColumn() !== false;
    }

    public static function create(string $name, string $email, string $passwordHash, string $role): int
    {
        $statement = Database::connection()->prepare(
            'INSERT INTO users (name, email, password_hash, role, is_active)
             VALUES (:name, :email, :password_hash, :role, 1)'
        );
        $statement->execute([
            'name' => $name,
            'email' => $email,
            'password_hash' => $passwordHash,
            'role' => $role,
        ]);

        return (int) Database::connection()->lastInsertId();
    }

    public static function update(int $id, string $name, string $email, string $role): void
    {
        $statement = Database::connection()->prepare(
            'UPDATE users
             SET name = :name, email = :email, role = :role
             WHERE id = :id'
        );
        $statement->execute([
            'id' => $id,
            'name' => $name,
            'email' => $email,
            'role' => $role,
        ]);
    }

    public static function updatePassword(int $id, string $passwordHash): void
    {
        $statement = Database::connection()->prepare(
            'UPDATE users SET password_hash = :password_hash WHERE id = :id'
        );
        $statement->execute([
            'id' => $id,
            'password_hash' => $passwordHash,
        ]);
    }

    public static function setActive(int $id, bool $active): void
    {
        $statement = Database::connection()->prepare(
            'UPDATE users SET is_active = :is_active WHERE id = :id'
        );
        $statement->execute([
            'id' => $id,
            'is_active' => $active ? 1 : 0,
        ]);
    }

    public static function countActive(): int
    {
        return (int) Database::connection()
            ->query('SELECT COUNT(*) FROM users WHERE is_active = 1')
            ->fetchColumn();
    }

    public static function countActiveAdmins(): int
    {
        return (int) Database::connection()
            ->query("SELECT COUNT(*) FROM users WHERE role = 'admin' AND is_active = 1")
            ->fetchColumn();
    }
}

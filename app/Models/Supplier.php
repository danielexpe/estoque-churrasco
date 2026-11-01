<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

final class Supplier
{
    public static function all(?string $search = null): array
    {
        $sql = 'SELECT id, name, document, phone, email, notes, is_active, created_at FROM suppliers';
        $parameters = [];

        if ($search !== null && trim($search) !== '') {
            $like = '%' . trim($search) . '%';
            $sql .= ' WHERE name LIKE ? OR document LIKE ? OR email LIKE ?';
            $parameters = [$like, $like, $like];
        }

        $sql .= ' ORDER BY name';

        $statement = Database::connection()->prepare($sql);
        $statement->execute($parameters);

        return $statement->fetchAll();
    }

    public static function allActive(): array
    {
        return Database::connection()->query(
            'SELECT id, name FROM suppliers WHERE is_active = 1 ORDER BY name'
        )->fetchAll();
    }

    public static function find(int $id): ?array
    {
        $statement = Database::connection()->prepare(
            'SELECT id, name, document, phone, email, notes, is_active, created_at, updated_at
             FROM suppliers
             WHERE id = ?
             LIMIT 1'
        );
        $statement->execute([$id]);
        $supplier = $statement->fetch();

        return $supplier ?: null;
    }

    public static function create(array $data): int
    {
        $statement = Database::connection()->prepare(
            'INSERT INTO suppliers (name, document, phone, email, notes, is_active)
             VALUES (?, ?, ?, ?, ?, 1)'
        );
        $statement->execute([
            $data['name'],
            $data['document'],
            $data['phone'],
            $data['email'],
            $data['notes'],
        ]);

        return (int) Database::connection()->lastInsertId();
    }

    public static function update(int $id, array $data): void
    {
        $statement = Database::connection()->prepare(
            'UPDATE suppliers
             SET name = ?, document = ?, phone = ?, email = ?, notes = ?
             WHERE id = ?'
        );
        $statement->execute([
            $data['name'],
            $data['document'],
            $data['phone'],
            $data['email'],
            $data['notes'],
            $id,
        ]);
    }

    public static function setActive(int $id, bool $active): void
    {
        $statement = Database::connection()->prepare(
            'UPDATE suppliers SET is_active = ? WHERE id = ?'
        );
        $statement->execute([$active ? 1 : 0, $id]);
    }
}

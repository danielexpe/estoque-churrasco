<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

final class Category
{
    public static function all(): array
    {
        return Database::connection()->query(
            'SELECT c.id, c.name, c.description, c.default_controls_lots, c.is_active, c.created_at,
                    (SELECT COUNT(*) FROM products p WHERE p.category_id = c.id AND p.is_active = 1) AS products_count
             FROM categories c
             ORDER BY c.name'
        )->fetchAll();
    }

    public static function allActive(): array
    {
        return Database::connection()->query(
            'SELECT id, name, default_controls_lots
             FROM categories
             WHERE is_active = 1
             ORDER BY name'
        )->fetchAll();
    }

    public static function find(int $id): ?array
    {
        $statement = Database::connection()->prepare(
            'SELECT id, name, description, default_controls_lots, is_active, created_at, updated_at
             FROM categories
             WHERE id = ?
             LIMIT 1'
        );
        $statement->execute([$id]);
        $category = $statement->fetch();

        return $category ?: null;
    }

    public static function nameExists(string $name, ?int $exceptId = null): bool
    {
        $sql = 'SELECT id FROM categories WHERE name = ?';
        $parameters = [$name];

        if ($exceptId !== null) {
            $sql .= ' AND id <> ?';
            $parameters[] = $exceptId;
        }

        $statement = Database::connection()->prepare($sql . ' LIMIT 1');
        $statement->execute($parameters);

        return $statement->fetchColumn() !== false;
    }

    public static function create(string $name, ?string $description, bool $defaultControlsLots): int
    {
        $statement = Database::connection()->prepare(
            'INSERT INTO categories (name, description, default_controls_lots, is_active)
             VALUES (?, ?, ?, 1)'
        );
        $statement->execute([$name, $description, $defaultControlsLots ? 1 : 0]);

        return (int) Database::connection()->lastInsertId();
    }

    public static function update(int $id, string $name, ?string $description, bool $defaultControlsLots): void
    {
        $statement = Database::connection()->prepare(
            'UPDATE categories
             SET name = ?, description = ?, default_controls_lots = ?
             WHERE id = ?'
        );
        $statement->execute([$name, $description, $defaultControlsLots ? 1 : 0, $id]);
    }

    public static function setActive(int $id, bool $active): void
    {
        $statement = Database::connection()->prepare(
            'UPDATE categories SET is_active = ? WHERE id = ?'
        );
        $statement->execute([$active ? 1 : 0, $id]);
    }
}

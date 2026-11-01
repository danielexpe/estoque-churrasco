<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use PDO;

final class Product
{
    public const UNITS = ['un', 'kg', 'g', 'L', 'ml', 'pacote', 'caixa'];

    public static function paginate(array $filters, int $page, int $perPage = 20): array
    {
        $q = trim((string) ($filters['q'] ?? ''));
        $categoryId = $filters['category_id'] ?? null;
        $status = (string) ($filters['status'] ?? 'active');
        $lowStock = (bool) ($filters['low_stock'] ?? false);

        $conditions = [];
        $parameters = [];

        if ($q !== '') {
            $like = '%' . $q . '%';
            $conditions[] = '(p.name LIKE ? OR p.sku LIKE ? OR p.barcode LIKE ?)';
            array_push($parameters, $like, $like, $like);
        }

        if ($categoryId !== null) {
            $conditions[] = 'p.category_id = ?';
            $parameters[] = (int) $categoryId;
        }

        if ($status === 'active') {
            $conditions[] = 'p.is_active = 1';
        } elseif ($status === 'inactive') {
            $conditions[] = 'p.is_active = 0';
        }

        if ($lowStock) {
            $conditions[] = 'p.current_stock <= p.min_stock';
        }

        $where = $conditions === [] ? '' : ' WHERE ' . implode(' AND ', $conditions);

        $pdo = Database::connection();

        $countStatement = $pdo->prepare('SELECT COUNT(*) FROM products p' . $where);
        $countStatement->execute($parameters);
        $total = (int) $countStatement->fetchColumn();

        $pages = max(1, (int) ceil($total / $perPage));
        $page = max(1, min($page, $pages));
        $offset = ($page - 1) * $perPage;

        $sql = 'SELECT p.*, c.name AS category_name
                FROM products p
                LEFT JOIN categories c ON c.id = p.category_id'
                . $where
                . ' ORDER BY p.name LIMIT ? OFFSET ?';

        $statement = $pdo->prepare($sql);
        $index = 1;

        foreach ($parameters as $value) {
            $statement->bindValue(
                $index++,
                $value,
                is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR
            );
        }

        $statement->bindValue($index++, $perPage, PDO::PARAM_INT);
        $statement->bindValue($index++, $offset, PDO::PARAM_INT);
        $statement->execute();

        return [
            'items' => $statement->fetchAll(),
            'total' => $total,
            'page' => $page,
            'pages' => $pages,
        ];
    }

    public static function find(int $id): ?array
    {
        $statement = Database::connection()->prepare(
            'SELECT p.*, c.name AS category_name, c.is_active AS category_active
             FROM products p
             LEFT JOIN categories c ON c.id = p.category_id
             WHERE p.id = ?
             LIMIT 1'
        );
        $statement->execute([$id]);
        $product = $statement->fetch();

        return $product ?: null;
    }

    public static function skuExists(string $sku, ?int $exceptId = null): bool
    {
        if ($sku === '') {
            return false;
        }

        $sql = 'SELECT id FROM products WHERE sku = ?';
        $parameters = [$sku];

        if ($exceptId !== null) {
            $sql .= ' AND id <> ?';
            $parameters[] = $exceptId;
        }

        $statement = Database::connection()->prepare($sql . ' LIMIT 1');
        $statement->execute($parameters);

        return $statement->fetchColumn() !== false;
    }

    public static function barcodeExists(string $barcode, ?int $exceptId = null): bool
    {
        if ($barcode === '') {
            return false;
        }

        $sql = 'SELECT id FROM products WHERE barcode = ?';
        $parameters = [$barcode];

        if ($exceptId !== null) {
            $sql .= ' AND id <> ?';
            $parameters[] = $exceptId;
        }

        $statement = Database::connection()->prepare($sql . ' LIMIT 1');
        $statement->execute($parameters);

        return $statement->fetchColumn() !== false;
    }

    public static function create(array $data): int
    {
        $statement = Database::connection()->prepare(
            'INSERT INTO products (
                category_id, name, sku, barcode, unit, sale_price, cost_price,
                min_stock, current_stock, controls_lots, is_active
             ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 0, ?, 1)'
        );
        $statement->execute([
            $data['category_id'],
            $data['name'],
            $data['sku'],
            $data['barcode'],
            $data['unit'],
            $data['sale_price'],
            $data['cost_price'],
            $data['min_stock'],
            $data['controls_lots'],
        ]);

        return (int) Database::connection()->lastInsertId();
    }

    public static function update(int $id, array $data): void
    {
        $statement = Database::connection()->prepare(
            'UPDATE products
             SET category_id = ?, name = ?, sku = ?, barcode = ?, unit = ?,
                 sale_price = ?, cost_price = ?, min_stock = ?, controls_lots = ?
             WHERE id = ?'
        );
        $statement->execute([
            $data['category_id'],
            $data['name'],
            $data['sku'],
            $data['barcode'],
            $data['unit'],
            $data['sale_price'],
            $data['cost_price'],
            $data['min_stock'],
            $data['controls_lots'],
            $id,
        ]);
    }

    public static function setActive(int $id, bool $active): void
    {
        $statement = Database::connection()->prepare(
            'UPDATE products SET is_active = ? WHERE id = ?'
        );
        $statement->execute([$active ? 1 : 0, $id]);
    }

    public static function hasMovements(int $id): bool
    {
        $statement = Database::connection()->prepare(
            'SELECT 1 FROM stock_movements WHERE product_id = ? LIMIT 1'
        );
        $statement->execute([$id]);

        return $statement->fetchColumn() !== false;
    }

    public static function countActive(): int
    {
        return (int) Database::connection()
            ->query('SELECT COUNT(*) FROM products WHERE is_active = 1')
            ->fetchColumn();
    }

    public static function countLowStock(): int
    {
        return (int) Database::connection()
            ->query('SELECT COUNT(*) FROM products WHERE is_active = 1 AND current_stock <= min_stock')
            ->fetchColumn();
    }

    public static function allActiveForSelect(): array
    {
        return Database::connection()->query(
            'SELECT id, name, unit, controls_lots FROM products WHERE is_active = 1 ORDER BY name'
        )->fetchAll();
    }

    public static function lockForUpdate(int $id): ?array
    {
        $statement = Database::connection()->prepare(
            'SELECT id, name, unit, sale_price, controls_lots, current_stock, cost_price, is_active
             FROM products
             WHERE id = ?
             FOR UPDATE'
        );
        $statement->execute([$id]);
        $product = $statement->fetch();

        return $product ?: null;
    }

    public static function updateStockAndCost(int $id, string $currentStock, string $costPrice): void
    {
        $statement = Database::connection()->prepare(
            'UPDATE products SET current_stock = ?, cost_price = ? WHERE id = ?'
        );
        $statement->execute([$currentStock, $costPrice, $id]);
    }

    public static function updateStock(int $id, string $currentStock): void
    {
        $statement = Database::connection()->prepare(
            'UPDATE products SET current_stock = ? WHERE id = ?'
        );
        $statement->execute([$currentStock, $id]);
    }

    public static function allActiveForSale(string $today): array
    {
        $statement = Database::connection()->prepare(
            'SELECT p.id, p.name, p.unit, p.sale_price, p.controls_lots,
                    CASE
                        WHEN p.controls_lots = 1 THEN (
                            SELECT COALESCE(SUM(l.current_quantity), 0)
                            FROM product_lots l
                            WHERE l.product_id = p.id
                              AND l.current_quantity > 0
                              AND (l.expires_at IS NULL OR l.expires_at >= ?)
                        )
                        ELSE p.current_stock
                    END AS available_quantity
             FROM products p
             WHERE p.is_active = 1
             ORDER BY p.name'
        );
        $statement->execute([$today]);

        return $statement->fetchAll();
    }
}

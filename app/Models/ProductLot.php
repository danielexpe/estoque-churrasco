<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use PDO;

final class ProductLot
{
    public static function forProduct(int $productId): array
    {
        $statement = Database::connection()->prepare(
            'SELECT id, product_id, purchase_item_id, lot_code, expires_at,
                    initial_quantity, current_quantity, unit_cost, created_at
             FROM product_lots
             WHERE product_id = ?
             ORDER BY expires_at IS NULL, expires_at, id'
        );
        $statement->execute([$productId]);

        return $statement->fetchAll();
    }

    public static function createForPurchase(
        int $productId,
        int $purchaseItemId,
        string $lotCode,
        string $expiresAt,
        string $quantity,
        string $unitCost
    ): int {
        $statement = Database::connection()->prepare(
            'INSERT INTO product_lots (
                product_id, purchase_item_id, lot_code, expires_at,
                initial_quantity, current_quantity, unit_cost
             ) VALUES (?, ?, ?, ?, ?, ?, ?)'
        );
        $statement->execute([
            $productId,
            $purchaseItemId,
            $lotCode,
            $expiresAt,
            $quantity,
            $quantity,
            $unitCost,
        ]);

        return (int) Database::connection()->lastInsertId();
    }

    public static function lockAvailableForSale(int $productId, string $today): array
    {
        $statement = Database::connection()->prepare(
            'SELECT id, lot_code, expires_at, current_quantity
             FROM product_lots
             WHERE product_id = ?
               AND current_quantity > 0
               AND (expires_at IS NULL OR expires_at >= ?)
             ORDER BY expires_at IS NULL, expires_at, id
             FOR UPDATE'
        );
        $statement->execute([$productId, $today]);

        return $statement->fetchAll();
    }

    public static function setCurrentQuantity(int $id, string $quantity): void
    {
        $statement = Database::connection()->prepare(
            'UPDATE product_lots SET current_quantity = ? WHERE id = ?'
        );
        $statement->execute([$quantity, $id]);
    }

    public static function increaseCurrentQuantity(int $id, string $quantity): void
    {
        $statement = Database::connection()->prepare(
            'UPDATE product_lots SET current_quantity = current_quantity + ? WHERE id = ?'
        );
        $statement->execute([$quantity, $id]);
    }

    public static function createManual(
        int $productId,
        string $lotCode,
        string $expiresAt,
        string $quantity,
        string $unitCost
    ): int {
        $statement = Database::connection()->prepare(
            'INSERT INTO product_lots (
                product_id, purchase_item_id, lot_code, expires_at,
                initial_quantity, current_quantity, unit_cost
             ) VALUES (?, NULL, ?, ?, ?, ?, ?)'
        );
        $statement->execute([
            $productId,
            $lotCode,
            $expiresAt,
            $quantity,
            $quantity,
            $unitCost,
        ]);

        return (int) Database::connection()->lastInsertId();
    }

    public static function lockForAdjustment(int $productId): array
    {
        $statement = Database::connection()->prepare(
            'SELECT id, lot_code, expires_at, current_quantity
             FROM product_lots
             WHERE product_id = ? AND current_quantity > 0
             ORDER BY expires_at IS NULL, expires_at, id
             FOR UPDATE'
        );
        $statement->execute([$productId]);

        return $statement->fetchAll();
    }

    public static function expiringAlerts(string $threshold, int $limit = 10): array
    {
        $statement = Database::connection()->prepare(
            'SELECT l.id, l.product_id, l.lot_code, l.expires_at, l.current_quantity,
                    p.name AS product_name, p.unit
             FROM product_lots l
             JOIN products p ON p.id = l.product_id
             WHERE l.current_quantity > 0
               AND l.expires_at IS NOT NULL
               AND l.expires_at <= ?
             ORDER BY l.expires_at, l.id
             LIMIT ?'
        );
        $statement->bindValue(1, $threshold);
        $statement->bindValue(2, $limit, PDO::PARAM_INT);
        $statement->execute();

        return $statement->fetchAll();
    }

    public static function countExpiringAlerts(string $threshold): int
    {
        $statement = Database::connection()->prepare(
            'SELECT COUNT(*)
             FROM product_lots
             WHERE current_quantity > 0
               AND expires_at IS NOT NULL
               AND expires_at <= ?'
        );
        $statement->execute([$threshold]);

        return (int) $statement->fetchColumn();
    }
}

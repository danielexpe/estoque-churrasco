<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

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
}

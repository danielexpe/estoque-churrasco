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
}

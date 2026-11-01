<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use PDO;

final class StockMovement
{
    public static function forProduct(int $productId, int $limit = 20): array
    {
        $statement = Database::connection()->prepare(
            'SELECT m.id, m.movement_type, m.quantity, m.balance_after,
                    m.reference_type, m.reference_id, m.notes, m.created_at,
                    u.name AS user_name, l.lot_code
             FROM stock_movements m
             JOIN users u ON u.id = m.user_id
             LEFT JOIN product_lots l ON l.id = m.product_lot_id
             WHERE m.product_id = ?
             ORDER BY m.id DESC
             LIMIT ?'
        );
        $statement->bindValue(1, $productId, PDO::PARAM_INT);
        $statement->bindValue(2, $limit, PDO::PARAM_INT);
        $statement->execute();

        return $statement->fetchAll();
    }

    public static function forPurchase(int $purchaseId): array
    {
        $statement = Database::connection()->prepare(
            'SELECT m.id, m.movement_type, m.quantity, m.balance_after, m.notes, m.created_at,
                    p.name AS product_name, p.unit, u.name AS user_name, l.lot_code
             FROM stock_movements m
             JOIN products p ON p.id = m.product_id
             JOIN users u ON u.id = m.user_id
             LEFT JOIN product_lots l ON l.id = m.product_lot_id
             WHERE m.reference_type = \'purchase\' AND m.reference_id = ?
             ORDER BY m.id'
        );
        $statement->execute([$purchaseId]);

        return $statement->fetchAll();
    }

    public static function createForPurchase(
        int $productId,
        ?int $productLotId,
        string $quantity,
        string $balanceAfter,
        int $purchaseId,
        int $userId,
        ?string $notes
    ): void {
        $statement = Database::connection()->prepare(
            'INSERT INTO stock_movements (
                product_id, product_lot_id, movement_type, quantity, balance_after,
                reference_type, reference_id, user_id, notes
             ) VALUES (?, ?, \'entrada_compra\', ?, ?, \'purchase\', ?, ?, ?)'
        );
        $statement->execute([
            $productId,
            $productLotId,
            $quantity,
            $balanceAfter,
            $purchaseId,
            $userId,
            $notes,
        ]);
    }
}

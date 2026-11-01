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

    public static function createForSale(
        int $productId,
        ?int $productLotId,
        string $quantity,
        string $balanceAfter,
        int $saleId,
        int $userId,
        ?string $notes
    ): void {
        $statement = Database::connection()->prepare(
            'INSERT INTO stock_movements (
                product_id, product_lot_id, movement_type, quantity, balance_after,
                reference_type, reference_id, user_id, notes
             ) VALUES (?, ?, \'saida_venda\', ?, ?, \'sale\', ?, ?, ?)'
        );
        $statement->execute([
            $productId,
            $productLotId,
            $quantity,
            $balanceAfter,
            $saleId,
            $userId,
            $notes,
        ]);
    }

    public static function createForSaleReturn(
        int $productId,
        ?int $productLotId,
        string $quantity,
        string $balanceAfter,
        int $saleId,
        int $userId,
        ?string $notes
    ): void {
        $statement = Database::connection()->prepare(
            'INSERT INTO stock_movements (
                product_id, product_lot_id, movement_type, quantity, balance_after,
                reference_type, reference_id, user_id, notes
             ) VALUES (?, ?, \'estorno_venda\', ?, ?, \'sale\', ?, ?, ?)'
        );
        $statement->execute([
            $productId,
            $productLotId,
            $quantity,
            $balanceAfter,
            $saleId,
            $userId,
            $notes,
        ]);
    }

    public static function createForAdjustment(
        int $productId,
        ?int $productLotId,
        string $movementType,
        string $quantity,
        string $balanceAfter,
        int $adjustmentId,
        int $userId,
        ?string $notes
    ): void {
        $statement = Database::connection()->prepare(
            'INSERT INTO stock_movements (
                product_id, product_lot_id, movement_type, quantity, balance_after,
                reference_type, reference_id, user_id, notes
             ) VALUES (?, ?, ?, ?, ?, \'adjustment\', ?, ?, ?)'
        );
        $statement->execute([
            $productId,
            $productLotId,
            $movementType,
            $quantity,
            $balanceAfter,
            $adjustmentId,
            $userId,
            $notes,
        ]);
    }

    public static function paginate(array $filters, int $page, int $perPage = 20): array
    {
        $conditions = [];
        $parameters = [];

        if (($filters['product_id'] ?? null) !== null) {
            $conditions[] = 'm.product_id = ?';
            $parameters[] = (int) $filters['product_id'];
        }

        if (($filters['movement_type'] ?? null) !== null) {
            $conditions[] = 'm.movement_type = ?';
            $parameters[] = (string) $filters['movement_type'];
        }

        if (($filters['user_id'] ?? null) !== null) {
            $conditions[] = 'm.user_id = ?';
            $parameters[] = (int) $filters['user_id'];
        }

        if (($filters['date_from'] ?? null) !== null) {
            $conditions[] = 'm.created_at >= ?';
            $parameters[] = $filters['date_from'] . ' 00:00:00';
        }

        if (($filters['date_to'] ?? null) !== null) {
            $conditions[] = 'm.created_at <= ?';
            $parameters[] = $filters['date_to'] . ' 23:59:59';
        }

        $where = $conditions === [] ? '' : ' WHERE ' . implode(' AND ', $conditions);

        $pdo = Database::connection();

        $countStatement = $pdo->prepare('SELECT COUNT(*) FROM stock_movements m' . $where);
        $countStatement->execute($parameters);
        $total = (int) $countStatement->fetchColumn();

        $pages = max(1, (int) ceil($total / $perPage));
        $page = max(1, min($page, $pages));
        $offset = ($page - 1) * $perPage;

        $sql = 'SELECT m.id, m.movement_type, m.quantity, m.balance_after,
                       m.reference_type, m.reference_id, m.notes, m.created_at,
                       p.name AS product_name, p.unit, u.name AS user_name, l.lot_code
                FROM stock_movements m
                JOIN products p ON p.id = m.product_id
                JOIN users u ON u.id = m.user_id
                LEFT JOIN product_lots l ON l.id = m.product_lot_id'
                . $where
                . ' ORDER BY m.id DESC LIMIT ? OFFSET ?';

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
}

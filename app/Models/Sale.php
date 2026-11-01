<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use PDO;

final class Sale
{
    public static function paginate(array $filters, int $page, int $perPage = 20): array
    {
        $dateFrom = $filters['date_from'] ?? null;
        $dateTo = $filters['date_to'] ?? null;
        $status = (string) ($filters['status'] ?? 'all');

        $conditions = [];
        $parameters = [];

        if ($dateFrom !== null) {
            $conditions[] = 's.sold_at >= ?';
            $parameters[] = $dateFrom . ' 00:00:00';
        }

        if ($dateTo !== null) {
            $conditions[] = 's.sold_at <= ?';
            $parameters[] = $dateTo . ' 23:59:59';
        }

        if (in_array($status, ['concluida', 'cancelada'], true)) {
            $conditions[] = 's.status = ?';
            $parameters[] = $status;
        }

        $where = $conditions === [] ? '' : ' WHERE ' . implode(' AND ', $conditions);

        $pdo = Database::connection();

        $countStatement = $pdo->prepare('SELECT COUNT(*) FROM sales s' . $where);
        $countStatement->execute($parameters);
        $total = (int) $countStatement->fetchColumn();

        $pages = max(1, (int) ceil($total / $perPage));
        $page = max(1, min($page, $pages));
        $offset = ($page - 1) * $perPage;

        $sql = 'SELECT s.id, s.sold_at, s.total_value, s.status, s.created_at,
                       u.name AS user_name,
                       (SELECT COUNT(*) FROM sale_items si WHERE si.sale_id = s.id) AS items_count
                FROM sales s
                JOIN users u ON u.id = s.user_id'
                . $where
                . ' ORDER BY s.sold_at DESC, s.id DESC LIMIT ? OFFSET ?';

        $statement = $pdo->prepare($sql);
        $index = 1;

        foreach ($parameters as $value) {
            $statement->bindValue($index++, $value, PDO::PARAM_STR);
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
            'SELECT s.*, u.name AS user_name, c.name AS canceled_by_name
             FROM sales s
             JOIN users u ON u.id = s.user_id
             LEFT JOIN users c ON c.id = s.canceled_by
             WHERE s.id = ?
             LIMIT 1'
        );
        $statement->execute([$id]);
        $sale = $statement->fetch();

        return $sale ?: null;
    }

    public static function items(int $saleId): array
    {
        $statement = Database::connection()->prepare(
            'SELECT si.id, si.product_id, si.quantity, si.unit_price, si.unit_cost, si.line_total,
                    p.name AS product_name, p.unit
             FROM sale_items si
             JOIN products p ON p.id = si.product_id
             WHERE si.sale_id = ?
             ORDER BY si.id'
        );
        $statement->execute([$saleId]);

        return $statement->fetchAll();
    }

    public static function itemLots(int $saleId): array
    {
        $statement = Database::connection()->prepare(
            'SELECT sil.sale_item_id, sil.product_lot_id, sil.quantity,
                    l.lot_code, l.expires_at, p.name AS product_name, p.unit
             FROM sale_item_lots sil
             JOIN sale_items si ON si.id = sil.sale_item_id
             JOIN product_lots l ON l.id = sil.product_lot_id
             JOIN products p ON p.id = si.product_id
             WHERE si.sale_id = ?
             ORDER BY si.id, l.expires_at IS NULL, l.expires_at, l.id'
        );
        $statement->execute([$saleId]);

        return $statement->fetchAll();
    }

    public static function create(array $data): int
    {
        $statement = Database::connection()->prepare(
            'INSERT INTO sales (user_id, sold_at, total_value, status)
             VALUES (?, ?, ?, \'concluida\')'
        );
        $statement->execute([
            $data['user_id'],
            $data['sold_at'],
            $data['total_value'],
        ]);

        return (int) Database::connection()->lastInsertId();
    }

    public static function createItem(
        int $saleId,
        int $productId,
        string $quantity,
        string $unitPrice,
        string $unitCost,
        string $lineTotal
    ): int {
        $statement = Database::connection()->prepare(
            'INSERT INTO sale_items (
                sale_id, product_id, quantity, unit_price, unit_cost, line_total
             ) VALUES (?, ?, ?, ?, ?, ?)'
        );
        $statement->execute([
            $saleId,
            $productId,
            $quantity,
            $unitPrice,
            $unitCost,
            $lineTotal,
        ]);

        return (int) Database::connection()->lastInsertId();
    }

    public static function createItemLot(int $saleItemId, int $productLotId, string $quantity): void
    {
        $statement = Database::connection()->prepare(
            'INSERT INTO sale_item_lots (sale_item_id, product_lot_id, quantity)
             VALUES (?, ?, ?)'
        );
        $statement->execute([$saleItemId, $productLotId, $quantity]);
    }

    public static function updateTotal(int $id, string $total): void
    {
        $statement = Database::connection()->prepare(
            'UPDATE sales SET total_value = ? WHERE id = ?'
        );
        $statement->execute([$total, $id]);
    }

    public static function lockForUpdate(int $id): ?array
    {
        $statement = Database::connection()->prepare(
            'SELECT * FROM sales WHERE id = ? FOR UPDATE'
        );
        $statement->execute([$id]);
        $sale = $statement->fetch();

        return $sale ?: null;
    }

    public static function cancel(int $id, int $userId, string $reason, string $canceledAt): void
    {
        $statement = Database::connection()->prepare(
            'UPDATE sales
             SET status = \'cancelada\', canceled_by = ?, canceled_at = ?, cancel_reason = ?
             WHERE id = ? AND status = \'concluida\''
        );
        $statement->execute([$userId, $canceledAt, $reason, $id]);
    }

    public static function countToday(string $today): int
    {
        $statement = Database::connection()->prepare(
            'SELECT COUNT(*) FROM sales WHERE status = \'concluida\' AND DATE(sold_at) = ?'
        );
        $statement->execute([$today]);

        return (int) $statement->fetchColumn();
    }

    public static function totalToday(string $today): string
    {
        $statement = Database::connection()->prepare(
            'SELECT COALESCE(SUM(total_value), 0) FROM sales WHERE status = \'concluida\' AND DATE(sold_at) = ?'
        );
        $statement->execute([$today]);

        return (string) $statement->fetchColumn();
    }
}

<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use App\Core\Money;

final class ReportService
{
    public function stock(): array
    {
        return Database::connection()->query(
            'SELECT p.id, p.name, c.name AS category_name, p.unit,
                    p.current_stock, p.min_stock, p.sale_price,
                    p.controls_lots, p.is_active
             FROM products p
             LEFT JOIN categories c ON c.id = p.category_id
             WHERE p.is_active = 1
             ORDER BY p.name'
        )->fetchAll();
    }

    public function lowStock(): array
    {
        return Database::connection()->query(
            'SELECT p.id, p.name, c.name AS category_name, p.unit,
                    p.current_stock, p.min_stock, p.sale_price,
                    p.controls_lots, p.is_active
             FROM products p
             LEFT JOIN categories c ON c.id = p.category_id
             WHERE p.is_active = 1
               AND p.current_stock <= p.min_stock
             ORDER BY (p.current_stock - p.min_stock), p.name'
        )->fetchAll();
    }

    public function expiringLots(string $threshold): array
    {
        $statement = Database::connection()->prepare(
            'SELECT l.id, l.product_id, l.lot_code, l.expires_at, l.current_quantity,
                    p.name AS product_name, p.unit
             FROM product_lots l
             JOIN products p ON p.id = l.product_id
             WHERE l.current_quantity > 0
               AND l.expires_at IS NOT NULL
               AND l.expires_at <= ?
             ORDER BY l.expires_at, l.id'
        );
        $statement->execute([$threshold]);

        return $statement->fetchAll();
    }

    public function sales(string $dateFrom, string $dateTo): array
    {
        $start = $dateFrom . ' 00:00:00';
        $end = $dateTo . ' 23:59:59';
        $pdo = Database::connection();

        $concludedStatement = $pdo->prepare(
            'SELECT s.id, s.sold_at, s.total_value, s.status, u.name AS user_name,
                    (SELECT COUNT(*) FROM sale_items si WHERE si.sale_id = s.id) AS items_count
             FROM sales s
             JOIN users u ON u.id = s.user_id
             WHERE s.status = \'concluida\'
               AND s.sold_at >= ?
               AND s.sold_at <= ?
             ORDER BY s.sold_at DESC, s.id DESC'
        );
        $concludedStatement->execute([$start, $end]);
        $concluded = $concludedStatement->fetchAll();

        $canceledStatement = $pdo->prepare(
            'SELECT s.id, s.sold_at, s.total_value, s.status, s.cancel_reason, s.canceled_at,
                    u.name AS user_name, c.name AS canceled_by_name,
                    (SELECT COUNT(*) FROM sale_items si WHERE si.sale_id = s.id) AS items_count
             FROM sales s
             JOIN users u ON u.id = s.user_id
             LEFT JOIN users c ON c.id = s.canceled_by
             WHERE s.status = \'cancelada\'
               AND s.sold_at >= ?
               AND s.sold_at <= ?
             ORDER BY s.sold_at DESC, s.id DESC'
        );
        $canceledStatement->execute([$start, $end]);
        $canceled = $canceledStatement->fetchAll();

        $concludedTotal = '0.00';
        $itemLines = 0;

        foreach ($concluded as $sale) {
            $concludedTotal = Money::add($concludedTotal, (string) $sale['total_value'], 2);
            $itemLines += (int) $sale['items_count'];
        }

        return [
            'concluded' => $concluded,
            'canceled' => $canceled,
            'concluded_count' => count($concluded),
            'canceled_count' => count($canceled),
            'concluded_total' => $concludedTotal,
            'concluded_item_lines' => $itemLines,
        ];
    }

    public function reconciliation(): array
    {
        return Database::connection()->query(
            'SELECT p.id, p.name, p.unit, p.controls_lots, p.current_stock,
                    COALESCE(SUM(m.quantity), 0) AS ledger_stock,
                    (
                        SELECT COALESCE(SUM(l.current_quantity), 0)
                        FROM product_lots l
                        WHERE l.product_id = p.id
                    ) AS lots_stock
             FROM products p
             LEFT JOIN stock_movements m ON m.product_id = p.id
             GROUP BY p.id, p.name, p.unit, p.controls_lots, p.current_stock
             HAVING p.current_stock <> COALESCE(SUM(m.quantity), 0)
                 OR (
                     p.controls_lots = 1
                     AND p.current_stock <> (
                         SELECT COALESCE(SUM(l.current_quantity), 0)
                         FROM product_lots l
                         WHERE l.product_id = p.id
                     )
                 )
             ORDER BY p.name'
        )->fetchAll();
    }
}

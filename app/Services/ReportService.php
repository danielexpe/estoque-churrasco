<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;

final class ReportService
{
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

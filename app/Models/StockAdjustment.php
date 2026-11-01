<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use PDO;

final class StockAdjustment
{
    public static function paginate(array $filters, int $page, int $perPage = 20): array
    {
        $conditions = [];
        $parameters = [];

        if (($filters['date_from'] ?? null) !== null) {
            $conditions[] = 'a.created_at >= ?';
            $parameters[] = $filters['date_from'] . ' 00:00:00';
        }

        if (($filters['date_to'] ?? null) !== null) {
            $conditions[] = 'a.created_at <= ?';
            $parameters[] = $filters['date_to'] . ' 23:59:59';
        }

        if (($filters['adjustment_type'] ?? null) !== null) {
            $conditions[] = 'a.adjustment_type = ?';
            $parameters[] = (string) $filters['adjustment_type'];
        }

        $where = $conditions === [] ? '' : ' WHERE ' . implode(' AND ', $conditions);

        $pdo = Database::connection();

        $countStatement = $pdo->prepare('SELECT COUNT(*) FROM stock_adjustments a' . $where);
        $countStatement->execute($parameters);
        $total = (int) $countStatement->fetchColumn();

        $pages = max(1, (int) ceil($total / $perPage));
        $page = max(1, min($page, $pages));
        $offset = ($page - 1) * $perPage;

        $sql = 'SELECT a.id, a.adjustment_type, a.quantity, a.unit_cost, a.reason, a.created_at,
                       p.name AS product_name, p.unit, u.name AS user_name
                FROM stock_adjustments a
                JOIN products p ON p.id = a.product_id
                JOIN users u ON u.id = a.user_id'
                . $where
                . ' ORDER BY a.id DESC LIMIT ? OFFSET ?';

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

    public static function create(array $data): int
    {
        $statement = Database::connection()->prepare(
            'INSERT INTO stock_adjustments (
                product_id, user_id, adjustment_type, quantity, unit_cost, reason
             ) VALUES (?, ?, ?, ?, ?, ?)'
        );
        $statement->execute([
            $data['product_id'],
            $data['user_id'],
            $data['adjustment_type'],
            $data['quantity'],
            $data['unit_cost'],
            $data['reason'],
        ]);

        return (int) Database::connection()->lastInsertId();
    }
}

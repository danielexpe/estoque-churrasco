<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use PDO;

final class Purchase
{
    public static function paginate(array $filters, int $page, int $perPage = 20): array
    {
        $dateFrom = $filters['date_from'] ?? null;
        $dateTo = $filters['date_to'] ?? null;
        $supplierId = $filters['supplier_id'] ?? null;

        $conditions = [];
        $parameters = [];

        if ($dateFrom !== null) {
            $conditions[] = 'pu.purchased_at >= ?';
            $parameters[] = $dateFrom . ' 00:00:00';
        }

        if ($dateTo !== null) {
            $conditions[] = 'pu.purchased_at <= ?';
            $parameters[] = $dateTo . ' 23:59:59';
        }

        if ($supplierId !== null) {
            $conditions[] = 'pu.supplier_id = ?';
            $parameters[] = (int) $supplierId;
        }

        $where = $conditions === [] ? '' : ' WHERE ' . implode(' AND ', $conditions);

        $pdo = Database::connection();

        $countStatement = $pdo->prepare('SELECT COUNT(*) FROM purchases pu' . $where);
        $countStatement->execute($parameters);
        $total = (int) $countStatement->fetchColumn();

        $pages = max(1, (int) ceil($total / $perPage));
        $page = max(1, min($page, $pages));
        $offset = ($page - 1) * $perPage;

        $sql = 'SELECT pu.id, pu.purchased_at, pu.document_number, pu.total_cost, pu.created_at,
                       s.name AS supplier_name, u.name AS user_name,
                       (SELECT COUNT(*) FROM purchase_items pi WHERE pi.purchase_id = pu.id) AS items_count
                FROM purchases pu
                LEFT JOIN suppliers s ON s.id = pu.supplier_id
                JOIN users u ON u.id = pu.user_id'
                . $where
                . ' ORDER BY pu.purchased_at DESC, pu.id DESC LIMIT ? OFFSET ?';

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
            'SELECT pu.*, s.name AS supplier_name, u.name AS user_name
             FROM purchases pu
             LEFT JOIN suppliers s ON s.id = pu.supplier_id
             JOIN users u ON u.id = pu.user_id
             WHERE pu.id = ?
             LIMIT 1'
        );
        $statement->execute([$id]);
        $purchase = $statement->fetch();

        return $purchase ?: null;
    }

    public static function items(int $purchaseId): array
    {
        $statement = Database::connection()->prepare(
            'SELECT pi.id, pi.product_id, pi.quantity, pi.unit_cost, pi.line_total,
                    p.name AS product_name, p.unit,
                    l.id AS lot_id, l.lot_code, l.expires_at
             FROM purchase_items pi
             JOIN products p ON p.id = pi.product_id
             LEFT JOIN product_lots l ON l.purchase_item_id = pi.id
             WHERE pi.purchase_id = ?
             ORDER BY pi.id'
        );
        $statement->execute([$purchaseId]);

        return $statement->fetchAll();
    }

    public static function create(array $data): int
    {
        $statement = Database::connection()->prepare(
            'INSERT INTO purchases (
                supplier_id, user_id, purchased_at, document_number, notes, total_cost
             ) VALUES (?, ?, ?, ?, ?, ?)'
        );
        $statement->execute([
            $data['supplier_id'],
            $data['user_id'],
            $data['purchased_at'],
            $data['document_number'],
            $data['notes'],
            $data['total_cost'],
        ]);

        return (int) Database::connection()->lastInsertId();
    }

    public static function createItem(
        int $purchaseId,
        int $productId,
        string $quantity,
        string $unitCost,
        string $lineTotal
    ): int {
        $statement = Database::connection()->prepare(
            'INSERT INTO purchase_items (
                purchase_id, product_id, quantity, unit_cost, line_total
             ) VALUES (?, ?, ?, ?, ?)'
        );
        $statement->execute([
            $purchaseId,
            $productId,
            $quantity,
            $unitCost,
            $lineTotal,
        ]);

        return (int) Database::connection()->lastInsertId();
    }

    public static function updateTotal(int $id, string $total): void
    {
        $statement = Database::connection()->prepare(
            'UPDATE purchases SET total_cost = ? WHERE id = ?'
        );
        $statement->execute([$total, $id]);
    }
}

<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use App\Core\Money;
use App\Models\Product;
use App\Models\ProductLot;
use App\Models\Sale;
use App\Models\StockMovement;
use DomainException;
use Throwable;

final class SaleService
{
    public function paginate(array $filters, int $page): array
    {
        return Sale::paginate($filters, $page);
    }

    public function find(int $id): array
    {
        $sale = Sale::find($id);

        if ($sale === null) {
            throw new DomainException('Venda não encontrada.');
        }

        return $sale;
    }

    public function items(int $id): array
    {
        return Sale::items($id);
    }

    public function itemLots(int $id): array
    {
        return Sale::itemLots($id);
    }

    public function create(array $items, int $userId, string $soldAt): int
    {
        $consolidated = $this->consolidateItems($items);
        $today = date('Y-m-d');

        if ($consolidated === []) {
            throw new DomainException('Adicione pelo menos um item à venda.');
        }

        $pdo = Database::connection();
        $pdo->beginTransaction();

        try {
            $productIds = array_keys($consolidated);
            sort($productIds, SORT_NUMERIC);

            $products = [];

            foreach ($productIds as $productId) {
                $product = Product::lockForUpdate((int) $productId);

                if ($product === null || (int) $product['is_active'] !== 1) {
                    throw new DomainException('Produto inválido ou inativo na venda.');
                }

                $products[(int) $productId] = $product;
            }

            $saleId = Sale::create([
                'user_id' => $userId,
                'sold_at' => $soldAt,
                'total_value' => '0.00',
            ]);

            $total = '0.00';

            foreach ($consolidated as $productId => $quantity) {
                $product = $products[$productId];
                $unitPrice = (string) $product['sale_price'];
                $unitCost = (string) $product['cost_price'];
                $lineTotal = Money::multiply($quantity, $unitPrice, 2);

                $saleItemId = Sale::createItem(
                    $saleId,
                    $productId,
                    $quantity,
                    $unitPrice,
                    $unitCost,
                    $lineTotal
                );

                if ((int) $product['controls_lots'] === 1) {
                    $lots = ProductLot::lockAvailableForSale($productId, $today);
                    $available = '0.000';

                    foreach ($lots as $lot) {
                        $available = Money::add($available, (string) $lot['current_quantity'], 3);
                    }

                    if (Money::compare($available, $quantity, 3) < 0) {
                        throw new DomainException(
                            'Estoque disponível insuficiente para o produto ' . $product['name'] . '.'
                        );
                    }

                    $remaining = $quantity;

                    foreach ($lots as $lot) {
                        if (Money::compare($remaining, '0', 3) <= 0) {
                            break;
                        }

                        $lotQuantity = (string) $lot['current_quantity'];
                        $take = Money::compare($lotQuantity, $remaining, 3) >= 0
                            ? $remaining
                            : $lotQuantity;

                        if (Money::compare($take, '0', 3) <= 0) {
                            continue;
                        }

                        Sale::createItemLot($saleItemId, (int) $lot['id'], $take);
                        ProductLot::setCurrentQuantity(
                            (int) $lot['id'],
                            Money::subtract($lotQuantity, $take, 3)
                        );

                        $newStock = Money::subtract((string) $product['current_stock'], $take, 3);
                        Product::updateStock($productId, $newStock);
                        $product['current_stock'] = $newStock;
                        $products[$productId]['current_stock'] = $newStock;

                        StockMovement::createForSale(
                            $productId,
                            (int) $lot['id'],
                            Money::subtract('0', $take, 3),
                            $newStock,
                            $saleId,
                            $userId,
                            null
                        );

                        $remaining = Money::subtract($remaining, $take, 3);
                    }

                    if (Money::compare($remaining, '0', 3) > 0) {
                        throw new DomainException('Não foi possível alocar os lotes da venda.');
                    }
                } else {
                    $stock = (string) $product['current_stock'];

                    if (Money::compare($stock, $quantity, 3) < 0) {
                        throw new DomainException(
                            'Estoque disponível insuficiente para o produto ' . $product['name'] . '.'
                        );
                    }

                    $newStock = Money::subtract($stock, $quantity, 3);
                    Product::updateStock($productId, $newStock);
                    $products[$productId]['current_stock'] = $newStock;

                    StockMovement::createForSale(
                        $productId,
                        null,
                        Money::subtract('0', $quantity, 3),
                        $newStock,
                        $saleId,
                        $userId,
                        null
                    );
                }

                $total = Money::add($total, $lineTotal, 2);
            }

            Sale::updateTotal($saleId, $total);

            $pdo->commit();

            return $saleId;
        } catch (Throwable $exception) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            throw $exception;
        }
    }

    public function cancel(int $saleId, int $userId, string $reason, string $canceledAt): void
    {
        $pdo = Database::connection();
        $pdo->beginTransaction();

        try {
            $sale = Sale::lockForUpdate($saleId);

            if ($sale === null) {
                throw new DomainException('Venda não encontrada.');
            }

            if ($sale['status'] !== 'concluida') {
                throw new DomainException('Esta venda já foi cancelada.');
            }

            $items = Sale::items($saleId);
            $allocations = Sale::itemLots($saleId);
            $allocationsByItem = [];

            foreach ($allocations as $allocation) {
                $allocationsByItem[(int) $allocation['sale_item_id']][] = $allocation;
            }

            $productIds = array_values(array_unique(array_map(
                static fn (array $item): int => (int) $item['product_id'],
                $items
            )));
            sort($productIds, SORT_NUMERIC);

            $products = [];

            foreach ($productIds as $productId) {
                $product = Product::lockForUpdate($productId);

                if ($product === null) {
                    throw new DomainException('Produto da venda não encontrado.');
                }

                $products[$productId] = $product;
            }

            foreach ($items as $item) {
                $productId = (int) $item['product_id'];
                $product = $products[$productId];
                $stock = (string) $product['current_stock'];
                $itemAllocations = $allocationsByItem[(int) $item['id']] ?? [];

                if ($itemAllocations === [] && (int) $product['controls_lots'] === 1) {
                    throw new DomainException('Não foi possível localizar os lotes consumidos nesta venda.');
                }

                if ($itemAllocations !== []) {
                    foreach ($itemAllocations as $allocation) {
                        $quantity = (string) $allocation['quantity'];

                        ProductLot::increaseCurrentQuantity(
                            (int) $allocation['product_lot_id'],
                            $quantity
                        );

                        $newStock = Money::add($stock, $quantity, 3);
                        Product::updateStock($productId, $newStock);
                        $stock = $newStock;
                        $products[$productId]['current_stock'] = $newStock;

                        StockMovement::createForSaleReturn(
                            $productId,
                            (int) $allocation['product_lot_id'],
                            $quantity,
                            $newStock,
                            $saleId,
                            $userId,
                            $reason
                        );
                    }
                } else {
                    $quantity = (string) $item['quantity'];
                    $newStock = Money::add($stock, $quantity, 3);
                    Product::updateStock($productId, $newStock);
                    $products[$productId]['current_stock'] = $newStock;

                    StockMovement::createForSaleReturn(
                        $productId,
                        null,
                        $quantity,
                        $newStock,
                        $saleId,
                        $userId,
                        $reason
                    );
                }
            }

            Sale::cancel($saleId, $userId, $reason, $canceledAt);

            $pdo->commit();
        } catch (Throwable $exception) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            throw $exception;
        }
    }

    private function consolidateItems(array $items): array
    {
        $consolidated = [];

        foreach ($items as $item) {
            $productId = (int) $item['product_id'];
            $quantity = (string) $item['quantity'];

            if (Money::compare($quantity, '0', 3) <= 0) {
                throw new DomainException('A quantidade da venda deve ser maior que zero.');
            }

            if (!isset($consolidated[$productId])) {
                $consolidated[$productId] = '0.000';
            }

            $consolidated[$productId] = Money::add($consolidated[$productId], $quantity, 3);
        }

        return $consolidated;
    }
}

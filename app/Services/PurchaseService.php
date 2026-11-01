<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use App\Models\Product;
use App\Models\ProductLot;
use App\Models\Purchase;
use App\Models\StockMovement;
use App\Models\Supplier;
use DateTimeImmutable;
use DomainException;
use Throwable;

final class PurchaseService
{
    public function paginate(array $filters, int $page): array
    {
        return Purchase::paginate($filters, $page);
    }

    public function find(int $id): array
    {
        $purchase = Purchase::find($id);

        if ($purchase === null) {
            throw new DomainException('Compra não encontrada.');
        }

        return $purchase;
    }

    public function items(int $id): array
    {
        return Purchase::items($id);
    }

    public function create(array $header, array $items, int $userId): int
    {
        if ($items === []) {
            throw new DomainException('Adicione pelo menos um item à compra.');
        }

        $pdo = Database::connection();
        $pdo->beginTransaction();

        try {
            $supplierId = $header['supplier_id'] !== null ? (int) $header['supplier_id'] : null;

            if ($supplierId !== null) {
                $supplier = Supplier::find($supplierId);

                if ($supplier === null || (int) $supplier['is_active'] !== 1) {
                    throw new DomainException('Fornecedor inválido ou inativo.');
                }
            }

            $productIds = array_values(array_unique(array_map(
                static fn (array $item): int => (int) $item['product_id'],
                $items
            )));
            sort($productIds, SORT_NUMERIC);

            $products = [];

            foreach ($productIds as $productId) {
                $product = Product::lockForUpdate($productId);

                if ($product === null || (int) $product['is_active'] !== 1) {
                    throw new DomainException('Produto inválido ou inativo na compra.');
                }

                $products[$productId] = $product;
            }

            $purchaseId = Purchase::create([
                'supplier_id' => $supplierId,
                'user_id' => $userId,
                'purchased_at' => $header['purchased_at'],
                'document_number' => $header['document_number'],
                'notes' => $header['notes'],
                'total_cost' => '0.00',
            ]);

            $total = '0.00';
            $seenLots = [];

            foreach ($items as $item) {
                $productId = (int) $item['product_id'];
                $product = $products[$productId];
                $quantity = (string) $item['quantity'];
                $unitCost = (string) $item['unit_cost'];
                $lotCode = $item['lot_code'] ?? null;
                $expiresAt = $item['expires_at'] ?? null;

                if (bccomp($quantity, '0', 3) <= 0) {
                    throw new DomainException('A quantidade da compra deve ser maior que zero.');
                }

                if (bccomp($unitCost, '0', 2) < 0) {
                    throw new DomainException('O custo unitário não pode ser negativo.');
                }

                if ((int) $product['controls_lots'] === 1) {
                    $lotCode = trim((string) $lotCode);
                    $expiresAt = trim((string) $expiresAt);

                    if ($lotCode === '' || $expiresAt === '') {
                        throw new DomainException('Informe lote e validade para os produtos que controlam lote.');
                    }

                    $date = DateTimeImmutable::createFromFormat('!Y-m-d', $expiresAt);

                    if ($date === false || $date->format('Y-m-d') !== $expiresAt) {
                        throw new DomainException('Informe uma validade de lote válida.');
                    }

                    $lotKey = $productId . '|' . strtolower($lotCode);

                    if (isset($seenLots[$lotKey])) {
                        throw new DomainException('Há lotes repetidos para o mesmo produto na compra.');
                    }

                    $seenLots[$lotKey] = true;
                } else {
                    $lotCode = null;
                    $expiresAt = null;
                }

                $lineTotal = $this->roundMoney(bcmul($quantity, $unitCost, 6));
                $purchaseItemId = Purchase::createItem(
                    $purchaseId,
                    $productId,
                    $quantity,
                    $unitCost,
                    $lineTotal
                );
                $total = bcadd($total, $lineTotal, 2);

                $lotId = null;

                if ((int) $product['controls_lots'] === 1) {
                    $lotId = ProductLot::createForPurchase(
                        $productId,
                        $purchaseItemId,
                        $lotCode,
                        $expiresAt,
                        $quantity,
                        $unitCost
                    );
                }

                $oldStock = (string) $product['current_stock'];
                $oldCost = (string) $product['cost_price'];
                $newStock = bcadd($oldStock, $quantity, 3);
                $newCost = $this->weightedAverageCost($oldStock, $oldCost, $quantity, $unitCost);

                Product::updateStockAndCost($productId, $newStock, $newCost);
                $products[$productId]['current_stock'] = $newStock;
                $products[$productId]['cost_price'] = $newCost;

                StockMovement::createForPurchase(
                    $productId,
                    $lotId,
                    $quantity,
                    $newStock,
                    $purchaseId,
                    $userId,
                    $header['document_number']
                );
            }

            Purchase::updateTotal($purchaseId, $total);

            $pdo->commit();

            return $purchaseId;
        } catch (Throwable $exception) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            throw $exception;
        }
    }

    private function weightedAverageCost(
        string $oldStock,
        string $oldCost,
        string $quantity,
        string $unitCost
    ): string {
        $newStock = bcadd($oldStock, $quantity, 3);

        if (bccomp($newStock, '0', 3) <= 0) {
            return $this->roundMoney($oldCost);
        }

        $oldValue = bcmul($oldStock, $oldCost, 6);
        $newValue = bcmul($quantity, $unitCost, 6);
        $numerator = bcadd($oldValue, $newValue, 6);
        $average = bcdiv($numerator, $newStock, 6);

        return $this->roundMoney($average);
    }

    private function roundMoney(string $value): string
    {
        $negative = str_starts_with($value, '-');

        if ($negative) {
            $value = substr($value, 1);
        }

        $parts = explode('.', $value, 2);
        $integer = $parts[0] !== '' ? $parts[0] : '0';
        $decimal = str_pad($parts[1] ?? '', 3, '0');
        $roundDigit = (int) $decimal[2];
        $kept = substr($decimal, 0, 2);
        $base = $integer . '.' . $kept;

        if ($roundDigit >= 5) {
            $base = bcadd($base, '0.01', 2);
        }

        return ($negative ? '-' : '') . $base;
    }
}

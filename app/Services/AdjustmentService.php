<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use App\Core\Money;
use App\Models\Product;
use App\Models\ProductLot;
use App\Models\StockAdjustment;
use App\Models\StockMovement;
use DateTimeImmutable;
use DomainException;
use Throwable;

final class AdjustmentService
{
    private const TYPES = ['ajuste_positivo', 'ajuste_negativo', 'perda'];

    public function paginate(array $filters, int $page): array
    {
        return StockAdjustment::paginate($filters, $page);
    }

    public function create(
        string $adjustmentType,
        int $productId,
        string $quantity,
        ?string $unitCost,
        ?string $lotCode,
        ?string $expiresAt,
        string $reason,
        int $userId
    ): int {
        if (!in_array($adjustmentType, self::TYPES, true)) {
            throw new DomainException('Tipo de ajuste inválido.');
        }

        if (Money::compare($quantity, '0', 3) <= 0) {
            throw new DomainException('A quantidade deve ser maior que zero.');
        }

        $reason = trim($reason);

        if ($reason === '') {
            throw new DomainException('Informe o motivo do ajuste.');
        }

        $pdo = Database::connection();
        $pdo->beginTransaction();

        try {
            $product = Product::lockForUpdate($productId);

            if ($product === null || (int) $product['is_active'] !== 1) {
                throw new DomainException('Produto inválido ou inativo.');
            }

            $isPositive = $adjustmentType === 'ajuste_positivo';
            $controlsLots = (int) $product['controls_lots'] === 1;
            $providedCost = $isPositive ? $unitCost : null;

            if ($providedCost !== null && Money::compare($providedCost, '0', 2) < 0) {
                throw new DomainException('O custo unitário não pode ser negativo.');
            }

            if ($isPositive && $controlsLots) {
                $lotCode = trim((string) $lotCode);
                $expiresAt = trim((string) $expiresAt);

                if ($lotCode === '' || $expiresAt === '') {
                    throw new DomainException('Informe lote e validade para o ajuste positivo.');
                }

                $date = DateTimeImmutable::createFromFormat('!Y-m-d', $expiresAt);

                if ($date === false || $date->format('Y-m-d') !== $expiresAt) {
                    throw new DomainException('Informe uma validade de lote válida.');
                }
            } else {
                $lotCode = null;
                $expiresAt = null;
            }

            $adjustmentId = StockAdjustment::create([
                'product_id' => $productId,
                'user_id' => $userId,
                'adjustment_type' => $adjustmentType,
                'quantity' => $quantity,
                'unit_cost' => $providedCost,
                'reason' => $reason,
            ]);

            $oldStock = (string) $product['current_stock'];
            $oldCost = (string) $product['cost_price'];

            if ($isPositive) {
                $newStock = Money::add($oldStock, $quantity, 3);
                $newCost = $providedCost !== null
                    ? $this->weightedAverageCost($oldStock, $oldCost, $quantity, $providedCost)
                    : $oldCost;

                Product::updateStockAndCost($productId, $newStock, $newCost);

                $lotId = null;

                if ($controlsLots) {
                    $lotId = ProductLot::createManual(
                        $productId,
                        $lotCode,
                        $expiresAt,
                        $quantity,
                        $providedCost ?? $oldCost
                    );
                }

                StockMovement::createForAdjustment(
                    $productId,
                    $lotId,
                    $adjustmentType,
                    $quantity,
                    $newStock,
                    $adjustmentId,
                    $userId,
                    $reason
                );
            } else {
                if ($controlsLots) {
                    $lots = ProductLot::lockForAdjustment($productId);
                    $available = '0.000';

                    foreach ($lots as $lot) {
                        $available = Money::add($available, (string) $lot['current_quantity'], 3);
                    }

                    if (Money::compare($available, $quantity, 3) < 0) {
                        throw new DomainException('Estoque insuficiente para o ajuste ou perda.');
                    }

                    $remaining = $quantity;
                    $stock = $oldStock;

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

                        ProductLot::setCurrentQuantity(
                            (int) $lot['id'],
                            Money::subtract($lotQuantity, $take, 3)
                        );

                        $newStock = Money::subtract($stock, $take, 3);
                        Product::updateStock($productId, $newStock);
                        $stock = $newStock;

                        StockMovement::createForAdjustment(
                            $productId,
                            (int) $lot['id'],
                            $adjustmentType,
                            Money::subtract('0', $take, 3),
                            $newStock,
                            $adjustmentId,
                            $userId,
                            $reason
                        );

                        $remaining = Money::subtract($remaining, $take, 3);
                    }

                    if (Money::compare($remaining, '0', 3) > 0) {
                        throw new DomainException('Não foi possível baixar os lotes do ajuste.');
                    }
                } else {
                    if (Money::compare($oldStock, $quantity, 3) < 0) {
                        throw new DomainException('Estoque insuficiente para o ajuste ou perda.');
                    }

                    $newStock = Money::subtract($oldStock, $quantity, 3);
                    Product::updateStock($productId, $newStock);

                    StockMovement::createForAdjustment(
                        $productId,
                        null,
                        $adjustmentType,
                        Money::subtract('0', $quantity, 3),
                        $newStock,
                        $adjustmentId,
                        $userId,
                        $reason
                    );
                }
            }

            $pdo->commit();

            return $adjustmentId;
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
        $newStock = Money::add($oldStock, $quantity, 3);

        if (Money::compare($newStock, '0', 3) <= 0) {
            return Money::round($oldCost, 2);
        }

        $oldValue = bcmul($oldStock, $oldCost, 6);
        $newValue = bcmul($quantity, $unitCost, 6);
        $numerator = bcadd($oldValue, $newValue, 6);
        $average = bcdiv($numerator, $newStock, 6);

        return Money::round($average, 2);
    }
}

<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Category;
use App\Models\Product;
use DomainException;
use PDOException;

final class ProductService
{
    public const SKU_TAKEN = 1;
    public const BARCODE_TAKEN = 2;
    public const CATEGORY_INVALID = 3;
    public const LOTS_IMMUTABLE = 4;
    public const NOT_FOUND = 5;

    public function paginate(array $filters, int $page): array
    {
        return Product::paginate($filters, $page);
    }

    public function find(int $id): array
    {
        $product = Product::find($id);

        if ($product === null) {
            throw new DomainException('Produto não encontrado.', self::NOT_FOUND);
        }

        return $product;
    }

    public function hasMovements(int $id): bool
    {
        return Product::hasMovements($id);
    }

    public function create(array $data): int
    {
        $data = $this->normalize($data);

        $this->validateCategory($data['category_id'], true);
        $this->validateSku($data['sku']);
        $this->validateBarcode($data['barcode']);

        try {
            return Product::create($data);
        } catch (PDOException $exception) {
            $this->throwDuplicateException($exception);
            throw $exception;
        }
    }

    public function update(int $id, array $data): void
    {
        $current = $this->find($id);
        $data = $this->normalize($data);

        $this->validateCategory($data['category_id'], false, (int) $current['category_id']);
        $this->validateSku($data['sku'], $id);
        $this->validateBarcode($data['barcode'], $id);

        if (
            (int) $data['controls_lots'] !== (int) $current['controls_lots']
            && Product::hasMovements($id)
        ) {
            throw new DomainException(
                'O controle de lote não pode ser alterado após o primeiro movimento.',
                self::LOTS_IMMUTABLE
            );
        }

        try {
            Product::update($id, $data);
        } catch (PDOException $exception) {
            $this->throwDuplicateException($exception);
            throw $exception;
        }
    }

    public function setActive(int $id, bool $active): void
    {
        $this->find($id);
        Product::setActive($id, $active);
    }

    private function normalize(array $data): array
    {
        $data['name'] = trim((string) ($data['name'] ?? ''));
        $data['sku'] = trim((string) ($data['sku'] ?? ''));
        $data['barcode'] = trim((string) ($data['barcode'] ?? ''));
        $data['sku'] = $data['sku'] === '' ? null : $data['sku'];
        $data['barcode'] = $data['barcode'] === '' ? null : $data['barcode'];
        $data['category_id'] = $data['category_id'] !== null ? (int) $data['category_id'] : null;
        $data['controls_lots'] = (int) ($data['controls_lots'] ?? 0);

        return $data;
    }

    private function validateCategory(?int $categoryId, bool $requireActive, ?int $currentCategoryId = null): void
    {
        if ($categoryId === null) {
            return;
        }

        $category = Category::find($categoryId);

        if ($category === null) {
            throw new DomainException('Selecione uma categoria válida.', self::CATEGORY_INVALID);
        }

        if ($requireActive && (int) $category['is_active'] !== 1) {
            throw new DomainException('Selecione uma categoria ativa.', self::CATEGORY_INVALID);
        }

        if (
            !$requireActive
            && (int) $category['is_active'] !== 1
            && $categoryId !== $currentCategoryId
        ) {
            throw new DomainException('Selecione uma categoria ativa.', self::CATEGORY_INVALID);
        }
    }

    private function validateSku(?string $sku, ?int $exceptId = null): void
    {
        if ($sku !== null && Product::skuExists($sku, $exceptId)) {
            throw new DomainException('Já existe um produto com este SKU.', self::SKU_TAKEN);
        }
    }

    private function validateBarcode(?string $barcode, ?int $exceptId = null): void
    {
        if ($barcode !== null && Product::barcodeExists($barcode, $exceptId)) {
            throw new DomainException(
                'Já existe um produto com este código de barras.',
                self::BARCODE_TAKEN
            );
        }
    }

    private function throwDuplicateException(PDOException $exception): void
    {
        if ($exception->getCode() !== '23000') {
            return;
        }

        $message = $exception->getMessage();

        if (str_contains($message, 'uq_products_sku')) {
            throw new DomainException('Já existe um produto com este SKU.', self::SKU_TAKEN);
        }

        if (str_contains($message, 'uq_products_barcode')) {
            throw new DomainException(
                'Já existe um produto com este código de barras.',
                self::BARCODE_TAKEN
            );
        }
    }
}

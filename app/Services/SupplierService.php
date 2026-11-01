<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Supplier;
use DomainException;

final class SupplierService
{
    public const NOT_FOUND = 1;

    public function all(?string $search = null): array
    {
        return Supplier::all($search);
    }

    public function find(int $id): array
    {
        $supplier = Supplier::find($id);

        if ($supplier === null) {
            throw new DomainException('Fornecedor não encontrado.', self::NOT_FOUND);
        }

        return $supplier;
    }

    public function create(array $data): int
    {
        return Supplier::create($this->normalize($data));
    }

    public function update(int $id, array $data): void
    {
        $this->find($id);
        Supplier::update($id, $this->normalize($data));
    }

    public function setActive(int $id, bool $active): void
    {
        $this->find($id);
        Supplier::setActive($id, $active);
    }

    private function normalize(array $data): array
    {
        $data['name'] = trim((string) ($data['name'] ?? ''));

        foreach (['document', 'phone', 'email', 'notes'] as $field) {
            $value = trim((string) ($data[$field] ?? ''));
            $data[$field] = $value === '' ? null : $value;
        }

        return $data;
    }
}

<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Category;
use DomainException;
use PDOException;

final class CategoryService
{
    public const NAME_TAKEN = 1;
    public const NOT_FOUND = 2;

    public function all(): array
    {
        return Category::all();
    }

    public function allActive(): array
    {
        return Category::allActive();
    }

    public function find(int $id): array
    {
        $category = Category::find($id);

        if ($category === null) {
            throw new DomainException('Categoria não encontrada.', self::NOT_FOUND);
        }

        return $category;
    }

    public function create(string $name, ?string $description, bool $defaultControlsLots): int
    {
        $name = trim($name);
        $description = $this->normalizeDescription($description);

        if (Category::nameExists($name)) {
            throw new DomainException('Já existe uma categoria com este nome.', self::NAME_TAKEN);
        }

        try {
            return Category::create($name, $description, $defaultControlsLots);
        } catch (PDOException $exception) {
            if ($exception->getCode() === '23000') {
                throw new DomainException('Já existe uma categoria com este nome.', self::NAME_TAKEN);
            }

            throw $exception;
        }
    }

    public function update(int $id, string $name, ?string $description, bool $defaultControlsLots): void
    {
        $this->find($id);

        $name = trim($name);
        $description = $this->normalizeDescription($description);

        if (Category::nameExists($name, $id)) {
            throw new DomainException('Já existe uma categoria com este nome.', self::NAME_TAKEN);
        }

        try {
            Category::update($id, $name, $description, $defaultControlsLots);
        } catch (PDOException $exception) {
            if ($exception->getCode() === '23000') {
                throw new DomainException('Já existe uma categoria com este nome.', self::NAME_TAKEN);
            }

            throw $exception;
        }
    }

    public function setActive(int $id, bool $active): void
    {
        $this->find($id);
        Category::setActive($id, $active);
    }

    private function normalizeDescription(?string $description): ?string
    {
        $description = trim((string) $description);

        return $description === '' ? null : $description;
    }
}

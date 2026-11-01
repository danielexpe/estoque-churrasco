<?php

use App\Core\Csrf;
use App\Models\Product;

$isEdit = $isEdit ?? false;
$errors = $errors ?? [];
$generalError = $generalError ?? null;
$hasMovements = $hasMovements ?? false;
$cancelUrl = $isEdit && isset($product) ? '/produtos/' . (int) $product['id'] : '/produtos';
?>
<?php if ($generalError !== null): ?>
    <div class="alert alert-danger" role="alert"><?= e($generalError) ?></div>
<?php endif; ?>

<form method="post" action="<?= e($action) ?>" novalidate>
    <?= Csrf::field() ?>

    <div class="row g-3">
        <div class="col-md-8">
            <label for="name" class="form-label">Nome</label>
            <input
                type="text"
                class="form-control <?= isset($errors['name']) ? 'is-invalid' : '' ?>"
                id="name"
                name="name"
                value="<?= e($form['name'] ?? '') ?>"
                maxlength="160"
                required
            >
            <?php if (isset($errors['name'])): ?>
                <div class="invalid-feedback"><?= e($errors['name']) ?></div>
            <?php endif; ?>
        </div>

        <div class="col-md-4">
            <label for="category_id" class="form-label">Categoria</label>
            <select
                class="form-select <?= isset($errors['category_id']) ? 'is-invalid' : '' ?>"
                id="category_id"
                name="category_id"
                data-lots-default-source
            >
                <option value="">Sem categoria</option>
                <?php foreach ($categories as $category): ?>
                    <option
                        value="<?= (int) $category['id'] ?>"
                        data-default-lots="<?= (int) $category['default_controls_lots'] ?>"
                        <?= (string) ($form['category_id'] ?? '') === (string) $category['id'] ? 'selected' : '' ?>
                    >
                        <?= e($category['name']) ?><?= (int) ($category['is_active'] ?? 1) === 1 ? '' : ' (inativa)' ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <?php if (isset($errors['category_id'])): ?>
                <div class="invalid-feedback"><?= e($errors['category_id']) ?></div>
            <?php endif; ?>
        </div>

        <div class="col-md-4">
            <label for="sku" class="form-label">SKU</label>
            <input
                type="text"
                class="form-control <?= isset($errors['sku']) ? 'is-invalid' : '' ?>"
                id="sku"
                name="sku"
                value="<?= e($form['sku'] ?? '') ?>"
                maxlength="60"
            >
            <?php if (isset($errors['sku'])): ?>
                <div class="invalid-feedback"><?= e($errors['sku']) ?></div>
            <?php endif; ?>
        </div>

        <div class="col-md-4">
            <label for="barcode" class="form-label">Código de barras</label>
            <input
                type="text"
                class="form-control <?= isset($errors['barcode']) ? 'is-invalid' : '' ?>"
                id="barcode"
                name="barcode"
                value="<?= e($form['barcode'] ?? '') ?>"
                maxlength="60"
            >
            <?php if (isset($errors['barcode'])): ?>
                <div class="invalid-feedback"><?= e($errors['barcode']) ?></div>
            <?php endif; ?>
        </div>

        <div class="col-md-4">
            <label for="unit" class="form-label">Unidade</label>
            <select class="form-select <?= isset($errors['unit']) ? 'is-invalid' : '' ?>" id="unit" name="unit" required>
                <?php foreach (Product::UNITS as $unit): ?>
                    <option value="<?= e($unit) ?>" <?= ($form['unit'] ?? '') === $unit ? 'selected' : '' ?>>
                        <?= e($unit) ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <?php if (isset($errors['unit'])): ?>
                <div class="invalid-feedback"><?= e($errors['unit']) ?></div>
            <?php endif; ?>
        </div>

        <div class="col-md-4">
            <label for="sale_price" class="form-label">Preço de venda (R$)</label>
            <input
                type="number"
                step="0.01"
                min="0"
                class="form-control <?= isset($errors['sale_price']) ? 'is-invalid' : '' ?>"
                id="sale_price"
                name="sale_price"
                value="<?= e($form['sale_price'] ?? '0.00') ?>"
                required
            >
            <?php if (isset($errors['sale_price'])): ?>
                <div class="invalid-feedback"><?= e($errors['sale_price']) ?></div>
            <?php endif; ?>
        </div>

        <div class="col-md-4">
            <label for="cost_price" class="form-label">Custo atual (R$)</label>
            <input
                type="number"
                step="0.01"
                min="0"
                class="form-control <?= isset($errors['cost_price']) ? 'is-invalid' : '' ?>"
                id="cost_price"
                name="cost_price"
                value="<?= e($form['cost_price'] ?? '0.00') ?>"
                required
            >
            <?php if (isset($errors['cost_price'])): ?>
                <div class="invalid-feedback"><?= e($errors['cost_price']) ?></div>
            <?php endif; ?>
            <div class="form-text">Será recalculado pelas compras de reposição.</div>
        </div>

        <div class="col-md-4">
            <label for="min_stock" class="form-label">Estoque mínimo</label>
            <input
                type="number"
                step="0.001"
                min="0"
                class="form-control <?= isset($errors['min_stock']) ? 'is-invalid' : '' ?>"
                id="min_stock"
                name="min_stock"
                value="<?= e($form['min_stock'] ?? '0.000') ?>"
                required
            >
            <?php if (isset($errors['min_stock'])): ?>
                <div class="invalid-feedback"><?= e($errors['min_stock']) ?></div>
            <?php endif; ?>
        </div>

        <?php if ($isEdit && isset($product)): ?>
            <div class="col-md-4">
                <label class="form-label">Saldo atual</label>
                <input
                    type="text"
                    class="form-control"
                    value="<?= e(quantity($product['current_stock'], (string) $product['unit'])) ?>"
                    disabled
                >
                <div class="form-text">Alterado por compras, vendas e ajustes.</div>
            </div>
        <?php endif; ?>
    </div>

    <div class="form-check mt-4">
        <?php if ($hasMovements): ?>
            <input type="hidden" name="controls_lots" value="<?= !empty($form['controls_lots']) ? '1' : '0' ?>">
            <input class="form-check-input" type="checkbox" id="controls_lots" <?= !empty($form['controls_lots']) ? 'checked' : '' ?> disabled>
        <?php else: ?>
            <input type="hidden" name="controls_lots" value="0">
            <input
                class="form-check-input"
                type="checkbox"
                id="controls_lots"
                name="controls_lots"
                value="1"
                <?= !empty($form['controls_lots']) ? 'checked' : '' ?>
            >
        <?php endif; ?>
        <label class="form-check-label" for="controls_lots">Controla lote e validade</label>
        <div class="form-text">
            <?php if ($hasMovements): ?>
                Não pode ser alterado após o primeiro movimento de estoque.
            <?php else: ?>
                Carnes são marcadas automaticamente pelo padrão da categoria.
            <?php endif; ?>
        </div>
    </div>

    <div class="d-flex flex-wrap gap-2 mt-4">
        <button type="submit" class="btn btn-dark">
            <?= $isEdit ? 'Salvar alterações' : 'Criar produto' ?>
        </button>
        <a href="<?= e($cancelUrl) ?>" class="btn btn-outline-secondary">Cancelar</a>
    </div>
</form>

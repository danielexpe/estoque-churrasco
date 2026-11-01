<?php

$item = $item ?? [];
$rowErrors = $rowErrors ?? [];
$products = $products ?? [];
$selectedProductId = (string) ($item['product_id'] ?? '');
$selectedProduct = null;

foreach ($products as $product) {
    if ((string) $product['id'] === $selectedProductId) {
        $selectedProduct = $product;
        break;
    }
}

$quantity = (string) ($item['quantity'] ?? '');
$unitCost = (string) ($item['unit_cost'] ?? '');
$quantityNumber = is_numeric(str_replace(',', '.', $quantity))
    ? (float) str_replace(',', '.', $quantity)
    : 0.0;
$costNumber = is_numeric(str_replace(',', '.', $unitCost))
    ? (float) str_replace(',', '.', $unitCost)
    : 0.0;

$invalid = static fn (string $field): string => isset($rowErrors[$field]) ? 'is-invalid' : '';
?>
<tr class="purchase-item-row">
    <td>
        <select
            name="product_id[]"
            class="form-select form-select-sm product-select <?= $invalid('product_id') ?>"
            required
        >
            <option value="">Selecione</option>
            <?php foreach ($products as $product): ?>
                <option
                    value="<?= (int) $product['id'] ?>"
                    data-controls-lots="<?= (int) $product['controls_lots'] ?>"
                    data-unit="<?= e($product['unit']) ?>"
                    <?= $selectedProductId === (string) $product['id'] ? 'selected' : '' ?>
                >
                    <?= e($product['name']) ?>
                </option>
            <?php endforeach; ?>
        </select>
        <?php if (isset($rowErrors['product_id'])): ?>
            <div class="invalid-feedback d-block"><?= e($rowErrors['product_id']) ?></div>
        <?php endif; ?>
    </td>

    <td>
        <div class="input-group input-group-sm">
            <input
                type="number"
                step="0.001"
                min="0.001"
                name="quantity[]"
                class="form-control quantity-input <?= $invalid('quantity') ?>"
                value="<?= e($quantity) ?>"
                required
            >
            <span class="input-group-text unit-label"><?= e($selectedProduct['unit'] ?? '') ?></span>
        </div>
        <?php if (isset($rowErrors['quantity'])): ?>
            <div class="invalid-feedback d-block"><?= e($rowErrors['quantity']) ?></div>
        <?php endif; ?>
    </td>

    <td>
        <input
            type="number"
            step="0.01"
            min="0"
            name="unit_cost[]"
            class="form-control form-control-sm cost-input <?= $invalid('unit_cost') ?>"
            value="<?= e($unitCost) ?>"
            required
        >
        <?php if (isset($rowErrors['unit_cost'])): ?>
            <div class="invalid-feedback d-block"><?= e($rowErrors['unit_cost']) ?></div>
        <?php endif; ?>
    </td>

    <td>
        <input
            type="text"
            name="lot_code[]"
            class="form-control form-control-sm lot-code <?= $invalid('lot_code') ?>"
            value="<?= e($item['lot_code'] ?? '') ?>"
            maxlength="80"
            placeholder="Lote"
        >
        <?php if (isset($rowErrors['lot_code'])): ?>
            <div class="invalid-feedback d-block"><?= e($rowErrors['lot_code']) ?></div>
        <?php endif; ?>
    </td>

    <td>
        <input
            type="date"
            name="expires_at[]"
            class="form-control form-control-sm lot-expiry <?= $invalid('expires_at') ?>"
            value="<?= e($item['expires_at'] ?? '') ?>"
        >
        <?php if (isset($rowErrors['expires_at'])): ?>
            <div class="invalid-feedback d-block"><?= e($rowErrors['expires_at']) ?></div>
        <?php endif; ?>
    </td>

    <td class="text-end line-total"><?= e(money($quantityNumber * $costNumber)) ?></td>

    <td class="text-end">
        <button type="button" class="btn btn-sm btn-outline-danger remove-row">Remover</button>
    </td>
</tr>

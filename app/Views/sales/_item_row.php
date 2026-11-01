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

$unit = (string) ($selectedProduct['unit'] ?? '');
$price = (string) ($selectedProduct['sale_price'] ?? '0.00');
$available = (string) ($selectedProduct['available_quantity'] ?? '0');
$quantity = (string) ($item['quantity'] ?? '');
$quantityNumber = is_numeric(str_replace(',', '.', $quantity))
    ? (float) str_replace(',', '.', $quantity)
    : 0.0;

$invalid = static fn (string $field): string => isset($rowErrors[$field]) ? 'is-invalid' : '';
?>
<tr class="sale-item-row">
    <td>
        <select
            name="product_id[]"
            class="form-select form-select-sm sale-product-select <?= $invalid('product_id') ?>"
            required
        >
            <option value="">Selecione</option>
            <?php foreach ($products as $product): ?>
                <option
                    value="<?= (int) $product['id'] ?>"
                    data-price="<?= e($product['sale_price']) ?>"
                    data-unit="<?= e($product['unit']) ?>"
                    data-available="<?= e($product['available_quantity']) ?>"
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
                class="form-control sale-quantity <?= $invalid('quantity') ?>"
                value="<?= e($quantity) ?>"
                required
            >
            <span class="input-group-text sale-unit"><?= e($unit) ?></span>
        </div>
        <?php if (isset($rowErrors['quantity'])): ?>
            <div class="invalid-feedback d-block"><?= e($rowErrors['quantity']) ?></div>
        <?php endif; ?>
    </td>

    <td class="sale-price"><?= e(money($price)) ?></td>

    <td class="sale-available small text-secondary"><?= e(quantity($available, $unit)) ?></td>

    <td class="text-end sale-line-total"><?= e(money($quantityNumber * (float) $price)) ?></td>

    <td class="text-end">
        <button type="button" class="btn btn-sm btn-outline-danger remove-sale-row">Remover</button>
    </td>
</tr>

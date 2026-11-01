<?php

use App\Core\Csrf;
?>
<div class="mb-4">
    <h1 class="h3 mb-1">Nova venda</h1>
    <p class="text-secondary mb-0">A venda é registrada no momento da confirmação e usa o preço fixo do cadastro.</p>
</div>

<?php if ($generalError !== null): ?>
    <div class="alert alert-danger" role="alert"><?= e($generalError) ?></div>
<?php endif; ?>

<?php if (isset($errors['items'])): ?>
    <div class="alert alert-danger" role="alert"><?= e($errors['items']) ?></div>
<?php endif; ?>

<form method="post" action="/vendas">
    <?= Csrf::field() ?>

    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white d-flex flex-wrap justify-content-between align-items-center gap-2">
            <span class="fw-semibold">Itens da venda</span>
            <button type="button" class="btn btn-sm btn-outline-dark" id="add-sale-item">Adicionar item</button>
        </div>

        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th style="min-width: 240px;">Produto</th>
                        <th style="min-width: 150px;">Quantidade</th>
                        <th>Preço</th>
                        <th>Disponível</th>
                        <th class="text-end">Total</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody id="sale-items">
                    <?php foreach ($form['items'] as $index => $item): ?>
                        <?php
                        $rowErrors = [];
                        foreach (['product_id', 'quantity'] as $field) {
                            $key = 'items.' . $index . '.' . $field;
                            if (isset($errors[$key])) {
                                $rowErrors[$field] = $errors[$key];
                            }
                        }
                        ?>
                        <?php require __DIR__ . '/_item_row.php'; ?>
                    <?php endforeach; ?>
                </tbody>
                <tfoot>
                    <tr>
                        <th colspan="4" class="text-end">Total da venda</th>
                        <th class="text-end" id="sale-total">R$ 0,00</th>
                        <th></th>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>

    <div class="d-flex flex-wrap gap-2 mt-3">
        <button type="submit" class="btn btn-dark">Registrar venda</button>
        <a href="/vendas" class="btn btn-outline-secondary">Cancelar</a>
    </div>
</form>

<template id="sale-item-template">
    <?php
    $item = [];
    $rowErrors = [];
    require __DIR__ . '/_item_row.php';
    ?>
</template>

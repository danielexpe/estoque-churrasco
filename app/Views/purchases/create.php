<?php

use App\Core\Csrf;
?>
<div class="mb-4">
    <h1 class="h3 mb-1">Nova compra</h1>
    <p class="text-secondary mb-0">Registre a entrada dos itens, os custos e os lotes.</p>
</div>

<?php if ($generalError !== null): ?>
    <div class="alert alert-danger" role="alert"><?= e($generalError) ?></div>
<?php endif; ?>

<?php if (isset($errors['items'])): ?>
    <div class="alert alert-danger" role="alert"><?= e($errors['items']) ?></div>
<?php endif; ?>

<form method="post" action="/compras">
    <?= Csrf::field() ?>

    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-4">
                    <label for="supplier_id" class="form-label">Fornecedor</label>
                    <select class="form-select <?= isset($errors['supplier_id']) ? 'is-invalid' : '' ?>" id="supplier_id" name="supplier_id">
                        <option value="">Sem fornecedor</option>
                        <?php foreach ($suppliers as $supplier): ?>
                            <option
                                value="<?= (int) $supplier['id'] ?>"
                                <?= (string) $form['supplier_id'] === (string) $supplier['id'] ? 'selected' : '' ?>
                            >
                                <?= e($supplier['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <?php if (isset($errors['supplier_id'])): ?>
                        <div class="invalid-feedback"><?= e($errors['supplier_id']) ?></div>
                    <?php endif; ?>
                </div>

                <div class="col-md-3">
                    <label for="purchased_at" class="form-label">Data da compra</label>
                    <input
                        type="date"
                        class="form-control <?= isset($errors['purchased_at']) ? 'is-invalid' : '' ?>"
                        id="purchased_at"
                        name="purchased_at"
                        value="<?= e($form['purchased_at']) ?>"
                        max="<?= e(date('Y-m-d')) ?>"
                        required
                    >
                    <?php if (isset($errors['purchased_at'])): ?>
                        <div class="invalid-feedback"><?= e($errors['purchased_at']) ?></div>
                    <?php endif; ?>
                </div>

                <div class="col-md-5">
                    <label for="document_number" class="form-label">Documento/nota</label>
                    <input
                        type="text"
                        class="form-control <?= isset($errors['document_number']) ? 'is-invalid' : '' ?>"
                        id="document_number"
                        name="document_number"
                        value="<?= e($form['document_number']) ?>"
                        maxlength="80"
                    >
                    <?php if (isset($errors['document_number'])): ?>
                        <div class="invalid-feedback"><?= e($errors['document_number']) ?></div>
                    <?php endif; ?>
                </div>

                <div class="col-12">
                    <label for="notes" class="form-label">Observações</label>
                    <textarea
                        class="form-control <?= isset($errors['notes']) ? 'is-invalid' : '' ?>"
                        id="notes"
                        name="notes"
                        rows="2"
                        maxlength="500"
                    ><?= e($form['notes']) ?></textarea>
                    <?php if (isset($errors['notes'])): ?>
                        <div class="invalid-feedback"><?= e($errors['notes']) ?></div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-sm mt-3">
        <div class="card-header bg-white d-flex flex-wrap justify-content-between align-items-center gap-2">
            <span class="fw-semibold">Itens da compra</span>
            <button type="button" class="btn btn-sm btn-outline-dark" id="add-item">Adicionar item</button>
        </div>

        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th style="min-width: 220px;">Produto</th>
                        <th style="min-width: 150px;">Quantidade</th>
                        <th style="min-width: 130px;">Custo unit.</th>
                        <th style="min-width: 140px;">Lote</th>
                        <th style="min-width: 150px;">Validade</th>
                        <th class="text-end">Total</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody id="purchase-items">
                    <?php foreach ($form['items'] as $index => $item): ?>
                        <?php
                        $rowErrors = [];
                        foreach (['product_id', 'quantity', 'unit_cost', 'lot_code', 'expires_at'] as $field) {
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
                        <th colspan="5" class="text-end">Total da compra</th>
                        <th class="text-end" id="purchase-total">R$ 0,00</th>
                        <th></th>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>

    <div class="d-flex flex-wrap gap-2 mt-3">
        <button type="submit" class="btn btn-dark">Registrar compra</button>
        <a href="/compras" class="btn btn-outline-secondary">Cancelar</a>
    </div>
</form>

<template id="purchase-item-template">
    <?php
    $item = [];
    $rowErrors = [];
    require __DIR__ . '/_item_row.php';
    ?>
</template>

<?php

use App\Core\Csrf;
?>
<div class="mb-4">
    <h1 class="h3 mb-1">Novo ajuste</h1>
    <p class="text-secondary mb-0">Corrija o inventário ou registre uma perda com motivo.</p>
</div>

<?php if ($generalError !== null): ?>
    <div class="alert alert-danger" role="alert"><?= e($generalError) ?></div>
<?php endif; ?>

<div class="alert alert-info">
    Ajustes positivos de produtos com lote criam um novo lote. Saídas por ajuste ou perda consomem os lotes por FEFO, incluindo lotes vencidos.
</div>

<form method="post" action="/ajustes" novalidate data-confirm="Registrar este ajuste?">
    <?= Csrf::field() ?>

    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-5">
                    <label for="adjustment_product_id" class="form-label">Produto</label>
                    <select
                        class="form-select <?= isset($errors['product_id']) ? 'is-invalid' : '' ?>"
                        id="adjustment_product_id"
                        name="product_id"
                        required
                    >
                        <option value="">Selecione</option>
                        <?php foreach ($products as $product): ?>
                            <option
                                value="<?= (int) $product['id'] ?>"
                                data-controls-lots="<?= (int) $product['controls_lots'] ?>"
                                data-unit="<?= e($product['unit']) ?>"
                                data-stock="<?= e($product['current_stock']) ?>"
                                <?= (string) $form['product_id'] === (string) $product['id'] ? 'selected' : '' ?>
                            >
                                <?= e($product['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <?php if (isset($errors['product_id'])): ?>
                        <div class="invalid-feedback"><?= e($errors['product_id']) ?></div>
                    <?php endif; ?>
                    <div class="form-text">
                        Saldo atual: <span id="adjustment_stock">0</span>
                    </div>
                </div>

                <div class="col-md-3">
                    <label for="adjustment_type" class="form-label">Tipo</label>
                    <select class="form-select <?= isset($errors['adjustment_type']) ? 'is-invalid' : '' ?>" id="adjustment_type" name="adjustment_type" required>
                        <option value="ajuste_positivo" <?= $form['adjustment_type'] === 'ajuste_positivo' ? 'selected' : '' ?>>Ajuste positivo</option>
                        <option value="ajuste_negativo" <?= $form['adjustment_type'] === 'ajuste_negativo' ? 'selected' : '' ?>>Ajuste negativo</option>
                        <option value="perda" <?= $form['adjustment_type'] === 'perda' ? 'selected' : '' ?>>Perda</option>
                    </select>
                    <?php if (isset($errors['adjustment_type'])): ?>
                        <div class="invalid-feedback"><?= e($errors['adjustment_type']) ?></div>
                    <?php endif; ?>
                </div>

                <div class="col-md-4">
                    <label for="quantity" class="form-label">Quantidade</label>
                    <div class="input-group">
                        <input
                            type="number"
                            step="0.001"
                            min="0.001"
                            class="form-control <?= isset($errors['quantity']) ? 'is-invalid' : '' ?>"
                            id="quantity"
                            name="quantity"
                            value="<?= e($form['quantity']) ?>"
                            required
                        >
                        <span class="input-group-text" id="adjustment_unit"></span>
                    </div>
                    <?php if (isset($errors['quantity'])): ?>
                        <div class="invalid-feedback d-block"><?= e($errors['quantity']) ?></div>
                    <?php endif; ?>
                </div>

                <div class="col-md-4">
                    <label for="unit_cost" class="form-label">Custo unitário (opcional)</label>
                    <input
                        type="number"
                        step="0.01"
                        min="0"
                        class="form-control <?= isset($errors['unit_cost']) ? 'is-invalid' : '' ?>"
                        id="unit_cost"
                        name="unit_cost"
                        value="<?= e($form['unit_cost']) ?>"
                    >
                    <?php if (isset($errors['unit_cost'])): ?>
                        <div class="invalid-feedback"><?= e($errors['unit_cost']) ?></div>
                    <?php endif; ?>
                    <div class="form-text">Somente no ajuste positivo; se informado, recalcula o custo médio.</div>
                </div>

                <div class="col-md-4">
                    <label for="lot_code" class="form-label">Lote (ajuste positivo)</label>
                    <input
                        type="text"
                        class="form-control <?= isset($errors['lot_code']) ? 'is-invalid' : '' ?>"
                        id="lot_code"
                        name="lot_code"
                        value="<?= e($form['lot_code']) ?>"
                        maxlength="80"
                    >
                    <?php if (isset($errors['lot_code'])): ?>
                        <div class="invalid-feedback"><?= e($errors['lot_code']) ?></div>
                    <?php endif; ?>
                </div>

                <div class="col-md-4">
                    <label for="expires_at" class="form-label">Validade do lote (ajuste positivo)</label>
                    <input
                        type="date"
                        class="form-control <?= isset($errors['expires_at']) ? 'is-invalid' : '' ?>"
                        id="expires_at"
                        name="expires_at"
                        value="<?= e($form['expires_at']) ?>"
                    >
                    <?php if (isset($errors['expires_at'])): ?>
                        <div class="invalid-feedback"><?= e($errors['expires_at']) ?></div>
                    <?php endif; ?>
                </div>

                <div class="col-12">
                    <label for="reason" class="form-label">Motivo</label>
                    <textarea
                        class="form-control <?= isset($errors['reason']) ? 'is-invalid' : '' ?>"
                        id="reason"
                        name="reason"
                        rows="2"
                        minlength="5"
                        maxlength="500"
                        required
                    ><?= e($form['reason']) ?></textarea>
                    <?php if (isset($errors['reason'])): ?>
                        <div class="invalid-feedback"><?= e($errors['reason']) ?></div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="small text-secondary mt-3" id="adjustment_lot_hint"></div>
        </div>
    </div>

    <div class="d-flex flex-wrap gap-2 mt-3">
        <button type="submit" class="btn btn-dark">Registrar ajuste</button>
        <a href="/ajustes" class="btn btn-outline-secondary">Cancelar</a>
    </div>
</form>

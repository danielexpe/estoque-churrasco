<?php

$expiryAlertDays = (int) config('app.expiry_alert_days', 7);
$today = new DateTimeImmutable('today');
?>
<div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-4">
    <div>
        <div class="small text-secondary mb-1">
            <a href="/compras" class="text-decoration-none">Compras</a>
            / #<?= (int) $purchase['id'] ?>
        </div>
        <h1 class="h3 mb-1">Compra #<?= (int) $purchase['id'] ?></h1>
        <p class="text-secondary mb-0">
            <?= e(date('d/m/Y', strtotime((string) $purchase['purchased_at']))) ?>
            · <?= e($purchase['supplier_name'] ?? 'Sem fornecedor') ?>
            <?php if (!empty($purchase['document_number'])): ?>
                · Documento: <?= e($purchase['document_number']) ?>
            <?php endif; ?>
        </p>
    </div>
    <a href="/compras/nova" class="btn btn-outline-dark align-self-center">Nova compra</a>
</div>

<div class="row g-3">
    <div class="col-sm-6 col-xl-3">
        <div class="card h-100 border-0 shadow-sm">
            <div class="card-body">
                <div class="text-uppercase text-secondary small">Total da compra</div>
                <div class="h3 mb-0"><?= e(money($purchase['total_cost'])) ?></div>
            </div>
        </div>
    </div>

    <div class="col-sm-6 col-xl-3">
        <div class="card h-100 border-0 shadow-sm">
            <div class="card-body">
                <div class="text-uppercase text-secondary small">Itens</div>
                <div class="h3 mb-0"><?= count($items) ?></div>
            </div>
        </div>
    </div>

    <div class="col-sm-6 col-xl-3">
        <div class="card h-100 border-0 shadow-sm">
            <div class="card-body">
                <div class="text-uppercase text-secondary small">Responsável</div>
                <div class="h5 mb-0"><?= e($purchase['user_name']) ?></div>
            </div>
        </div>
    </div>

    <div class="col-sm-6 col-xl-3">
        <div class="card h-100 border-0 shadow-sm">
            <div class="card-body">
                <div class="text-uppercase text-secondary small">Registrada em</div>
                <div class="h6 mb-0"><?= e(date('d/m/Y H:i', strtotime((string) $purchase['created_at']))) ?></div>
            </div>
        </div>
    </div>
</div>

<?php if (!empty($purchase['notes'])): ?>
    <div class="card border-0 shadow-sm mt-4">
        <div class="card-body">
            <div class="text-uppercase text-secondary small mb-1">Observações</div>
            <p class="mb-0"><?= nl2br(e($purchase['notes'])) ?></p>
        </div>
    </div>
<?php endif; ?>

<div class="card border-0 shadow-sm mt-4">
    <div class="card-header bg-white fw-semibold">Itens</div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>Produto</th>
                    <th>Unidade</th>
                    <th>Quantidade</th>
                    <th>Custo unit.</th>
                    <th>Total</th>
                    <th>Lote</th>
                    <th>Validade</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($items === []): ?>
                    <tr>
                        <td colspan="7" class="text-center text-secondary py-4">Nenhum item nesta compra.</td>
                    </tr>
                <?php endif; ?>

                <?php foreach ($items as $item): ?>
                    <?php
                    $expires = $item['expires_at'] !== null
                        ? new DateTimeImmutable((string) $item['expires_at'])
                        : null;
                    $expired = $expires !== null && $expires < $today;
                    $near = $expires !== null
                        && !$expired
                        && $expires <= $today->modify('+' . $expiryAlertDays . ' days');
                    ?>
                    <tr>
                        <td class="fw-semibold"><?= e($item['product_name']) ?></td>
                        <td><?= e($item['unit']) ?></td>
                        <td><?= e(quantity($item['quantity'], (string) $item['unit'])) ?></td>
                        <td><?= e(money($item['unit_cost'])) ?></td>
                        <td><?= e(money($item['line_total'])) ?></td>
                        <td><?= e($item['lot_code'] ?? '—') ?></td>
                        <td>
                            <?php if ($expires === null): ?>
                                <span class="text-secondary">—</span>
                            <?php elseif ($expired): ?>
                                <span class="badge text-bg-danger"><?= e($expires->format('d/m/Y')) ?> · vencido</span>
                            <?php elseif ($near): ?>
                                <span class="badge text-bg-warning"><?= e($expires->format('d/m/Y')) ?> · a vencer</span>
                            <?php else: ?>
                                <?= e($expires->format('d/m/Y')) ?>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="card border-0 shadow-sm mt-4">
    <div class="card-header bg-white fw-semibold">Movimentações geradas</div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>Data</th>
                    <th>Produto</th>
                    <th>Tipo</th>
                    <th>Quantidade</th>
                    <th>Saldo após</th>
                    <th>Lote</th>
                    <th>Usuário</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($movements === []): ?>
                    <tr>
                        <td colspan="7" class="text-center text-secondary py-4">Nenhuma movimentação gerada.</td>
                    </tr>
                <?php endif; ?>

                <?php foreach ($movements as $movement): ?>
                    <tr>
                        <td class="text-secondary small">
                            <?= e(date('d/m/Y H:i', strtotime((string) $movement['created_at']))) ?>
                        </td>
                        <td><?= e($movement['product_name']) ?></td>
                        <td><?= e(movement_label((string) $movement['movement_type'])) ?></td>
                        <td><?= e(quantity($movement['quantity'], (string) $movement['unit'])) ?></td>
                        <td><?= e(quantity($movement['balance_after'], (string) $movement['unit'])) ?></td>
                        <td class="small"><?= e($movement['lot_code'] ?? '—') ?></td>
                        <td class="small"><?= e($movement['user_name']) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

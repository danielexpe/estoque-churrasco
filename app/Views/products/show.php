<?php

use App\Core\Auth;
use App\Core\Csrf;

$isAdmin = Auth::isAdmin();
$expiryAlertDays = (int) config('app.expiry_alert_days', 7);
$today = new DateTimeImmutable('today');
$isActive = (int) $product['is_active'] === 1;
$lowStock = (float) $product['current_stock'] <= (float) $product['min_stock'];
?>
<div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-4">
    <div>
        <div class="small text-secondary mb-1">
            <a href="/produtos" class="text-decoration-none">Produtos</a>
            / <?= e($product['category_name'] ?? 'Sem categoria') ?>
        </div>
        <h1 class="h3 mb-1"><?= e($product['name']) ?></h1>
        <p class="text-secondary mb-0">
            <?php if (!empty($product['sku'])): ?>
                SKU: <?= e($product['sku']) ?>
            <?php endif; ?>
            <?php if (!empty($product['barcode'])): ?>
                · Código de barras: <?= e($product['barcode']) ?>
            <?php endif; ?>
        </p>
    </div>

    <div class="d-flex flex-wrap gap-2 align-self-center">
        <span class="badge <?= $isActive ? 'text-bg-success' : 'text-bg-secondary' ?>">
            <?= $isActive ? 'Ativo' : 'Inativo' ?>
        </span>

        <?php if ($isAdmin): ?>
            <a href="/produtos/<?= (int) $product['id'] ?>/editar" class="btn btn-sm btn-outline-dark">
                Editar
            </a>

            <?php if ($isActive): ?>
                <form
                    method="post"
                    action="/produtos/<?= (int) $product['id'] ?>/desativar"
                    data-confirm="Desativar este produto?"
                >
                    <?= Csrf::field() ?>
                    <button type="submit" class="btn btn-sm btn-outline-danger">Desativar</button>
                </form>
            <?php else: ?>
                <form method="post" action="/produtos/<?= (int) $product['id'] ?>/ativar">
                    <?= Csrf::field() ?>
                    <button type="submit" class="btn btn-sm btn-outline-success">Ativar</button>
                </form>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

<div class="row g-3">
    <div class="col-sm-6 col-xl-3">
        <div class="card h-100 border-0 shadow-sm">
            <div class="card-body">
                <div class="text-uppercase text-secondary small">Saldo atual</div>
                <div class="h3 mb-0"><?= e(quantity($product['current_stock'], (string) $product['unit'])) ?></div>
                <?php if ($lowStock): ?>
                    <span class="badge text-bg-warning mt-2">Estoque baixo</span>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="col-sm-6 col-xl-3">
        <div class="card h-100 border-0 shadow-sm">
            <div class="card-body">
                <div class="text-uppercase text-secondary small">Estoque mínimo</div>
                <div class="h3 mb-0"><?= e(quantity($product['min_stock'], (string) $product['unit'])) ?></div>
            </div>
        </div>
    </div>

    <div class="col-sm-6 col-xl-3">
        <div class="card h-100 border-0 shadow-sm">
            <div class="card-body">
                <div class="text-uppercase text-secondary small">Preço de venda</div>
                <div class="h3 mb-0"><?= e(money($product['sale_price'])) ?></div>
            </div>
        </div>
    </div>

    <div class="col-sm-6 col-xl-3">
        <div class="card h-100 border-0 shadow-sm">
            <div class="card-body">
                <div class="text-uppercase text-secondary small">
                    <?= $isAdmin ? 'Custo médio' : 'Unidade' ?>
                </div>
                <div class="h3 mb-0">
                    <?= $isAdmin ? e(money($product['cost_price'])) : e($product['unit']) ?>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="card border-0 shadow-sm mt-4">
    <div class="card-body">
        <dl class="row mb-0">
            <dt class="col-sm-3">Categoria</dt>
            <dd class="col-sm-9"><?= e($product['category_name'] ?? 'Sem categoria') ?></dd>

            <dt class="col-sm-3">Unidade</dt>
            <dd class="col-sm-9"><?= e($product['unit']) ?></dd>

            <dt class="col-sm-3">Controle de lote</dt>
            <dd class="col-sm-9"><?= (int) $product['controls_lots'] === 1 ? 'Sim' : 'Não' ?></dd>

            <dt class="col-sm-3">Cadastrado em</dt>
            <dd class="col-sm-9"><?= e(date('d/m/Y H:i', strtotime((string) $product['created_at']))) ?></dd>
        </dl>
    </div>
</div>

<div class="card border-0 shadow-sm mt-4">
    <div class="card-header bg-white fw-semibold">Lotes</div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>Lote</th>
                    <th>Validade</th>
                    <th>Saldo</th>
                    <?php if ($isAdmin): ?>
                        <th>Custo</th>
                    <?php endif; ?>
                    <th>Situação</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($lots === []): ?>
                    <tr>
                        <td colspan="<?= $isAdmin ? 5 : 4 ?>" class="text-center text-secondary py-4">
                            Nenhum lote registrado. Os lotes são criados nas compras de reposição.
                        </td>
                    </tr>
                <?php endif; ?>

                <?php foreach ($lots as $lot): ?>
                    <?php
                    $expires = $lot['expires_at'] !== null
                        ? new DateTimeImmutable((string) $lot['expires_at'])
                        : null;
                    $expired = $expires !== null && $expires < $today;
                    $near = $expires !== null
                        && !$expired
                        && $expires <= $today->modify('+' . $expiryAlertDays . ' days');
                    ?>
                    <tr>
                        <td class="fw-semibold"><?= e($lot['lot_code']) ?></td>
                        <td><?= $expires !== null ? e($expires->format('d/m/Y')) : '—' ?></td>
                        <td><?= e(quantity($lot['current_quantity'], (string) $product['unit'])) ?></td>
                        <?php if ($isAdmin): ?>
                            <td><?= e(money($lot['unit_cost'])) ?></td>
                        <?php endif; ?>
                        <td>
                            <?php if ($expired): ?>
                                <span class="badge text-bg-danger">Vencido</span>
                            <?php elseif ($near): ?>
                                <span class="badge text-bg-warning">A vencer</span>
                            <?php elseif ($expires !== null): ?>
                                <span class="badge text-bg-success">Válido</span>
                            <?php else: ?>
                                <span class="badge text-bg-secondary">Sem validade</span>
                            <?php endif; ?>

                            <?php if ((float) $lot['current_quantity'] <= 0): ?>
                                <span class="badge text-bg-light border">Sem saldo</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="card border-0 shadow-sm mt-4">
    <div class="card-header bg-white fw-semibold">Últimas movimentações</div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>Data</th>
                    <th>Tipo</th>
                    <th>Quantidade</th>
                    <th>Saldo após</th>
                    <th>Lote</th>
                    <th>Usuário</th>
                    <th>Observações</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($movements === []): ?>
                    <tr>
                        <td colspan="7" class="text-center text-secondary py-4">
                            Nenhuma movimentação registrada.
                        </td>
                    </tr>
                <?php endif; ?>

                <?php foreach ($movements as $movement): ?>
                    <tr>
                        <td class="text-secondary small">
                            <?= e(date('d/m/Y H:i', strtotime((string) $movement['created_at']))) ?>
                        </td>
                        <td><?= e(movement_label((string) $movement['movement_type'])) ?></td>
                        <td><?= e(quantity($movement['quantity'], (string) $product['unit'])) ?></td>
                        <td><?= e(quantity($movement['balance_after'], (string) $product['unit'])) ?></td>
                        <td class="small"><?= e($movement['lot_code'] ?? '—') ?></td>
                        <td class="small"><?= e($movement['user_name']) ?></td>
                        <td class="small text-secondary"><?= e($movement['notes'] ?? '—') ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

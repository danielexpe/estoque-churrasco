<?php

use App\Core\Auth;
use App\Core\Csrf;

$isAdmin = Auth::isAdmin();
$isCanceled = $sale['status'] === 'cancelada';
?>
<div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-4">
    <div>
        <div class="small text-secondary mb-1">
            <a href="/vendas" class="text-decoration-none">Vendas</a>
            / #<?= (int) $sale['id'] ?>
        </div>
        <h1 class="h3 mb-1">Venda #<?= (int) $sale['id'] ?></h1>
        <p class="text-secondary mb-0">
            <?= e(date('d/m/Y H:i', strtotime((string) $sale['sold_at']))) ?>
            · Responsável: <?= e($sale['user_name']) ?>
        </p>
    </div>
    <span class="badge <?= $isCanceled ? 'text-bg-secondary' : 'text-bg-success' ?> align-self-center">
        <?= $isCanceled ? 'Cancelada' : 'Concluída' ?>
    </span>
</div>

<div class="row g-3">
    <div class="col-sm-6 col-xl-3">
        <div class="card h-100 border-0 shadow-sm">
            <div class="card-body">
                <div class="text-uppercase text-secondary small">Total da venda</div>
                <div class="h3 mb-0"><?= e(money($sale['total_value'])) ?></div>
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
                <div class="text-uppercase text-secondary small">Status</div>
                <div class="h5 mb-0"><?= $isCanceled ? 'Cancelada' : 'Concluída' ?></div>
            </div>
        </div>
    </div>

    <div class="col-sm-6 col-xl-3">
        <div class="card h-100 border-0 shadow-sm">
            <div class="card-body">
                <div class="text-uppercase text-secondary small">Registrada em</div>
                <div class="h6 mb-0"><?= e(date('d/m/Y H:i', strtotime((string) $sale['created_at']))) ?></div>
            </div>
        </div>
    </div>
</div>

<div class="card border-0 shadow-sm mt-4">
    <div class="card-header bg-white fw-semibold">Itens</div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>Produto</th>
                    <th>Unidade</th>
                    <th>Quantidade</th>
                    <th>Preço unit.</th>
                    <th>Total</th>
                    <?php if ($isAdmin): ?>
                        <th>Custo unit.</th>
                    <?php endif; ?>
                </tr>
            </thead>
            <tbody>
                <?php if ($items === []): ?>
                    <tr>
                        <td colspan="<?= $isAdmin ? 6 : 5 ?>" class="text-center text-secondary py-4">
                            Nenhum item nesta venda.
                        </td>
                    </tr>
                <?php endif; ?>

                <?php foreach ($items as $item): ?>
                    <tr>
                        <td class="fw-semibold"><?= e($item['product_name']) ?></td>
                        <td><?= e($item['unit']) ?></td>
                        <td><?= e(quantity($item['quantity'], (string) $item['unit'])) ?></td>
                        <td><?= e(money($item['unit_price'])) ?></td>
                        <td><?= e(money($item['line_total'])) ?></td>
                        <?php if ($isAdmin): ?>
                            <td><?= e(money($item['unit_cost'])) ?></td>
                        <?php endif; ?>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php if ($itemLots !== []): ?>
    <div class="card border-0 shadow-sm mt-4">
        <div class="card-header bg-white fw-semibold">Lotes consumidos</div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Produto</th>
                        <th>Lote</th>
                        <th>Validade</th>
                        <th>Quantidade</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($itemLots as $lot): ?>
                        <tr>
                            <td><?= e($lot['product_name']) ?></td>
                            <td class="fw-semibold"><?= e($lot['lot_code']) ?></td>
                            <td><?= e($lot['expires_at'] !== null ? date('d/m/Y', strtotime((string) $lot['expires_at'])) : '—') ?></td>
                            <td><?= e(quantity($lot['quantity'], (string) $lot['unit'])) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
<?php endif; ?>

<?php if ($isCanceled): ?>
    <div class="card border-0 shadow-sm mt-4">
        <div class="card-body">
            <h2 class="h5">Cancelamento</h2>
            <p class="mb-1">
                Cancelada por <strong><?= e($sale['canceled_by_name'] ?? '—') ?></strong>
                em <?= e($sale['canceled_at'] !== null ? date('d/m/Y H:i', strtotime((string) $sale['canceled_at'])) : '—') ?>.
            </p>
            <p class="mb-0 text-secondary">Motivo: <?= e($sale['cancel_reason'] ?? '—') ?></p>
        </div>
    </div>
<?php elseif ($isAdmin): ?>
    <div class="card border-0 shadow-sm mt-4">
        <div class="card-body">
            <h2 class="h5">Cancelar venda</h2>
            <p class="text-secondary">
                O cancelamento devolve todos os itens ao estoque, inclusive aos lotes de origem.
            </p>

            <form method="post" action="/vendas/<?= (int) $sale['id'] ?>/cancelar" data-confirm="Cancelar esta venda e devolver o estoque?">
                <?= Csrf::field() ?>
                <div class="mb-3">
                    <label for="cancel_reason" class="form-label">Motivo do cancelamento</label>
                    <textarea
                        class="form-control"
                        id="cancel_reason"
                        name="cancel_reason"
                        rows="2"
                        maxlength="500"
                        required
                    ></textarea>
                </div>
                <button type="submit" class="btn btn-outline-danger">Cancelar venda</button>
            </form>
        </div>
    </div>
<?php endif; ?>

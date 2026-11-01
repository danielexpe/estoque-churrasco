<?php

use App\Core\Auth;
use App\Core\Csrf;

$isAdmin = Auth::isAdmin();
$columns = $isAdmin ? 9 : 7;
?>
<div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-4">
    <div>
        <h1 class="h3 mb-1">Produtos</h1>
        <p class="text-secondary mb-0">Catálogo, preços e saldo atual do estoque.</p>
    </div>
    <?php if ($isAdmin): ?>
        <a href="/produtos/novo" class="btn btn-dark">Novo produto</a>
    <?php endif; ?>
</div>

<form method="get" action="/produtos" class="card border-0 shadow-sm mb-3">
    <div class="card-body">
        <div class="row g-2 align-items-end">
            <div class="col-md-4">
                <label for="q" class="form-label">Buscar</label>
                <input
                    type="text"
                    class="form-control"
                    id="q"
                    name="q"
                    value="<?= e($filters['q']) ?>"
                    placeholder="Nome, SKU ou código de barras"
                >
            </div>

            <div class="col-md-3">
                <label for="categoria" class="form-label">Categoria</label>
                <select class="form-select" id="categoria" name="categoria">
                    <option value="">Todas</option>
                    <?php foreach ($categories as $category): ?>
                        <option
                            value="<?= (int) $category['id'] ?>"
                            <?= (int) ($filters['category_id'] ?? 0) === (int) $category['id'] ? 'selected' : '' ?>
                        >
                            <?= e($category['name']) ?><?= (int) $category['is_active'] === 1 ? '' : ' (inativa)' ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-md-2">
                <label for="status" class="form-label">Status</label>
                <select class="form-select" id="status" name="status">
                    <?php foreach (['active' => 'Ativos', 'inactive' => 'Inativos', 'all' => 'Todos'] as $value => $label): ?>
                        <option value="<?= $value ?>" <?= $filters['status'] === $value ? 'selected' : '' ?>>
                            <?= $label ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-md-3">
                <div class="form-check mt-md-4">
                    <input
                        class="form-check-input"
                        type="checkbox"
                        id="estoque_baixo"
                        name="estoque_baixo"
                        value="1"
                        <?= $filters['low_stock'] ? 'checked' : '' ?>
                    >
                    <label class="form-check-label" for="estoque_baixo">Somente estoque baixo</label>
                </div>
            </div>

            <div class="col-12 d-flex flex-wrap gap-2">
                <button type="submit" class="btn btn-dark">Filtrar</button>
                <a href="/produtos" class="btn btn-outline-secondary">Limpar</a>
                <span class="ms-auto text-secondary small align-self-center">
                    <?= (int) $total ?> produto(s) encontrado(s)
                </span>
            </div>
        </div>
    </div>
</form>

<div class="card border-0 shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>Produto</th>
                    <th>Categoria</th>
                    <th>Unidade</th>
                    <th>Saldo</th>
                    <th>Mínimo</th>
                    <th>Preço</th>
                    <?php if ($isAdmin): ?>
                        <th>Custo</th>
                    <?php endif; ?>
                    <th>Status</th>
                    <?php if ($isAdmin): ?>
                        <th class="text-end">Ações</th>
                    <?php endif; ?>
                </tr>
            </thead>
            <tbody>
                <?php if ($products === []): ?>
                    <tr>
                        <td colspan="<?= $columns ?>" class="text-center text-secondary py-4">
                            Nenhum produto encontrado para os filtros informados.
                        </td>
                    </tr>
                <?php endif; ?>

                <?php foreach ($products as $product): ?>
                    <?php $lowStock = (float) $product['current_stock'] <= (float) $product['min_stock']; ?>
                    <tr>
                        <td>
                            <a class="fw-semibold text-decoration-none" href="/produtos/<?= (int) $product['id'] ?>">
                                <?= e($product['name']) ?>
                            </a>
                            <?php if ($lowStock): ?>
                                <span class="badge text-bg-warning ms-1">Estoque baixo</span>
                            <?php endif; ?>
                            <?php if (!empty($product['sku'])): ?>
                                <div class="text-secondary small">SKU: <?= e($product['sku']) ?></div>
                            <?php endif; ?>
                        </td>
                        <td class="text-secondary small"><?= e($product['category_name'] ?? 'Sem categoria') ?></td>
                        <td><?= e($product['unit']) ?></td>
                        <td><?= e(quantity($product['current_stock'], (string) $product['unit'])) ?></td>
                        <td><?= e(quantity($product['min_stock'], (string) $product['unit'])) ?></td>
                        <td><?= e(money($product['sale_price'])) ?></td>
                        <?php if ($isAdmin): ?>
                            <td><?= e(money($product['cost_price'])) ?></td>
                        <?php endif; ?>
                        <td>
                            <?php if ((int) $product['is_active'] === 1): ?>
                                <span class="badge text-bg-success">Ativo</span>
                            <?php else: ?>
                                <span class="badge text-bg-secondary">Inativo</span>
                            <?php endif; ?>
                        </td>
                        <?php if ($isAdmin): ?>
                            <td class="text-end">
                                <a class="btn btn-sm btn-outline-dark" href="/produtos/<?= (int) $product['id'] ?>/editar">
                                    Editar
                                </a>

                                <?php if ((int) $product['is_active'] === 1): ?>
                                    <form
                                        method="post"
                                        action="/produtos/<?= (int) $product['id'] ?>/desativar"
                                        class="d-inline"
                                        data-confirm="Desativar este produto?"
                                    >
                                        <?= Csrf::field() ?>
                                        <button type="submit" class="btn btn-sm btn-outline-danger">Desativar</button>
                                    </form>
                                <?php else: ?>
                                    <form method="post" action="/produtos/<?= (int) $product['id'] ?>/ativar" class="d-inline">
                                        <?= Csrf::field() ?>
                                        <button type="submit" class="btn btn-sm btn-outline-success">Ativar</button>
                                    </form>
                                <?php endif; ?>
                            </td>
                        <?php endif; ?>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require BASE_PATH . '/app/Views/partials/pagination.php'; ?>

<?php

use App\Core\Auth;
?>
<div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-4">
    <div>
        <h1 class="h3 mb-1">Movimentações</h1>
        <p class="text-secondary mb-0">Histórico completo de entradas, saídas, estornos e ajustes.</p>
    </div>
    <?php if (Auth::isAdmin()): ?>
        <a href="/relatorios/conciliacao" class="btn btn-outline-dark">Conciliação de estoque</a>
    <?php endif; ?>
</div>

<form method="get" action="/movimentacoes" class="card border-0 shadow-sm mb-3">
    <div class="card-body">
        <div class="row g-2 align-items-end">
            <div class="col-md-4">
                <label for="produto" class="form-label">Produto</label>
                <select class="form-select" id="produto" name="produto">
                    <option value="">Todos</option>
                    <?php foreach ($products as $product): ?>
                        <option value="<?= (int) $product['id'] ?>" <?= (string) $filters['product_id'] === (string) $product['id'] ? 'selected' : '' ?>>
                            <?= e($product['name']) ?><?= (int) $product['is_active'] === 1 ? '' : ' (inativo)' ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-md-3">
                <label for="tipo" class="form-label">Tipo</label>
                <select class="form-select" id="tipo" name="tipo">
                    <option value="">Todos</option>
                    <?php foreach (['entrada_compra', 'saida_venda', 'estorno_venda', 'ajuste_positivo', 'ajuste_negativo', 'perda'] as $type): ?>
                        <option value="<?= $type ?>" <?= $filters['movement_type'] === $type ? 'selected' : '' ?>>
                            <?= e(movement_label($type)) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-md-3">
                <label for="usuario" class="form-label">Usuário</label>
                <select class="form-select" id="usuario" name="usuario">
                    <option value="">Todos</option>
                    <?php foreach ($users as $user): ?>
                        <option value="<?= (int) $user['id'] ?>" <?= (string) $filters['user_id'] === (string) $user['id'] ? 'selected' : '' ?>>
                            <?= e($user['name']) ?><?= (int) $user['is_active'] === 1 ? '' : ' (inativo)' ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-md-2">
                <label for="data_inicio" class="form-label">De</label>
                <input type="date" class="form-control" id="data_inicio" name="data_inicio" value="<?= e($filters['date_from']) ?>">
            </div>

            <div class="col-md-2">
                <label for="data_fim" class="form-label">Até</label>
                <input type="date" class="form-control" id="data_fim" name="data_fim" value="<?= e($filters['date_to']) ?>">
            </div>

            <div class="col-md-2 d-flex gap-2">
                <button type="submit" class="btn btn-dark">Filtrar</button>
                <a href="/movimentacoes" class="btn btn-outline-secondary">Limpar</a>
            </div>

            <div class="col-12">
                <span class="text-secondary small"><?= (int) $total ?> movimentação(ões) encontrada(s)</span>
            </div>
        </div>
    </div>
</form>

<div class="card border-0 shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>Data</th>
                    <th>Produto</th>
                    <th>Lote</th>
                    <th>Tipo</th>
                    <th>Quantidade</th>
                    <th>Saldo após</th>
                    <th>Origem</th>
                    <th>Usuário</th>
                    <th>Observações</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($movements === []): ?>
                    <tr>
                        <td colspan="9" class="text-center text-secondary py-4">
                            Nenhuma movimentação encontrada para os filtros informados.
                        </td>
                    </tr>
                <?php endif; ?>

                <?php foreach ($movements as $movement): ?>
                    <tr>
                        <td class="text-secondary small">
                            <?= e(date('d/m/Y H:i', strtotime((string) $movement['created_at']))) ?>
                        </td>
                        <td class="fw-semibold"><?= e($movement['product_name']) ?></td>
                        <td class="small"><?= e($movement['lot_code'] ?? '—') ?></td>
                        <td><?= e(movement_label((string) $movement['movement_type'])) ?></td>
                        <td><?= e(quantity($movement['quantity'], (string) $movement['unit'])) ?></td>
                        <td><?= e(quantity($movement['balance_after'], (string) $movement['unit'])) ?></td>
                        <td class="small">
                            <?= e(reference_label($movement['reference_type'], $movement['reference_id'] !== null ? (int) $movement['reference_id'] : null)) ?>
                        </td>
                        <td class="small"><?= e($movement['user_name']) ?></td>
                        <td class="small text-secondary"><?= e($movement['notes'] ?? '—') ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require BASE_PATH . '/app/Views/partials/pagination.php'; ?>

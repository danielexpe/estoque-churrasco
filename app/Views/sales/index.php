<div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-4">
    <div>
        <h1 class="h3 mb-1">Vendas</h1>
        <p class="text-secondary mb-0">Saídas de estoque e histórico de vendas.</p>
    </div>
    <a href="/vendas/nova" class="btn btn-dark">Nova venda</a>
</div>

<form method="get" action="/vendas" class="card border-0 shadow-sm mb-3">
    <div class="card-body">
        <div class="row g-2 align-items-end">
            <div class="col-md-3">
                <label for="data_inicio" class="form-label">Data inicial</label>
                <input type="date" class="form-control" id="data_inicio" name="data_inicio" value="<?= e($filters['date_from']) ?>">
            </div>

            <div class="col-md-3">
                <label for="data_fim" class="form-label">Data final</label>
                <input type="date" class="form-control" id="data_fim" name="data_fim" value="<?= e($filters['date_to']) ?>">
            </div>

            <div class="col-md-3">
                <label for="status" class="form-label">Status</label>
                <select class="form-select" id="status" name="status">
                    <?php foreach (['all' => 'Todas', 'concluida' => 'Concluídas', 'cancelada' => 'Canceladas'] as $value => $label): ?>
                        <option value="<?= $value ?>" <?= $filters['status'] === $value ? 'selected' : '' ?>>
                            <?= $label ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-md-3 d-flex gap-2">
                <button type="submit" class="btn btn-dark">Filtrar</button>
                <a href="/vendas" class="btn btn-outline-secondary">Limpar</a>
            </div>

            <div class="col-12">
                <span class="text-secondary small"><?= (int) $total ?> venda(s) encontrada(s)</span>
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
                    <th>Responsável</th>
                    <th>Itens</th>
                    <th>Total</th>
                    <th>Status</th>
                    <th class="text-end">Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($sales === []): ?>
                    <tr>
                        <td colspan="6" class="text-center text-secondary py-4">
                            Nenhuma venda encontrada para os filtros informados.
                        </td>
                    </tr>
                <?php endif; ?>

                <?php foreach ($sales as $sale): ?>
                    <tr>
                        <td><?= e(date('d/m/Y H:i', strtotime((string) $sale['sold_at']))) ?></td>
                        <td class="text-secondary small"><?= e($sale['user_name']) ?></td>
                        <td><?= (int) $sale['items_count'] ?></td>
                        <td class="fw-semibold"><?= e(money($sale['total_value'])) ?></td>
                        <td>
                            <?php if ($sale['status'] === 'concluida'): ?>
                                <span class="badge text-bg-success">Concluída</span>
                            <?php else: ?>
                                <span class="badge text-bg-secondary">Cancelada</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-end">
                            <a class="btn btn-sm btn-outline-dark" href="/vendas/<?= (int) $sale['id'] ?>">
                                Ver detalhe
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require BASE_PATH . '/app/Views/partials/pagination.php'; ?>

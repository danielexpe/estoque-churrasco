<div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-4">
    <div>
        <h1 class="h3 mb-1">Compras</h1>
        <p class="text-secondary mb-0">Entradas de reposição e custos de aquisição.</p>
    </div>
    <a href="/compras/nova" class="btn btn-dark">Nova compra</a>
</div>

<form method="get" action="/compras" class="card border-0 shadow-sm mb-3">
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

            <div class="col-md-4">
                <label for="fornecedor" class="form-label">Fornecedor</label>
                <select class="form-select" id="fornecedor" name="fornecedor">
                    <option value="">Todos</option>
                    <?php foreach ($suppliers as $supplier): ?>
                        <option
                            value="<?= (int) $supplier['id'] ?>"
                            <?= (string) $filters['supplier_id'] === (string) $supplier['id'] ? 'selected' : '' ?>
                        >
                            <?= e($supplier['name']) ?><?= (int) $supplier['is_active'] === 1 ? '' : ' (inativo)' ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-md-2 d-flex gap-2">
                <button type="submit" class="btn btn-dark">Filtrar</button>
                <a href="/compras" class="btn btn-outline-secondary">Limpar</a>
            </div>

            <div class="col-12">
                <span class="text-secondary small"><?= (int) $total ?> compra(s) encontrada(s)</span>
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
                    <th>Fornecedor</th>
                    <th>Documento</th>
                    <th>Itens</th>
                    <th>Total</th>
                    <th>Responsável</th>
                    <th class="text-end">Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($purchases === []): ?>
                    <tr>
                        <td colspan="7" class="text-center text-secondary py-4">
                            Nenhuma compra encontrada para os filtros informados.
                        </td>
                    </tr>
                <?php endif; ?>

                <?php foreach ($purchases as $purchase): ?>
                    <tr>
                        <td><?= e(date('d/m/Y', strtotime((string) $purchase['purchased_at']))) ?></td>
                        <td><?= e($purchase['supplier_name'] ?? 'Sem fornecedor') ?></td>
                        <td class="text-secondary small"><?= e($purchase['document_number'] ?? '—') ?></td>
                        <td><?= (int) $purchase['items_count'] ?></td>
                        <td class="fw-semibold"><?= e(money($purchase['total_cost'])) ?></td>
                        <td class="text-secondary small"><?= e($purchase['user_name']) ?></td>
                        <td class="text-end">
                            <a class="btn btn-sm btn-outline-dark" href="/compras/<?= (int) $purchase['id'] ?>">
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

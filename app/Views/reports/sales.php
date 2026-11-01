<?php

$csvQuery = http_build_query($filters);
?>
<div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-4">
    <div>
        <h1 class="h3 mb-1">Vendas por período</h1>
        <p class="text-secondary mb-0">Vendas concluídas e canceladas no período selecionado.</p>
    </div>
    <a href="/relatorios/vendas.csv?<?= e($csvQuery) ?>" class="btn btn-outline-dark">Exportar CSV</a>
</div>

<?php require __DIR__ . '/_nav.php'; ?>

<form method="get" action="/relatorios/vendas" class="card border-0 shadow-sm mb-3">
    <div class="card-body">
        <div class="row g-2 align-items-end">
            <div class="col-md-4">
                <label for="data_inicio" class="form-label">Data inicial</label>
                <input type="date" class="form-control" id="data_inicio" name="data_inicio" value="<?= e($filters['date_from']) ?>">
            </div>

            <div class="col-md-4">
                <label for="data_fim" class="form-label">Data final</label>
                <input type="date" class="form-control" id="data_fim" name="data_fim" value="<?= e($filters['date_to']) ?>">
            </div>

            <div class="col-md-4 d-flex gap-2">
                <button type="submit" class="btn btn-dark">Filtrar</button>
                <a href="/relatorios/vendas" class="btn btn-outline-secondary">Mês atual</a>
            </div>
        </div>
    </div>
</form>

<div class="row g-3 mb-4">
    <div class="col-sm-6 col-xl-3">
        <div class="card h-100 border-0 shadow-sm">
            <div class="card-body">
                <div class="text-uppercase text-secondary small">Vendas concluídas</div>
                <div class="display-6"><?= (int) $data['concluded_count'] ?></div>
            </div>
        </div>
    </div>

    <div class="col-sm-6 col-xl-3">
        <div class="card h-100 border-0 shadow-sm">
            <div class="card-body">
                <div class="text-uppercase text-secondary small">Total vendido</div>
                <div class="h3 mb-0"><?= e(money($data['concluded_total'])) ?></div>
            </div>
        </div>
    </div>

    <div class="col-sm-6 col-xl-3">
        <div class="card h-100 border-0 shadow-sm">
            <div class="card-body">
                <div class="text-uppercase text-secondary small">Linhas de itens</div>
                <div class="display-6"><?= (int) $data['concluded_item_lines'] ?></div>
            </div>
        </div>
    </div>

    <div class="col-sm-6 col-xl-3">
        <div class="card h-100 border-0 shadow-sm">
            <div class="card-body">
                <div class="text-uppercase text-secondary small">Canceladas</div>
                <div class="display-6"><?= (int) $data['canceled_count'] ?></div>
            </div>
        </div>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-header bg-white fw-semibold">Vendas concluídas</div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>Data</th>
                    <th>Responsável</th>
                    <th>Itens</th>
                    <th>Total</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($data['concluded'] === []): ?>
                    <tr>
                        <td colspan="5" class="text-center text-secondary py-4">
                            Nenhuma venda concluída no período.
                        </td>
                    </tr>
                <?php endif; ?>

                <?php foreach ($data['concluded'] as $sale): ?>
                    <tr>
                        <td><?= e(date('d/m/Y H:i', strtotime((string) $sale['sold_at']))) ?></td>
                        <td class="text-secondary small"><?= e($sale['user_name']) ?></td>
                        <td><?= (int) $sale['items_count'] ?></td>
                        <td class="fw-semibold"><?= e(money($sale['total_value'])) ?></td>
                        <td><span class="badge text-bg-success">Concluída</span></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="card border-0 shadow-sm mt-4">
    <div class="card-header bg-white fw-semibold">Vendas canceladas</div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>Data</th>
                    <th>Responsável</th>
                    <th>Itens</th>
                    <th>Total</th>
                    <th>Cancelada por</th>
                    <th>Motivo</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($data['canceled'] === []): ?>
                    <tr>
                        <td colspan="6" class="text-center text-secondary py-4">
                            Nenhuma venda cancelada no período.
                        </td>
                    </tr>
                <?php endif; ?>

                <?php foreach ($data['canceled'] as $sale): ?>
                    <tr>
                        <td><?= e(date('d/m/Y H:i', strtotime((string) $sale['sold_at']))) ?></td>
                        <td class="text-secondary small"><?= e($sale['user_name']) ?></td>
                        <td><?= (int) $sale['items_count'] ?></td>
                        <td class="fw-semibold"><?= e(money($sale['total_value'])) ?></td>
                        <td class="text-secondary small"><?= e($sale['canceled_by_name'] ?? '—') ?></td>
                        <td class="text-secondary small"><?= e($sale['cancel_reason'] ?? '—') ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

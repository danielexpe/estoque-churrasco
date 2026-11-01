<div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-4">
    <div>
        <h1 class="h3 mb-1">Estoque atual</h1>
        <p class="text-secondary mb-0">Saldo dos produtos ativos do catálogo.</p>
    </div>
    <a href="/relatorios/estoque.csv" class="btn btn-outline-dark">Exportar CSV</a>
</div>

<?php require __DIR__ . '/_nav.php'; ?>

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
                    <th>Controle de lote</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($rows === []): ?>
                    <tr>
                        <td colspan="7" class="text-center text-secondary py-4">Nenhum produto ativo.</td>
                    </tr>
                <?php endif; ?>

                <?php foreach ($rows as $row): ?>
                    <tr>
                        <td class="fw-semibold"><?= e($row['name']) ?></td>
                        <td class="text-secondary small"><?= e($row['category_name'] ?? 'Sem categoria') ?></td>
                        <td><?= e($row['unit']) ?></td>
                        <td><?= e(quantity($row['current_stock'], (string) $row['unit'])) ?></td>
                        <td><?= e(quantity($row['min_stock'], (string) $row['unit'])) ?></td>
                        <td><?= e(money($row['sale_price'])) ?></td>
                        <td><?= (int) $row['controls_lots'] === 1 ? 'Sim' : 'Não' ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

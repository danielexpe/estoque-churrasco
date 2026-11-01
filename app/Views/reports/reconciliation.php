<div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-4">
    <div>
        <h1 class="h3 mb-1">Conciliação de estoque</h1>
        <p class="text-secondary mb-0">
            Confere o saldo do produto com a soma das movimentações e, quando aplicável, com a soma dos lotes.
        </p>
    </div>
    <a href="/movimentacoes" class="btn btn-outline-dark">Voltar às movimentações</a>
</div>

<?php if ($rows === []): ?>
    <div class="alert alert-success" role="alert">
        Nenhuma divergência encontrada. O saldo dos produtos confere com o livro de movimentações.
    </div>
<?php else: ?>
    <div class="alert alert-warning" role="alert">
        Foram encontradas <?= count($rows) ?> divergência(s). Verifique os produtos abaixo.
    </div>

    <div class="card border-0 shadow-sm">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Produto</th>
                        <th>Unidade</th>
                        <th>Controle de lote</th>
                        <th>Saldo atual</th>
                        <th>Soma movimentos</th>
                        <th>Diferença movimentos</th>
                        <th>Soma lotes</th>
                        <th>Diferença lotes</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($rows as $row): ?>
                        <?php
                        $ledgerDiff = bcsub(
                            (string) $row['current_stock'],
                            (string) $row['ledger_stock'],
                            3
                        );
                        $lotsDiff = (int) $row['controls_lots'] === 1
                            ? bcsub((string) $row['current_stock'], (string) $row['lots_stock'], 3)
                            : null;
                        ?>
                        <tr>
                            <td class="fw-semibold"><?= e($row['name']) ?></td>
                            <td><?= e($row['unit']) ?></td>
                            <td><?= (int) $row['controls_lots'] === 1 ? 'Sim' : 'Não' ?></td>
                            <td><?= e(quantity($row['current_stock'], (string) $row['unit'])) ?></td>
                            <td><?= e(quantity($row['ledger_stock'], (string) $row['unit'])) ?></td>
                            <td class="text-danger fw-semibold">
                                <?= e(quantity($ledgerDiff, (string) $row['unit'])) ?>
                            </td>
                            <td>
                                <?= (int) $row['controls_lots'] === 1
                                    ? e(quantity($row['lots_stock'], (string) $row['unit']))
                                    : '—' ?>
                            </td>
                            <td class="text-danger fw-semibold">
                                <?= $lotsDiff !== null
                                    ? e(quantity($lotsDiff, (string) $row['unit']))
                                    : '—' ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
<?php endif; ?>

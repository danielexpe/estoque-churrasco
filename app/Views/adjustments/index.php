<div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-4">
    <div>
        <h1 class="h3 mb-1">Ajustes e perdas</h1>
        <p class="text-secondary mb-0">Correções de inventário, perdas e entradas manuais.</p>
    </div>
    <a href="/ajustes/novo" class="btn btn-dark">Novo ajuste</a>
</div>

<form method="get" action="/ajustes" class="card border-0 shadow-sm mb-3">
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
                <label for="tipo" class="form-label">Tipo</label>
                <select class="form-select" id="tipo" name="tipo">
                    <option value="">Todos</option>
                    <?php foreach (['ajuste_positivo' => 'Ajuste positivo', 'ajuste_negativo' => 'Ajuste negativo', 'perda' => 'Perda'] as $value => $label): ?>
                        <option value="<?= $value ?>" <?= $filters['adjustment_type'] === $value ? 'selected' : '' ?>>
                            <?= $label ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-md-3 d-flex gap-2">
                <button type="submit" class="btn btn-dark">Filtrar</button>
                <a href="/ajustes" class="btn btn-outline-secondary">Limpar</a>
            </div>

            <div class="col-12">
                <span class="text-secondary small"><?= (int) $total ?> ajuste(s) encontrado(s)</span>
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
                    <th>Tipo</th>
                    <th>Quantidade</th>
                    <th>Custo unit.</th>
                    <th>Motivo</th>
                    <th>Responsável</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($adjustments === []): ?>
                    <tr>
                        <td colspan="7" class="text-center text-secondary py-4">
                            Nenhum ajuste encontrado para os filtros informados.
                        </td>
                    </tr>
                <?php endif; ?>

                <?php foreach ($adjustments as $adjustment): ?>
                    <tr>
                        <td class="text-secondary small">
                            <?= e(date('d/m/Y H:i', strtotime((string) $adjustment['created_at']))) ?>
                        </td>
                        <td class="fw-semibold"><?= e($adjustment['product_name']) ?></td>
                        <td>
                            <span class="badge <?= $adjustment['adjustment_type'] === 'perda' ? 'text-bg-danger' : ($adjustment['adjustment_type'] === 'ajuste_negativo' ? 'text-bg-warning' : 'text-bg-success') ?>">
                                <?= e(adjustment_label((string) $adjustment['adjustment_type'])) ?>
                            </span>
                        </td>
                        <td><?= e(quantity($adjustment['quantity'], (string) $adjustment['unit'])) ?></td>
                        <td><?= $adjustment['unit_cost'] !== null ? e(money($adjustment['unit_cost'])) : '—' ?></td>
                        <td class="text-secondary small"><?= e($adjustment['reason']) ?></td>
                        <td class="text-secondary small"><?= e($adjustment['user_name']) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require BASE_PATH . '/app/Views/partials/pagination.php'; ?>

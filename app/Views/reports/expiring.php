<?php

$today = new DateTimeImmutable('today');
?>
<div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-4">
    <div>
        <h1 class="h3 mb-1">Vencimentos</h1>
        <p class="text-secondary mb-0">
            Lotes vencidos ou com validade até <?= e(date('d/m/Y', strtotime($threshold))) ?>.
        </p>
    </div>
    <a href="/relatorios/vencimentos.csv" class="btn btn-outline-dark">Exportar CSV</a>
</div>

<?php require __DIR__ . '/_nav.php'; ?>

<div class="card border-0 shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>Produto</th>
                    <th>Lote</th>
                    <th>Validade</th>
                    <th>Saldo</th>
                    <th>Situação</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($rows === []): ?>
                    <tr>
                        <td colspan="5" class="text-center text-secondary py-4">
                            Nenhum lote vencido ou próximo do vencimento.
                        </td>
                    </tr>
                <?php endif; ?>

                <?php foreach ($rows as $row): ?>
                    <?php
                    $expires = new DateTimeImmutable((string) $row['expires_at']);
                    $isExpired = $expires < $today;
                    ?>
                    <tr>
                        <td>
                            <a class="fw-semibold text-decoration-none" href="/produtos/<?= (int) $row['product_id'] ?>">
                                <?= e($row['product_name']) ?>
                            </a>
                        </td>
                        <td class="fw-semibold"><?= e($row['lot_code']) ?></td>
                        <td><?= e($expires->format('d/m/Y')) ?></td>
                        <td><?= e(quantity($row['current_quantity'], (string) $row['unit'])) ?></td>
                        <td>
                            <span class="badge <?= $isExpired ? 'text-bg-danger' : 'text-bg-warning' ?>">
                                <?= $isExpired ? 'Vencido' : 'A vencer' ?>
                            </span>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

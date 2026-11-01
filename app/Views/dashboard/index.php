<?php

use App\Core\Auth;

$user = Auth::user();
?>
<div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-4">
    <div>
        <h1 class="h3 mb-1">Painel</h1>
        <p class="text-secondary mb-0">
            Olá, <?= e($user['name'] ?? 'usuário') ?>. Relatórios, testes e documentação estão prontos.
        </p>
    </div>
    <span class="badge text-bg-success align-self-center">Fase 6 concluída</span>
</div>

<div class="row g-3">
    <div class="col-sm-6 col-xl-3">
        <div class="card h-100 border-0 shadow-sm">
            <div class="card-body">
                <div class="text-uppercase text-secondary small">Produtos ativos</div>
                <div class="display-6"><?= (int) ($activeProducts ?? 0) ?></div>
                <p class="small text-secondary mb-0">Itens disponíveis no catálogo.</p>
            </div>
        </div>
    </div>

    <div class="col-sm-6 col-xl-3">
        <div class="card h-100 border-0 shadow-sm">
            <div class="card-body">
                <div class="text-uppercase text-secondary small">Usuários ativos</div>
                <div class="display-6"><?= (int) ($activeUsers ?? 0) ?></div>
                <p class="small text-secondary mb-0">Acessos internos habilitados.</p>
            </div>
        </div>
    </div>

    <div class="col-sm-6 col-xl-3">
        <div class="card h-100 border-0 shadow-sm">
            <div class="card-body">
                <div class="text-uppercase text-secondary small">Estoque baixo</div>
                <div class="display-6"><?= (int) ($lowStockProducts ?? 0) ?></div>
                <p class="small text-secondary mb-0">
                    <a href="/produtos?estoque_baixo=1" class="text-decoration-none">Ver produtos em alerta</a>
                </p>
            </div>
        </div>
    </div>

    <?php if (Auth::isAdmin()): ?>
        <div class="col-sm-6 col-xl-3">
            <div class="card h-100 border-0 shadow-sm">
                <div class="card-body">
                    <div class="text-uppercase text-secondary small">Vendas hoje</div>
                    <div class="display-6"><?= (int) ($salesToday ?? 0) ?></div>
                    <p class="small text-secondary mb-0">
                        Total: <?= e(money($salesTodayTotal ?? '0.00')) ?>
                    </p>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php
$todayDate = new DateTimeImmutable('today');
?>
<div class="row g-3 mt-1">
    <div class="col-lg-6">
        <div class="card h-100 border-0 shadow-sm">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <span class="fw-semibold">Produtos com estoque baixo</span>
                <a href="/produtos?estoque_baixo=1" class="small text-decoration-none">Ver todos</a>
            </div>
            <div class="table-responsive">
                <table class="table table-sm table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Produto</th>
                            <th>Saldo</th>
                            <th>Mínimo</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (($lowStockList ?? []) === []): ?>
                            <tr>
                                <td colspan="3" class="text-center text-secondary py-3">Nenhum produto em alerta.</td>
                            </tr>
                        <?php endif; ?>

                        <?php foreach (($lowStockList ?? []) as $item): ?>
                            <tr>
                                <td>
                                    <a class="text-decoration-none" href="/produtos/<?= (int) $item['id'] ?>">
                                        <?= e($item['name']) ?>
                                    </a>
                                </td>
                                <td><?= e(quantity($item['current_stock'], (string) $item['unit'])) ?></td>
                                <td><?= e(quantity($item['min_stock'], (string) $item['unit'])) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="col-lg-6">
        <div class="card h-100 border-0 shadow-sm">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <span class="fw-semibold">
                    Lotes vencidos ou a vencer (<?= (int) ($expiringLotsCount ?? 0) ?>)
                </span>
            </div>
            <div class="table-responsive">
                <table class="table table-sm table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Produto</th>
                            <th>Lote</th>
                            <th>Validade</th>
                            <th>Saldo</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (($expiringLots ?? []) === []): ?>
                            <tr>
                                <td colspan="4" class="text-center text-secondary py-3">Nenhum lote em alerta.</td>
                            </tr>
                        <?php endif; ?>

                        <?php foreach (($expiringLots ?? []) as $lot): ?>
                            <?php
                            $expires = new DateTimeImmutable((string) $lot['expires_at']);
                            $isExpired = $expires < $todayDate;
                            ?>
                            <tr>
                                <td>
                                    <a class="text-decoration-none" href="/produtos/<?= (int) $lot['product_id'] ?>">
                                        <?= e($lot['product_name']) ?>
                                    </a>
                                </td>
                                <td class="small"><?= e($lot['lot_code']) ?></td>
                                <td>
                                    <span class="badge <?= $isExpired ? 'text-bg-danger' : 'text-bg-warning' ?>">
                                        <?= e($expires->format('d/m/Y')) ?>
                                        · <?= $isExpired ? 'Vencido' : 'A vencer' ?>
                                    </span>
                                </td>
                                <td><?= e(quantity($lot['current_quantity'], (string) $lot['unit'])) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php if (($expiringLotsCount ?? 0) > count($expiringLots ?? [])): ?>
                <div class="card-footer bg-white small text-secondary">
                    Mostrando os primeiros <?= count($expiringLots ?? []) ?> de <?= (int) $expiringLotsCount ?>.
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<div class="card border-0 shadow-sm mt-4">
    <div class="card-body">
        <h2 class="h5">Próximos passos</h2>
        <ul class="mb-0">
            <li>Instalação, testes e backup documentados.</li>
            <li>Relatórios com exportação CSV disponíveis.</li>
            <li>Evoluir o sistema conforme o uso da loja.</li>
        </ul>
    </div>
</div>

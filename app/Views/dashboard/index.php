<?php

use App\Core\Auth;

$user = Auth::user();
?>
<div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-4">
    <div>
        <h1 class="h3 mb-1">Painel</h1>
        <p class="text-secondary mb-0">
            Olá, <?= e($user['name'] ?? 'usuário') ?>. Vendas e cancelamento estão prontos.
        </p>
    </div>
    <span class="badge text-bg-success align-self-center">Fase 4 concluída</span>
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

<div class="card border-0 shadow-sm mt-4">
    <div class="card-body">
        <h2 class="h5">Próximos passos</h2>
        <ul class="mb-0">
            <li>Ajustes, perdas e alertas de validade.</li>
            <li>Relatórios e conciliação de estoque.</li>
            <li>Testes, acabamento e documentação final.</li>
        </ul>
    </div>
</div>

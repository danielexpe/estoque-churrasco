<?php

use App\Core\Auth;

$user = Auth::user();
?>
<div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-4">
    <div>
        <h1 class="h3 mb-1">Painel</h1>
        <p class="text-secondary mb-0">
            Olá, <?= e($user['name'] ?? 'usuário') ?>. Catálogo e cadastros estão prontos.
        </p>
    </div>
    <span class="badge text-bg-success align-self-center">Fase 2 concluída</span>
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

    <div class="col-sm-6 col-xl-3">
        <div class="card h-100 border-0 shadow-sm">
            <div class="card-body">
                <div class="text-uppercase text-secondary small">Vendas hoje</div>
                <div class="display-6">0</div>
                <p class="small text-secondary mb-0">Registro de vendas na fase 4.</p>
            </div>
        </div>
    </div>
</div>

<div class="card border-0 shadow-sm mt-4">
    <div class="card-body">
        <h2 class="h5">Próximos passos</h2>
        <ul class="mb-0">
            <li>Compras de reposição, lotes e custo médio.</li>
            <li>Vendas, cancelamentos e movimentações.</li>
            <li>Relatórios e conciliação de estoque.</li>
        </ul>
    </div>
</div>

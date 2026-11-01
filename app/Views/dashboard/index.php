<?php

use App\Core\Auth;

$user = Auth::user();
?>
<div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-4">
    <div>
        <h1 class="h3 mb-1">Painel</h1>
        <p class="text-secondary mb-0">
            Olá, <?= e($user['name'] ?? 'usuário') ?>. A fundação do sistema está pronta.
        </p>
    </div>
    <span class="badge text-bg-success align-self-center">Fase 0 concluída</span>
</div>

<div class="row g-3">
    <div class="col-md-4">
        <div class="card h-100 border-0 shadow-sm">
            <div class="card-body">
                <div class="text-uppercase text-secondary small">Produtos</div>
                <div class="display-6">0</div>
                <p class="small text-secondary mb-0">Cadastro na próxima fase.</p>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card h-100 border-0 shadow-sm">
            <div class="card-body">
                <div class="text-uppercase text-secondary small">Estoque baixo</div>
                <div class="display-6">0</div>
                <p class="small text-secondary mb-0">Alertas entram com os produtos.</p>
            </div>
        </div>
    </div>

    <div class="col-md-4">
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
            <li>Gestão de usuários e permissões por perfil.</li>
            <li>Cadastro de categorias, produtos e fornecedores.</li>
            <li>Compras, vendas, lotes e movimentações.</li>
        </ul>
    </div>
</div>

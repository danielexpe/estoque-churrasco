<?php

use App\Core\Auth;
use App\Core\Csrf;
use App\Core\Flash;

$appName = (string) config('app.name', 'Estoque Churrasco');
$user = Auth::user();
$currentPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$isDashboard = $currentPath === '/';
$isProducts = str_starts_with($currentPath, '/produtos');
$isSales = str_starts_with($currentPath, '/vendas');
$isMovements = str_starts_with($currentPath, '/movimentacoes');
$isReports = str_starts_with($currentPath, '/relatorios');
$isPurchases = str_starts_with($currentPath, '/compras');
$isAdjustments = str_starts_with($currentPath, '/ajustes');
$isCategories = str_starts_with($currentPath, '/categorias');
$isSuppliers = str_starts_with($currentPath, '/fornecedores');
$isReconciliation = str_starts_with($currentPath, '/relatorios/conciliacao');
$isUsers = str_starts_with($currentPath, '/usuarios');
$current = static fn (bool $active): string => $active ? ' aria-current="page"' : '';
?>
<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($title ?? 'Painel') ?> · <?= e($appName) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="/assets/css/app.css">
</head>
<body>
    <a class="visually-hidden-focusable" href="#main-content">Pular para o conteúdo</a>

    <div class="app-shell">
        <aside class="app-sidebar">
            <a class="app-brand" href="/"><?= e($appName) ?></a>

            <nav class="nav flex-column gap-1" aria-label="Menu principal">
                <a class="nav-link <?= $isDashboard ? 'active' : '' ?>" href="/"<?= $current($isDashboard) ?>>Painel</a>
                <a class="nav-link <?= $isProducts ? 'active' : '' ?>" href="/produtos"<?= $current($isProducts) ?>>Produtos</a>
                <a class="nav-link <?= $isSales ? 'active' : '' ?>" href="/vendas"<?= $current($isSales) ?>>Vendas</a>
                <a class="nav-link <?= $isMovements ? 'active' : '' ?>" href="/movimentacoes"<?= $current($isMovements) ?>>Movimentações</a>
                <a class="nav-link <?= $isReports ? 'active' : '' ?>" href="/relatorios/estoque"<?= $current($isReports) ?>>Relatórios</a>

                <?php if (Auth::isAdmin()): ?>
                    <div class="text-uppercase small text-white-50 mt-3 mb-1 px-2">Administração</div>
                    <a class="nav-link <?= $isPurchases ? 'active' : '' ?>" href="/compras"<?= $current($isPurchases) ?>>Compras</a>
                    <a class="nav-link <?= $isAdjustments ? 'active' : '' ?>" href="/ajustes"<?= $current($isAdjustments) ?>>Ajustes</a>
                    <a class="nav-link <?= $isCategories ? 'active' : '' ?>" href="/categorias"<?= $current($isCategories) ?>>Categorias</a>
                    <a class="nav-link <?= $isSuppliers ? 'active' : '' ?>" href="/fornecedores"<?= $current($isSuppliers) ?>>Fornecedores</a>
                    <a class="nav-link <?= $isReconciliation ? 'active' : '' ?>" href="/relatorios/conciliacao"<?= $current($isReconciliation) ?>>Conciliação</a>
                    <a class="nav-link <?= $isUsers ? 'active' : '' ?>" href="/usuarios"<?= $current($isUsers) ?>>Usuários</a>
                <?php endif; ?>
            </nav>

            <div class="app-sidebar-footer">
                <?php if ($user !== null): ?>
                    <div class="fw-semibold"><?= e($user['name']) ?></div>
                    <div class="small text-white-50"><?= e(role_label((string) $user['role'])) ?></div>

                    <form method="post" action="/logout" class="mt-3">
                        <?= Csrf::field() ?>
                        <button type="submit" class="btn btn-sm btn-outline-light w-100">Sair</button>
                    </form>
                <?php endif; ?>
            </div>
        </aside>

        <div class="app-main">
            <header class="app-topbar">
                <span class="fw-semibold"><?= e($title ?? 'Painel') ?></span>
                <span class="badge text-bg-secondary">Fase 6</span>
            </header>

            <main class="app-content" id="main-content">
                <?php foreach (Flash::all() as $flash): ?>
                    <div class="alert alert-<?= e($flash['type']) ?> alert-dismissible fade show" role="alert">
                        <?= e($flash['message']) ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fechar"></button>
                    </div>
                <?php endforeach; ?>

                <?= $content ?>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="/assets/js/app.js"></script>
</body>
</html>

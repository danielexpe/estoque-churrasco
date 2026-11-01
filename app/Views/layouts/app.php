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
$isPurchases = str_starts_with($currentPath, '/compras');
$isCategories = str_starts_with($currentPath, '/categorias');
$isSuppliers = str_starts_with($currentPath, '/fornecedores');
$isUsers = str_starts_with($currentPath, '/usuarios');
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
    <div class="app-shell">
        <aside class="app-sidebar">
            <a class="app-brand" href="/"><?= e($appName) ?></a>

            <nav class="nav flex-column gap-1">
                <a class="nav-link <?= $isDashboard ? 'active' : '' ?>" href="/">Painel</a>
                <a class="nav-link <?= $isProducts ? 'active' : '' ?>" href="/produtos">Produtos</a>
                <a class="nav-link <?= $isSales ? 'active' : '' ?>" href="/vendas">Vendas</a>

                <?php if (Auth::isAdmin()): ?>
                    <div class="text-uppercase small text-white-50 mt-3 mb-1 px-2">Administração</div>
                    <a class="nav-link <?= $isPurchases ? 'active' : '' ?>" href="/compras">Compras</a>
                    <a class="nav-link <?= $isCategories ? 'active' : '' ?>" href="/categorias">Categorias</a>
                    <a class="nav-link <?= $isSuppliers ? 'active' : '' ?>" href="/fornecedores">Fornecedores</a>
                    <a class="nav-link <?= $isUsers ? 'active' : '' ?>" href="/usuarios">Usuários</a>
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
                <span class="badge text-bg-secondary">Fase 4</span>
            </header>

            <main class="app-content">
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

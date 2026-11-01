<?php

use App\Core\Auth;

$activeReport = $activeReport ?? '';
$links = [
    'stock' => ['/relatorios/estoque', 'Estoque'],
    'low-stock' => ['/relatorios/estoque-baixo', 'Estoque baixo'],
    'expiring' => ['/relatorios/vencimentos', 'Vencimentos'],
];

if (Auth::isAdmin()) {
    $links['sales'] = ['/relatorios/vendas', 'Vendas'];
    $links['reconciliation'] = ['/relatorios/conciliacao', 'Conciliação'];
}
?>
<ul class="nav nav-pills flex-wrap gap-2 mb-4">
    <?php foreach ($links as $key => $link): ?>
        <li class="nav-item">
            <a
                class="nav-link <?= $activeReport === $key ? 'active' : '' ?>"
                href="<?= e($link[0]) ?>"
                <?= $activeReport === $key ? 'aria-current="page"' : '' ?>
            >
                <?= e($link[1]) ?>
            </a>
        </li>
    <?php endforeach; ?>
</ul>

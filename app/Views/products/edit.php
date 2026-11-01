<div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-4">
    <div>
        <h1 class="h3 mb-1">Editar produto</h1>
        <p class="text-secondary mb-0"><?= e($product['name']) ?></p>
    </div>
    <div class="d-flex flex-wrap gap-2 align-self-center">
        <span class="badge <?= (int) $product['is_active'] === 1 ? 'text-bg-success' : 'text-bg-secondary' ?>">
            <?= (int) $product['is_active'] === 1 ? 'Ativo' : 'Inativo' ?>
        </span>
        <a href="/produtos/<?= (int) $product['id'] ?>" class="btn btn-sm btn-outline-secondary">Ver detalhe</a>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body p-4">
        <?php require __DIR__ . '/_form.php'; ?>
    </div>
</div>

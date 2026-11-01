<div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-4">
    <div>
        <h1 class="h3 mb-1">Editar usuário</h1>
        <p class="text-secondary mb-0"><?= e($form['email'] ?? $user['email']) ?></p>
    </div>
    <span class="badge <?= (int) $user['is_active'] === 1 ? 'text-bg-success' : 'text-bg-secondary' ?> align-self-center">
        <?= (int) $user['is_active'] === 1 ? 'Ativo' : 'Inativo' ?>
    </span>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body p-4">
        <?php require __DIR__ . '/_form.php'; ?>
    </div>
</div>

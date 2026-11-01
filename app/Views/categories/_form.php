<?php

use App\Core\Csrf;

$isEdit = $isEdit ?? false;
$errors = $errors ?? [];
$generalError = $generalError ?? null;
?>
<?php if ($generalError !== null): ?>
    <div class="alert alert-danger" role="alert"><?= e($generalError) ?></div>
<?php endif; ?>

<form method="post" action="<?= e($action) ?>" novalidate>
    <?= Csrf::field() ?>

    <div class="row g-3">
        <div class="col-md-6">
            <label for="name" class="form-label">Nome</label>
            <input
                type="text"
                class="form-control <?= isset($errors['name']) ? 'is-invalid' : '' ?>"
                id="name"
                name="name"
                value="<?= e($form['name'] ?? '') ?>"
                maxlength="80"
                required
            >
            <?php if (isset($errors['name'])): ?>
                <div class="invalid-feedback"><?= e($errors['name']) ?></div>
            <?php endif; ?>
        </div>

        <div class="col-md-6">
            <label for="description" class="form-label">Descrição</label>
            <input
                type="text"
                class="form-control <?= isset($errors['description']) ? 'is-invalid' : '' ?>"
                id="description"
                name="description"
                value="<?= e($form['description'] ?? '') ?>"
                maxlength="255"
            >
            <?php if (isset($errors['description'])): ?>
                <div class="invalid-feedback"><?= e($errors['description']) ?></div>
            <?php endif; ?>
        </div>
    </div>

    <div class="form-check mt-4">
        <input type="hidden" name="default_controls_lots" value="0">
        <input
            class="form-check-input"
            type="checkbox"
            id="default_controls_lots"
            name="default_controls_lots"
            value="1"
            <?= !empty($form['default_controls_lots']) ? 'checked' : '' ?>
        >
        <label class="form-check-label" for="default_controls_lots">
            Usar controle de lote por padrão
        </label>
        <div class="form-text">
            Novos produtos desta categoria já virão com lote e validade marcados. Os produtos existentes não são alterados.
        </div>
    </div>

    <div class="d-flex flex-wrap gap-2 mt-4">
        <button type="submit" class="btn btn-dark">
            <?= $isEdit ? 'Salvar alterações' : 'Criar categoria' ?>
        </button>
        <a href="/categorias" class="btn btn-outline-secondary">Cancelar</a>
    </div>
</form>

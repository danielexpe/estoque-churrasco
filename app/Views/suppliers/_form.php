<?php

use App\Core\Csrf;

$isEdit = $isEdit ?? false;
$errors = $errors ?? [];
?>
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
                maxlength="160"
                required
            >
            <?php if (isset($errors['name'])): ?>
                <div class="invalid-feedback"><?= e($errors['name']) ?></div>
            <?php endif; ?>
        </div>

        <div class="col-md-3">
            <label for="document" class="form-label">CPF/CNPJ</label>
            <input
                type="text"
                class="form-control <?= isset($errors['document']) ? 'is-invalid' : '' ?>"
                id="document"
                name="document"
                value="<?= e($form['document'] ?? '') ?>"
                maxlength="20"
            >
            <?php if (isset($errors['document'])): ?>
                <div class="invalid-feedback"><?= e($errors['document']) ?></div>
            <?php endif; ?>
        </div>

        <div class="col-md-3">
            <label for="phone" class="form-label">Telefone</label>
            <input
                type="text"
                class="form-control <?= isset($errors['phone']) ? 'is-invalid' : '' ?>"
                id="phone"
                name="phone"
                value="<?= e($form['phone'] ?? '') ?>"
                maxlength="30"
            >
            <?php if (isset($errors['phone'])): ?>
                <div class="invalid-feedback"><?= e($errors['phone']) ?></div>
            <?php endif; ?>
        </div>

        <div class="col-md-6">
            <label for="email" class="form-label">E-mail</label>
            <input
                type="email"
                class="form-control <?= isset($errors['email']) ? 'is-invalid' : '' ?>"
                id="email"
                name="email"
                value="<?= e($form['email'] ?? '') ?>"
                maxlength="160"
            >
            <?php if (isset($errors['email'])): ?>
                <div class="invalid-feedback"><?= e($errors['email']) ?></div>
            <?php endif; ?>
        </div>

        <div class="col-12">
            <label for="notes" class="form-label">Observações</label>
            <textarea
                class="form-control <?= isset($errors['notes']) ? 'is-invalid' : '' ?>"
                id="notes"
                name="notes"
                rows="3"
                maxlength="500"
            ><?= e($form['notes'] ?? '') ?></textarea>
            <?php if (isset($errors['notes'])): ?>
                <div class="invalid-feedback"><?= e($errors['notes']) ?></div>
            <?php endif; ?>
        </div>
    </div>

    <div class="d-flex flex-wrap gap-2 mt-4">
        <button type="submit" class="btn btn-dark">
            <?= $isEdit ? 'Salvar alterações' : 'Criar fornecedor' ?>
        </button>
        <a href="/fornecedores" class="btn btn-outline-secondary">Cancelar</a>
    </div>
</form>

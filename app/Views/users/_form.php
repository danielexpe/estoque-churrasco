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
                maxlength="120"
                required
            >
            <?php if (isset($errors['name'])): ?>
                <div class="invalid-feedback"><?= e($errors['name']) ?></div>
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
                required
            >
            <?php if (isset($errors['email'])): ?>
                <div class="invalid-feedback"><?= e($errors['email']) ?></div>
            <?php endif; ?>
        </div>

        <div class="col-md-6">
            <label for="role" class="form-label">Perfil</label>
            <select class="form-select <?= isset($errors['role']) ? 'is-invalid' : '' ?>" id="role" name="role" required>
                <option value="operador" <?= ($form['role'] ?? '') === 'operador' ? 'selected' : '' ?>>Operador</option>
                <option value="admin" <?= ($form['role'] ?? '') === 'admin' ? 'selected' : '' ?>>Administrador</option>
            </select>
            <?php if (isset($errors['role'])): ?>
                <div class="invalid-feedback"><?= e($errors['role']) ?></div>
            <?php endif; ?>
        </div>

        <div class="col-md-6">
            <label for="password" class="form-label">
                <?= $isEdit ? 'Nova senha (opcional)' : 'Senha' ?>
            </label>
            <input
                type="password"
                class="form-control <?= isset($errors['password']) ? 'is-invalid' : '' ?>"
                id="password"
                name="password"
                autocomplete="new-password"
                <?= $isEdit ? '' : 'required' ?>
            >
            <?php if (isset($errors['password'])): ?>
                <div class="invalid-feedback"><?= e($errors['password']) ?></div>
            <?php elseif ($isEdit): ?>
                <div class="form-text">Deixe em branco para manter a senha atual.</div>
            <?php endif; ?>
        </div>

        <div class="col-md-6">
            <label for="password_confirmation" class="form-label">Confirmar senha</label>
            <input
                type="password"
                class="form-control <?= isset($errors['password']) ? 'is-invalid' : '' ?>"
                id="password_confirmation"
                name="password_confirmation"
                autocomplete="new-password"
                <?= $isEdit ? '' : 'required' ?>
            >
        </div>
    </div>

    <div class="d-flex flex-wrap gap-2 mt-4">
        <button type="submit" class="btn btn-dark">
            <?= $isEdit ? 'Salvar alterações' : 'Criar usuário' ?>
        </button>
        <a href="/usuarios" class="btn btn-outline-secondary">Cancelar</a>
    </div>
</form>

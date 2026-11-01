<?php

use App\Core\Csrf;

$appName = (string) config('app.name', 'Estoque Churrasco');
?>
<div class="row justify-content-center">
    <div class="col-12 col-sm-8 col-md-5 col-lg-4">
        <div class="card border-0 shadow-sm">
            <div class="card-body p-4 p-md-5">
                <h1 class="h4 mb-1"><?= e($appName) ?></h1>
                <p class="text-secondary mb-4">Acesso interno</p>

                <form method="post" action="/login" novalidate>
                    <?= Csrf::field() ?>

                    <div class="mb-3">
                        <label for="email" class="form-label">E-mail</label>
                        <input
                            type="email"
                            class="form-control"
                            id="email"
                            name="email"
                            value="<?= old('email') ?>"
                            autocomplete="username"
                            required
                            autofocus
                        >
                    </div>

                    <div class="mb-4">
                        <label for="password" class="form-label">Senha</label>
                        <input
                            type="password"
                            class="form-control"
                            id="password"
                            name="password"
                            autocomplete="current-password"
                            required
                        >
                    </div>

                    <button type="submit" class="btn btn-dark w-100">Entrar</button>
                </form>

                <p class="small text-secondary mt-4 mb-0">Acesso restrito aos funcionários da loja.</p>
            </div>
        </div>
    </div>
</div>

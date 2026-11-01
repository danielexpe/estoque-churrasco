<?php

use App\Core\Auth;
use App\Core\Csrf;

$currentUserId = Auth::id();
?>
<div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-4">
    <div>
        <h1 class="h3 mb-1">Usuários</h1>
        <p class="text-secondary mb-0">Gerencie os acessos internos ao sistema.</p>
    </div>
    <a href="/usuarios/novo" class="btn btn-dark">Novo usuário</a>
</div>

<div class="card border-0 shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>Nome</th>
                    <th>E-mail</th>
                    <th>Perfil</th>
                    <th>Status</th>
                    <th>Criado em</th>
                    <th class="text-end">Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($users === []): ?>
                    <tr>
                        <td colspan="6" class="text-center text-secondary py-4">Nenhum usuário cadastrado.</td>
                    </tr>
                <?php endif; ?>

                <?php foreach ($users as $user): ?>
                    <tr>
                        <td>
                            <?= e($user['name']) ?>
                            <?php if ((int) $user['id'] === $currentUserId): ?>
                                <span class="badge text-bg-light border ms-1">Você</span>
                            <?php endif; ?>
                        </td>
                        <td><?= e($user['email']) ?></td>
                        <td><?= e(role_label((string) $user['role'])) ?></td>
                        <td>
                            <?php if ((int) $user['is_active'] === 1): ?>
                                <span class="badge text-bg-success">Ativo</span>
                            <?php else: ?>
                                <span class="badge text-bg-secondary">Inativo</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-secondary small">
                            <?= e(date('d/m/Y', strtotime((string) $user['created_at']))) ?>
                        </td>
                        <td class="text-end">
                            <a class="btn btn-sm btn-outline-dark" href="/usuarios/<?= (int) $user['id'] ?>/editar">
                                Editar
                            </a>

                            <?php if ((int) $user['is_active'] === 1): ?>
                                <form
                                    method="post"
                                    action="/usuarios/<?= (int) $user['id'] ?>/desativar"
                                    class="d-inline"
                                    data-confirm="Desativar este usuário?"
                                >
                                    <?= Csrf::field() ?>
                                    <button type="submit" class="btn btn-sm btn-outline-danger">Desativar</button>
                                </form>
                            <?php else: ?>
                                <form method="post" action="/usuarios/<?= (int) $user['id'] ?>/ativar" class="d-inline">
                                    <?= Csrf::field() ?>
                                    <button type="submit" class="btn btn-sm btn-outline-success">Ativar</button>
                                </form>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

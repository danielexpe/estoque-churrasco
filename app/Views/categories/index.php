<?php

use App\Core\Csrf;
?>
<div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-4">
    <div>
        <h1 class="h3 mb-1">Categorias</h1>
        <p class="text-secondary mb-0">Organize o catálogo e defina o padrão de lote.</p>
    </div>
    <a href="/categorias/nova" class="btn btn-dark">Nova categoria</a>
</div>

<div class="card border-0 shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>Nome</th>
                    <th>Descrição</th>
                    <th>Lote padrão</th>
                    <th>Produtos ativos</th>
                    <th>Status</th>
                    <th class="text-end">Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($categories === []): ?>
                    <tr>
                        <td colspan="6" class="text-center text-secondary py-4">Nenhuma categoria cadastrada.</td>
                    </tr>
                <?php endif; ?>

                <?php foreach ($categories as $category): ?>
                    <tr>
                        <td class="fw-semibold"><?= e($category['name']) ?></td>
                        <td class="text-secondary small"><?= e($category['description'] ?? '—') ?></td>
                        <td>
                            <?php if ((int) $category['default_controls_lots'] === 1): ?>
                                <span class="badge text-bg-info">Sim</span>
                            <?php else: ?>
                                <span class="text-secondary">Não</span>
                            <?php endif; ?>
                        </td>
                        <td><?= (int) $category['products_count'] ?></td>
                        <td>
                            <?php if ((int) $category['is_active'] === 1): ?>
                                <span class="badge text-bg-success">Ativa</span>
                            <?php else: ?>
                                <span class="badge text-bg-secondary">Inativa</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-end">
                            <a class="btn btn-sm btn-outline-dark" href="/categorias/<?= (int) $category['id'] ?>/editar">
                                Editar
                            </a>

                            <?php if ((int) $category['is_active'] === 1): ?>
                                <form
                                    method="post"
                                    action="/categorias/<?= (int) $category['id'] ?>/desativar"
                                    class="d-inline"
                                    data-confirm="Desativar esta categoria?"
                                >
                                    <?= Csrf::field() ?>
                                    <button type="submit" class="btn btn-sm btn-outline-danger">Desativar</button>
                                </form>
                            <?php else: ?>
                                <form method="post" action="/categorias/<?= (int) $category['id'] ?>/ativar" class="d-inline">
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

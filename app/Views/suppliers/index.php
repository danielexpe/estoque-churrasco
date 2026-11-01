<?php

use App\Core\Csrf;
?>
<div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-4">
    <div>
        <h1 class="h3 mb-1">Fornecedores</h1>
        <p class="text-secondary mb-0">Cadastro dos parceiros de reposição.</p>
    </div>
    <a href="/fornecedores/novo" class="btn btn-dark">Novo fornecedor</a>
</div>

<form method="get" action="/fornecedores" class="card border-0 shadow-sm mb-3">
    <div class="card-body">
        <div class="row g-2 align-items-end">
            <div class="col-md-8">
                <label for="busca" class="form-label">Buscar</label>
                <input
                    type="text"
                    class="form-control"
                    id="busca"
                    name="busca"
                    value="<?= e($search) ?>"
                    placeholder="Nome, documento ou e-mail"
                >
            </div>
            <div class="col-md-4 d-flex gap-2">
                <button type="submit" class="btn btn-dark">Filtrar</button>
                <a href="/fornecedores" class="btn btn-outline-secondary">Limpar</a>
            </div>
        </div>
    </div>
</form>

<div class="card border-0 shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>Nome</th>
                    <th>Documento</th>
                    <th>Telefone</th>
                    <th>E-mail</th>
                    <th>Status</th>
                    <th class="text-end">Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($suppliers === []): ?>
                    <tr>
                        <td colspan="6" class="text-center text-secondary py-4">Nenhum fornecedor encontrado.</td>
                    </tr>
                <?php endif; ?>

                <?php foreach ($suppliers as $supplier): ?>
                    <tr>
                        <td class="fw-semibold"><?= e($supplier['name']) ?></td>
                        <td class="text-secondary small"><?= e($supplier['document'] ?? '—') ?></td>
                        <td class="text-secondary small"><?= e($supplier['phone'] ?? '—') ?></td>
                        <td class="text-secondary small"><?= e($supplier['email'] ?? '—') ?></td>
                        <td>
                            <?php if ((int) $supplier['is_active'] === 1): ?>
                                <span class="badge text-bg-success">Ativo</span>
                            <?php else: ?>
                                <span class="badge text-bg-secondary">Inativo</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-end">
                            <a class="btn btn-sm btn-outline-dark" href="/fornecedores/<?= (int) $supplier['id'] ?>/editar">
                                Editar
                            </a>

                            <?php if ((int) $supplier['is_active'] === 1): ?>
                                <form
                                    method="post"
                                    action="/fornecedores/<?= (int) $supplier['id'] ?>/desativar"
                                    class="d-inline"
                                    data-confirm="Desativar este fornecedor?"
                                >
                                    <?= Csrf::field() ?>
                                    <button type="submit" class="btn btn-sm btn-outline-danger">Desativar</button>
                                </form>
                            <?php else: ?>
                                <form method="post" action="/fornecedores/<?= (int) $supplier['id'] ?>/ativar" class="d-inline">
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

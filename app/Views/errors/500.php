<?php

$debug = config('app.env') !== 'production';
?>
<div class="text-center py-5">
    <div class="display-5 fw-bold">500</div>
    <h1 class="h4 mt-2">Erro interno</h1>
    <p class="text-secondary">Não foi possível concluir a operação. Tente novamente.</p>

    <?php if ($debug && isset($exception)): ?>
        <pre class="text-start small bg-light border rounded p-3 mt-4 text-danger"><?= e($exception->getMessage()) ?></pre>
    <?php endif; ?>

    <a class="btn btn-dark" href="/">Voltar ao painel</a>
</div>

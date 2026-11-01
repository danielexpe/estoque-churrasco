<?php if (($pages ?? 1) > 1): ?>
    <nav class="mt-3" aria-label="Paginação">
        <ul class="pagination pagination-sm mb-0">
            <?php for ($i = 1; $i <= $pages; $i++): ?>
                <?php $query = array_merge($_GET, ['page' => $i]); ?>
                <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                    <a class="page-link" href="?<?= e(http_build_query($query)) ?>"><?= $i ?></a>
                </li>
            <?php endfor; ?>
        </ul>
    </nav>
<?php endif; ?>

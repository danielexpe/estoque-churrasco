<?php

declare(strict_types=1);

namespace App\Core;

use RuntimeException;

final class View
{
    public static function render(string $view, array $data = [], string $layout = 'layouts/app'): void
    {
        $viewFile = BASE_PATH . '/app/Views/' . $view . '.php';

        if (!is_file($viewFile)) {
            throw new RuntimeException('View não encontrada: ' . $view);
        }

        extract($data, EXTR_SKIP);

        ob_start();
        require $viewFile;
        $content = ob_get_clean();

        $layoutFile = BASE_PATH . '/app/Views/' . $layout . '.php';

        if (!is_file($layoutFile)) {
            throw new RuntimeException('Layout não encontrado: ' . $layout);
        }

        require $layoutFile;
    }
}

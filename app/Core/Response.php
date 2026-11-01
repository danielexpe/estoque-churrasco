<?php

declare(strict_types=1);

namespace App\Core;

final class Response
{
    public static function redirect(string $path, int $status = 302): never
    {
        header('Location: ' . $path, true, $status);
        exit;
    }

    public static function abort(int $status, string $view): never
    {
        http_response_code($status);
        View::render($view, [], 'layouts/auth');
        exit;
    }
}

<?php

declare(strict_types=1);

define('BASE_PATH', dirname(__DIR__));

spl_autoload_register(static function (string $class): void {
    $prefix = 'App\\';

    if (!str_starts_with($class, $prefix)) {
        return;
    }

    $relative = substr($class, strlen($prefix));
    $file = BASE_PATH . '/app/' . str_replace('\\', '/', $relative) . '.php';

    if (is_file($file)) {
        require $file;
    }
});

function config(?string $key = null, mixed $default = null): mixed
{
    $config = $GLOBALS['app_config'] ?? [];

    if ($key === null) {
        return $config;
    }

    $value = $config;

    foreach (explode('.', $key) as $segment) {
        if (!is_array($value) || !array_key_exists($segment, $value)) {
            return $default;
        }

        $value = $value[$segment];
    }

    return $value;
}

function e(mixed $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function old(string $key, string $default = ''): string
{
    return e($_SESSION['_old'][$key] ?? $default);
}

$GLOBALS['app_config'] = require BASE_PATH . '/config/config.php';

date_default_timezone_set((string) config('app.timezone', 'America/Sao_Paulo'));

$logDir = BASE_PATH . '/storage/logs';
if (!is_dir($logDir)) {
    @mkdir($logDir, 0775, true);
}

$logFile = $logDir . '/app.log';
if (!is_dir($logDir) || (!is_writable($logDir) && !is_writable($logFile))) {
    $logFile = sys_get_temp_dir() . '/estoque-churrasco.log';
}

ini_set('log_errors', '1');
ini_set('error_log', $logFile);

if (config('app.env') === 'production') {
    ini_set('display_errors', '0');
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', '1');
    error_reporting(E_ALL);
}

if (PHP_SAPI !== 'cli') {
    header_remove('X-Powered-By');
}

if (PHP_SAPI !== 'cli' && session_status() === PHP_SESSION_NONE) {
    session_name((string) config('session.name', 'estoque_churrasco'));
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'domain' => '',
        'secure' => config('app.env') === 'production',
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

set_exception_handler(static function (Throwable $exception): void {
    error_log(sprintf(
        '[%s] %s em %s:%d',
        date('c'),
        $exception->getMessage(),
        $exception->getFile(),
        $exception->getLine()
    ));

    if (PHP_SAPI === 'cli') {
        fwrite(STDERR, 'Erro: ' . $exception->getMessage() . PHP_EOL);
        exit(1);
    }

    http_response_code(500);

    try {
        \App\Core\View::render('errors/500', ['exception' => $exception], 'layouts/auth');
    } catch (Throwable) {
        echo 'Erro interno. Tente novamente mais tarde.';
    }
});

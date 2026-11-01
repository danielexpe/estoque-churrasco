<?php

declare(strict_types=1);

return [
    'app' => [
        'name' => 'Estoque Churrasco',
        'env' => getenv('APP_ENV') ?: 'local',
        'timezone' => getenv('APP_TIMEZONE') ?: 'America/Sao_Paulo',
        'expiry_alert_days' => (int) (getenv('EXPIRY_ALERT_DAYS') ?: 7),
    ],
    'db' => [
        'host' => getenv('DB_HOST') ?: '127.0.0.1',
        'port' => getenv('DB_PORT') ?: '3306',
        'name' => getenv('DB_NAME') ?: 'estoque_churrasco',
        'user' => getenv('DB_USER') ?: 'estoque',
        'pass' => getenv('DB_PASS') ?: '',
    ],
    'session' => [
        'name' => getenv('SESSION_NAME') ?: 'estoque_churrasco',
    ],
];

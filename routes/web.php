<?php

declare(strict_types=1);

use App\Controllers\AuthController;
use App\Controllers\DashboardController;

/** @var App\Core\Router $router */

$router->get('/login', [AuthController::class, 'showLogin'], ['guest']);
$router->post('/login', [AuthController::class, 'login'], ['guest']);
$router->post('/logout', [AuthController::class, 'logout'], ['auth']);
$router->get('/', [DashboardController::class, 'index'], ['auth']);

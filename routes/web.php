<?php

declare(strict_types=1);

use App\Controllers\AuthController;
use App\Controllers\DashboardController;
use App\Controllers\UserController;

/** @var App\Core\Router $router */

$router->get('/login', [AuthController::class, 'showLogin'], ['guest']);
$router->post('/login', [AuthController::class, 'login'], ['guest']);
$router->post('/logout', [AuthController::class, 'logout'], ['auth']);
$router->get('/', [DashboardController::class, 'index'], ['auth']);

$router->get('/usuarios', [UserController::class, 'index'], ['auth', 'role:admin']);
$router->get('/usuarios/novo', [UserController::class, 'create'], ['auth', 'role:admin']);
$router->post('/usuarios', [UserController::class, 'store'], ['auth', 'role:admin']);
$router->get('/usuarios/{id}/editar', [UserController::class, 'edit'], ['auth', 'role:admin']);
$router->post('/usuarios/{id}', [UserController::class, 'update'], ['auth', 'role:admin']);
$router->post('/usuarios/{id}/desativar', [UserController::class, 'deactivate'], ['auth', 'role:admin']);
$router->post('/usuarios/{id}/ativar', [UserController::class, 'activate'], ['auth', 'role:admin']);

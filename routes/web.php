<?php

declare(strict_types=1);

use App\Controllers\AuthController;
use App\Controllers\CategoryController;
use App\Controllers\DashboardController;
use App\Controllers\ProductController;
use App\Controllers\PurchaseController;
use App\Controllers\SaleController;
use App\Controllers\SupplierController;
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

$router->get('/categorias', [CategoryController::class, 'index'], ['auth', 'role:admin']);
$router->get('/categorias/nova', [CategoryController::class, 'create'], ['auth', 'role:admin']);
$router->post('/categorias', [CategoryController::class, 'store'], ['auth', 'role:admin']);
$router->get('/categorias/{id}/editar', [CategoryController::class, 'edit'], ['auth', 'role:admin']);
$router->post('/categorias/{id}', [CategoryController::class, 'update'], ['auth', 'role:admin']);
$router->post('/categorias/{id}/desativar', [CategoryController::class, 'deactivate'], ['auth', 'role:admin']);
$router->post('/categorias/{id}/ativar', [CategoryController::class, 'activate'], ['auth', 'role:admin']);

$router->get('/fornecedores', [SupplierController::class, 'index'], ['auth', 'role:admin']);
$router->get('/fornecedores/novo', [SupplierController::class, 'create'], ['auth', 'role:admin']);
$router->post('/fornecedores', [SupplierController::class, 'store'], ['auth', 'role:admin']);
$router->get('/fornecedores/{id}/editar', [SupplierController::class, 'edit'], ['auth', 'role:admin']);
$router->post('/fornecedores/{id}', [SupplierController::class, 'update'], ['auth', 'role:admin']);
$router->post('/fornecedores/{id}/desativar', [SupplierController::class, 'deactivate'], ['auth', 'role:admin']);
$router->post('/fornecedores/{id}/ativar', [SupplierController::class, 'activate'], ['auth', 'role:admin']);

$router->get('/produtos', [ProductController::class, 'index'], ['auth']);
$router->get('/produtos/novo', [ProductController::class, 'create'], ['auth', 'role:admin']);
$router->post('/produtos', [ProductController::class, 'store'], ['auth', 'role:admin']);
$router->get('/produtos/{id}', [ProductController::class, 'show'], ['auth']);
$router->get('/produtos/{id}/editar', [ProductController::class, 'edit'], ['auth', 'role:admin']);
$router->post('/produtos/{id}', [ProductController::class, 'update'], ['auth', 'role:admin']);
$router->post('/produtos/{id}/desativar', [ProductController::class, 'deactivate'], ['auth', 'role:admin']);
$router->post('/produtos/{id}/ativar', [ProductController::class, 'activate'], ['auth', 'role:admin']);

$router->get('/compras', [PurchaseController::class, 'index'], ['auth', 'role:admin']);
$router->get('/compras/nova', [PurchaseController::class, 'create'], ['auth', 'role:admin']);
$router->post('/compras', [PurchaseController::class, 'store'], ['auth', 'role:admin']);
$router->get('/compras/{id}', [PurchaseController::class, 'show'], ['auth', 'role:admin']);

$router->get('/vendas', [SaleController::class, 'index'], ['auth']);
$router->get('/vendas/nova', [SaleController::class, 'create'], ['auth']);
$router->post('/vendas', [SaleController::class, 'store'], ['auth']);
$router->get('/vendas/{id}', [SaleController::class, 'show'], ['auth']);
$router->post('/vendas/{id}/cancelar', [SaleController::class, 'cancel'], ['auth', 'role:admin']);

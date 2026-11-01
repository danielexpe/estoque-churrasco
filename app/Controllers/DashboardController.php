<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\View;
use App\Models\Product;
use App\Models\Sale;
use App\Models\User;

final class DashboardController
{
    public function index(): void
    {
        $today = date('Y-m-d');

        View::render('dashboard/index', [
            'title' => 'Painel',
            'activeUsers' => User::countActive(),
            'activeProducts' => Product::countActive(),
            'lowStockProducts' => Product::countLowStock(),
            'salesToday' => Sale::countToday($today),
            'salesTodayTotal' => Sale::totalToday($today),
        ], 'layouts/app');
    }
}

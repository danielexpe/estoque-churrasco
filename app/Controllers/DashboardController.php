<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\View;
use App\Models\Product;
use App\Models\ProductLot;
use App\Models\Sale;
use App\Models\User;
use DateTimeImmutable;

final class DashboardController
{
    public function index(): void
    {
        $today = date('Y-m-d');
        $expiryDays = (int) config('app.expiry_alert_days', 7);
        $expiryThreshold = (new DateTimeImmutable($today))
            ->modify('+' . $expiryDays . ' days')
            ->format('Y-m-d');

        View::render('dashboard/index', [
            'title' => 'Painel',
            'activeUsers' => User::countActive(),
            'activeProducts' => Product::countActive(),
            'lowStockProducts' => Product::countLowStock(),
            'lowStockList' => Product::lowStock(5),
            'expiringLots' => ProductLot::expiringAlerts($expiryThreshold, 10),
            'expiringLotsCount' => ProductLot::countExpiringAlerts($expiryThreshold),
            'salesToday' => Sale::countToday($today),
            'salesTodayTotal' => Sale::totalToday($today),
        ], 'layouts/app');
    }
}

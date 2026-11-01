<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\View;
use App\Services\ReportService;

final class ReportController
{
    private ReportService $reports;

    public function __construct()
    {
        $this->reports = new ReportService();
    }

    public function reconciliation(): void
    {
        View::render('reports/reconciliation', [
            'title' => 'Conciliação de estoque',
            'rows' => $this->reports->reconciliation(),
        ]);
    }
}

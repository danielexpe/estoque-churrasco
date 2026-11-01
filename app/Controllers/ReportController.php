<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Csv;
use App\Core\Request;
use App\Core\View;
use App\Services\ReportService;
use DateTimeImmutable;

final class ReportController
{
    private ReportService $reports;

    public function __construct()
    {
        $this->reports = new ReportService();
    }

    public function stock(): void
    {
        View::render('reports/stock', [
            'title' => 'Estoque atual',
            'rows' => $this->reports->stock(),
            'activeReport' => 'stock',
        ]);
    }

    public function stockCsv(): never
    {
        $rows = [];

        foreach ($this->reports->stock() as $row) {
            $rows[] = [
                $row['name'],
                $row['category_name'] ?? 'Sem categoria',
                $row['unit'],
                number_format((float) $row['current_stock'], 3, ',', ''),
                number_format((float) $row['min_stock'], 3, ',', ''),
                number_format((float) $row['sale_price'], 2, ',', ''),
                (int) $row['controls_lots'] === 1 ? 'Sim' : 'Não',
                (int) $row['is_active'] === 1 ? 'Ativo' : 'Inativo',
            ];
        }

        Csv::download(
            'estoque_atual.csv',
            ['Produto', 'Categoria', 'Unidade', 'Saldo', 'Mínimo', 'Preço', 'Controle de lote', 'Status'],
            $rows
        );
    }

    public function lowStock(): void
    {
        View::render('reports/low-stock', [
            'title' => 'Estoque baixo',
            'rows' => $this->reports->lowStock(),
            'activeReport' => 'low-stock',
        ]);
    }

    public function lowStockCsv(): never
    {
        $rows = [];

        foreach ($this->reports->lowStock() as $row) {
            $rows[] = [
                $row['name'],
                $row['category_name'] ?? 'Sem categoria',
                $row['unit'],
                number_format((float) $row['current_stock'], 3, ',', ''),
                number_format((float) $row['min_stock'], 3, ',', ''),
                number_format((float) $row['sale_price'], 2, ',', ''),
                (int) $row['controls_lots'] === 1 ? 'Sim' : 'Não',
            ];
        }

        Csv::download(
            'estoque_baixo.csv',
            ['Produto', 'Categoria', 'Unidade', 'Saldo', 'Mínimo', 'Preço', 'Controle de lote'],
            $rows
        );
    }

    public function expiring(): void
    {
        View::render('reports/expiring', [
            'title' => 'Vencimentos',
            'rows' => $this->reports->expiringLots($this->expiryThreshold()),
            'threshold' => $this->expiryThreshold(),
            'activeReport' => 'expiring',
        ]);
    }

    public function expiringCsv(): never
    {
        $today = new DateTimeImmutable('today');
        $rows = [];

        foreach ($this->reports->expiringLots($this->expiryThreshold()) as $lot) {
            $expires = new DateTimeImmutable((string) $lot['expires_at']);
            $status = $expires < $today ? 'Vencido' : 'A vencer';

            $rows[] = [
                $lot['product_name'],
                $lot['lot_code'],
                $expires->format('d/m/Y'),
                number_format((float) $lot['current_quantity'], 3, ',', ''),
                $lot['unit'],
                $status,
            ];
        }

        Csv::download(
            'vencimentos.csv',
            ['Produto', 'Lote', 'Validade', 'Saldo', 'Unidade', 'Situação'],
            $rows
        );
    }

    public function sales(): void
    {
        [$dateFrom, $dateTo] = $this->salesDates();

        View::render('reports/sales', [
            'title' => 'Vendas por período',
            'data' => $this->reports->sales($dateFrom, $dateTo),
            'filters' => [
                'date_from' => $dateFrom,
                'date_to' => $dateTo,
            ],
            'activeReport' => 'sales',
        ]);
    }

    public function salesCsv(): never
    {
        [$dateFrom, $dateTo] = $this->salesDates();
        $data = $this->reports->sales($dateFrom, $dateTo);
        $all = array_merge($data['concluded'], $data['canceled']);
        usort(
            $all,
            static fn (array $a, array $b): int => strcmp((string) $b['sold_at'], (string) $a['sold_at'])
        );

        $rows = [];

        foreach ($all as $sale) {
            $rows[] = [
                (int) $sale['id'],
                date('d/m/Y H:i', strtotime((string) $sale['sold_at'])),
                $sale['status'] === 'concluida' ? 'Concluída' : 'Cancelada',
                $sale['user_name'],
                (int) $sale['items_count'],
                number_format((float) $sale['total_value'], 2, ',', ''),
                $sale['cancel_reason'] ?? '',
            ];
        }

        Csv::download(
            'vendas_' . $dateFrom . '_a_' . $dateTo . '.csv',
            ['ID', 'Data', 'Status', 'Responsável', 'Itens', 'Total', 'Motivo do cancelamento'],
            $rows
        );
    }

    public function reconciliation(): void
    {
        View::render('reports/reconciliation', [
            'title' => 'Conciliação de estoque',
            'rows' => $this->reports->reconciliation(),
            'activeReport' => 'reconciliation',
        ]);
    }

    private function expiryThreshold(): string
    {
        $days = (int) config('app.expiry_alert_days', 7);

        return (new DateTimeImmutable('today'))
            ->modify('+' . $days . ' days')
            ->format('Y-m-d');
    }

    private function salesDates(): array
    {
        $dateFrom = Request::string('data_inicio');
        $dateTo = Request::string('data_fim');

        if (!$this->isValidDate($dateFrom)) {
            $dateFrom = date('Y-m-01');
        }

        if (!$this->isValidDate($dateTo)) {
            $dateTo = date('Y-m-t');
        }

        if ($dateFrom > $dateTo) {
            [$dateFrom, $dateTo] = [$dateTo, $dateFrom];
        }

        return [$dateFrom, $dateTo];
    }

    private function isValidDate(string $value): bool
    {
        $date = DateTimeImmutable::createFromFormat('!Y-m-d', $value);

        return $date !== false && $date->format('Y-m-d') === $value;
    }
}

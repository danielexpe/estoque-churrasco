<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\View;
use App\Models\Product;
use App\Models\StockMovement;
use App\Models\User;
use DateTimeImmutable;

final class MovementController
{
    public function index(): void
    {
        $rawProduct = Request::string('produto');
        $rawType = Request::string('tipo');
        $rawUser = Request::string('usuario');
        $rawDateFrom = Request::string('data_inicio');
        $rawDateTo = Request::string('data_fim');

        $types = [
            'entrada_compra',
            'saida_venda',
            'estorno_venda',
            'ajuste_positivo',
            'ajuste_negativo',
            'perda',
        ];

        $type = in_array($rawType, $types, true) ? $rawType : '';
        $productId = ctype_digit($rawProduct) && (int) $rawProduct > 0 ? (int) $rawProduct : null;
        $userId = ctype_digit($rawUser) && (int) $rawUser > 0 ? (int) $rawUser : null;

        $filters = [
            'product_id' => $productId,
            'movement_type' => $type !== '' ? $type : null,
            'user_id' => $userId,
            'date_from' => $this->isValidDate($rawDateFrom) ? $rawDateFrom : null,
            'date_to' => $this->isValidDate($rawDateTo) ? $rawDateTo : null,
        ];

        $page = max(1, (int) ($_GET['page'] ?? 1));
        $result = StockMovement::paginate($filters, $page);

        View::render('movements/index', [
            'title' => 'Movimentações',
            'movements' => $result['items'],
            'total' => $result['total'],
            'page' => $result['page'],
            'pages' => $result['pages'],
            'filters' => [
                'product_id' => $rawProduct,
                'movement_type' => $type,
                'user_id' => $rawUser,
                'date_from' => $rawDateFrom,
                'date_to' => $rawDateTo,
            ],
            'products' => Product::allForSelect(),
            'users' => User::all(),
        ]);
    }

    private function isValidDate(string $value): bool
    {
        $date = DateTimeImmutable::createFromFormat('!Y-m-d', $value);

        return $date !== false && $date->format('Y-m-d') === $value;
    }
}

<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Csrf;
use App\Core\Flash;
use App\Core\Money;
use App\Core\Request;
use App\Core\Response;
use App\Core\Validator;
use App\Core\View;
use App\Models\Product;
use App\Services\SaleService;
use DateTimeImmutable;
use DomainException;

final class SaleController
{
    private SaleService $sales;

    public function __construct()
    {
        $this->sales = new SaleService();
    }

    public function index(): void
    {
        $rawDateFrom = Request::string('data_inicio');
        $rawDateTo = Request::string('data_fim');
        $status = Request::string('status', 'all');

        if (!in_array($status, ['all', 'concluida', 'cancelada'], true)) {
            $status = 'all';
        }

        $filters = [
            'date_from' => $this->isValidDate($rawDateFrom) ? $rawDateFrom : null,
            'date_to' => $this->isValidDate($rawDateTo) ? $rawDateTo : null,
            'status' => $status,
        ];

        $page = max(1, (int) ($_GET['page'] ?? 1));
        $result = $this->sales->paginate($filters, $page);

        View::render('sales/index', [
            'title' => 'Vendas',
            'sales' => $result['items'],
            'total' => $result['total'],
            'page' => $result['page'],
            'pages' => $result['pages'],
            'filters' => [
                'date_from' => $rawDateFrom,
                'date_to' => $rawDateTo,
                'status' => $status,
            ],
        ]);
    }

    public function create(): void
    {
        $products = Product::allActiveForSale(date('Y-m-d'));

        $this->renderCreate(['items' => [$this->emptyItem()]], [], null, $products);
    }

    public function store(): void
    {
        $this->checkCsrf('/vendas/nova');

        $form = ['items' => $this->itemsFromRequest()];
        $products = Product::allActiveForSale(date('Y-m-d'));
        $errors = $this->validate($form, $products);

        if ($errors !== []) {
            $this->renderCreate($form, $errors, null, $products);
            return;
        }

        try {
            $id = $this->sales->create(
                $this->normalizeItems($form['items']),
                (int) Auth::id(),
                date('Y-m-d H:i:s')
            );
        } catch (DomainException $exception) {
            $this->renderCreate($form, [], $exception->getMessage(), $products);
            return;
        }

        Flash::success('Venda registrada e estoque atualizado.');
        Response::redirect('/vendas/' . $id);
    }

    public function show(string $id): void
    {
        $sale = $this->findOr404((int) $id);

        View::render('sales/show', [
            'title' => 'Venda #' . (int) $sale['id'],
            'sale' => $sale,
            'items' => $this->sales->items((int) $sale['id']),
            'itemLots' => $this->sales->itemLots((int) $sale['id']),
        ]);
    }

    public function cancel(string $id): void
    {
        $this->checkCsrf('/vendas');
        $sale = $this->findOr404((int) $id);

        $reason = Request::string('cancel_reason');
        $validator = (new Validator())
            ->required('cancel_reason', $reason, 'Informe o motivo do cancelamento.')
            ->minLength('cancel_reason', $reason, 5, 'O motivo deve ter pelo menos 5 caracteres.')
            ->maxLength('cancel_reason', $reason, 500, 'O motivo deve ter no máximo 500 caracteres.');

        if ($validator->fails()) {
            $errors = $validator->errors();
            Flash::error((string) reset($errors));
            Response::redirect('/vendas/' . (int) $sale['id']);
        }

        try {
            $this->sales->cancel(
                (int) $sale['id'],
                (int) Auth::id(),
                $reason,
                date('Y-m-d H:i:s')
            );
        } catch (DomainException $exception) {
            Flash::error($exception->getMessage());
            Response::redirect('/vendas/' . (int) $sale['id']);
        }

        Flash::success('Venda cancelada e estoque devolvido.');
        Response::redirect('/vendas/' . (int) $sale['id']);
    }

    private function validate(array $form, array $products): array
    {
        $errors = [];
        $productMap = [];

        foreach ($products as $product) {
            $productMap[(int) $product['id']] = $product;
        }

        $hasItem = false;
        $totalsByProduct = [];

        foreach ($form['items'] as $index => $item) {
            if ($this->isEmptyItem($item)) {
                continue;
            }

            $hasItem = true;
            $prefix = 'items.' . $index . '.';
            $productId = (string) $item['product_id'];
            $product = null;

            if ($productId === '') {
                $errors[$prefix . 'product_id'] = 'Selecione um produto.';
            } elseif (!ctype_digit($productId) || !isset($productMap[(int) $productId])) {
                $errors[$prefix . 'product_id'] = 'Selecione um produto válido e ativo.';
            } else {
                $product = $productMap[(int) $productId];
            }

            $rowValidator = (new Validator())
                ->decimal($prefix . 'quantity', $item['quantity'], 3, 'Informe a quantidade.');

            $errors = array_merge($errors, $rowValidator->errors());

            $quantity = str_replace(',', '.', trim($item['quantity']));
            $quantityIsValid = preg_match('/^\d+(\.\d{1,3})?$/', $quantity) === 1;

            if ($quantityIsValid && Money::compare($quantity, '0', 3) <= 0) {
                $errors[$prefix . 'quantity'] = 'A quantidade deve ser maior que zero.';
            }

            if ($product !== null && $quantityIsValid) {
                $id = (int) $productId;
                $totalsByProduct[$id] = Money::add(
                    $totalsByProduct[$id] ?? '0.000',
                    $quantity,
                    3
                );
            }
        }

        if (!$hasItem) {
            $errors['items'] = 'Adicione pelo menos um item à venda.';
        }

        foreach ($totalsByProduct as $productId => $quantity) {
            $product = $productMap[$productId];
            $available = (string) $product['available_quantity'];

            if (Money::compare($quantity, $available, 3) > 0) {
                $errors['items'] = 'Estoque disponível insuficiente para '
                    . $product['name']
                    . '. Disponível: '
                    . quantity($available, (string) $product['unit'])
                    . '.';
            }
        }

        return $errors;
    }

    private function itemsFromRequest(): array
    {
        $productIds = $_POST['product_id'] ?? [];
        $quantities = $_POST['quantity'] ?? [];

        $productIds = is_array($productIds) ? $productIds : [];
        $quantities = is_array($quantities) ? $quantities : [];

        $count = max(count($productIds), count($quantities));

        if ($count === 0) {
            return [$this->emptyItem()];
        }

        $items = [];

        for ($index = 0; $index < $count; $index++) {
            $items[] = [
                'product_id' => $this->scalarString($productIds[$index] ?? ''),
                'quantity' => $this->scalarString($quantities[$index] ?? ''),
            ];
        }

        return $items;
    }

    private function normalizeItems(array $items): array
    {
        $normalized = [];

        foreach ($items as $item) {
            if ($this->isEmptyItem($item)) {
                continue;
            }

            $normalized[] = [
                'product_id' => (int) $item['product_id'],
                'quantity' => $this->normalizeDecimal($item['quantity'], 3),
            ];
        }

        return $normalized;
    }

    private function emptyItem(): array
    {
        return [
            'product_id' => '',
            'quantity' => '',
        ];
    }

    private function isEmptyItem(array $item): bool
    {
        return $item['product_id'] === '' && $item['quantity'] === '';
    }

    private function normalizeDecimal(string $value, int $scale): string
    {
        $normalized = str_replace(',', '.', trim($value));

        return number_format((float) $normalized, $scale, '.', '');
    }

    private function isValidDate(string $value): bool
    {
        $date = DateTimeImmutable::createFromFormat('!Y-m-d', $value);

        return $date !== false && $date->format('Y-m-d') === $value;
    }

    private function scalarString(mixed $value): string
    {
        return is_scalar($value) ? trim((string) $value) : '';
    }

    private function renderCreate(
        array $form,
        array $errors = [],
        ?string $generalError = null,
        ?array $products = null
    ): void {
        View::render('sales/create', [
            'title' => 'Nova venda',
            'form' => $form,
            'errors' => $errors,
            'generalError' => $generalError,
            'products' => $products ?? Product::allActiveForSale(date('Y-m-d')),
        ]);
    }

    private function findOr404(int $id): array
    {
        try {
            return $this->sales->find($id);
        } catch (DomainException) {
            Response::abort(404, 'errors/404');
        }
    }

    private function checkCsrf(string $fallback): void
    {
        if (!Csrf::validate($_POST['_csrf'] ?? null)) {
            Flash::error('Sessão expirada. Tente novamente.');
            Response::redirect($fallback);
        }
    }
}

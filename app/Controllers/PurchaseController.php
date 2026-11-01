<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Csrf;
use App\Core\Flash;
use App\Core\Request;
use App\Core\Response;
use App\Core\Validator;
use App\Core\View;
use App\Models\Product;
use App\Models\StockMovement;
use App\Models\Supplier;
use App\Services\PurchaseService;
use DateTimeImmutable;
use DomainException;

final class PurchaseController
{
    private PurchaseService $purchases;

    public function __construct()
    {
        $this->purchases = new PurchaseService();
    }

    public function index(): void
    {
        $rawDateFrom = Request::string('data_inicio');
        $rawDateTo = Request::string('data_fim');
        $rawSupplier = Request::string('fornecedor');

        $filters = [
            'date_from' => $this->isValidDate($rawDateFrom) ? $rawDateFrom : null,
            'date_to' => $this->isValidDate($rawDateTo) ? $rawDateTo : null,
            'supplier_id' => ctype_digit($rawSupplier) && (int) $rawSupplier > 0
                ? (int) $rawSupplier
                : null,
        ];

        $page = max(1, (int) ($_GET['page'] ?? 1));
        $result = $this->purchases->paginate($filters, $page);

        View::render('purchases/index', [
            'title' => 'Compras',
            'purchases' => $result['items'],
            'total' => $result['total'],
            'page' => $result['page'],
            'pages' => $result['pages'],
            'filters' => [
                'date_from' => $rawDateFrom,
                'date_to' => $rawDateTo,
                'supplier_id' => $rawSupplier,
            ],
            'suppliers' => Supplier::all(),
        ]);
    }

    public function create(): void
    {
        $this->renderCreate([
            'supplier_id' => '',
            'purchased_at' => date('Y-m-d'),
            'document_number' => '',
            'notes' => '',
            'items' => [$this->emptyItem()],
        ]);
    }

    public function store(): void
    {
        $this->checkCsrf('/compras/nova');

        $form = $this->formData();
        $suppliers = Supplier::allActive();
        $products = Product::allActiveForSelect();
        $errors = $this->validate($form, $suppliers, $products);

        if ($errors !== []) {
            $this->renderCreate($form, $errors, null, $suppliers, $products);
            return;
        }

        $items = $this->normalizeItems($form['items']);

        try {
            $id = $this->purchases->create([
                'supplier_id' => $form['supplier_id'] === '' ? null : (int) $form['supplier_id'],
                'purchased_at' => $form['purchased_at'] . ' 00:00:00',
                'document_number' => $form['document_number'] === '' ? null : $form['document_number'],
                'notes' => $form['notes'] === '' ? null : $form['notes'],
            ], $items, (int) Auth::id());
        } catch (DomainException $exception) {
            $this->renderCreate($form, [], $exception->getMessage(), $suppliers, $products);
            return;
        }

        Flash::success('Compra registrada e estoque atualizado.');
        Response::redirect('/compras/' . $id);
    }

    public function show(string $id): void
    {
        $purchase = $this->findOr404((int) $id);

        View::render('purchases/show', [
            'title' => 'Compra #' . (int) $purchase['id'],
            'purchase' => $purchase,
            'items' => $this->purchases->items((int) $purchase['id']),
            'movements' => StockMovement::forPurchase((int) $purchase['id']),
        ]);
    }

    private function validate(array $form, array $suppliers, array $products): array
    {
        $errors = [];

        $supplierIds = array_map(
            static fn (array $supplier): int => (int) $supplier['id'],
            $suppliers
        );

        if ($form['supplier_id'] !== '') {
            if (
                !ctype_digit($form['supplier_id'])
                || !in_array((int) $form['supplier_id'], $supplierIds, true)
            ) {
                $errors['supplier_id'] = 'Selecione um fornecedor ativo.';
            }
        }

        if ($form['purchased_at'] === '') {
            $errors['purchased_at'] = 'Informe a data da compra.';
        } elseif (!$this->isValidDate($form['purchased_at'])) {
            $errors['purchased_at'] = 'Informe uma data válida.';
        } else {
            $date = DateTimeImmutable::createFromFormat('!Y-m-d', $form['purchased_at']);

            if ($date !== false && $date > new DateTimeImmutable('today')) {
                $errors['purchased_at'] = 'A data da compra não pode ser futura.';
            }
        }

        $headerValidator = (new Validator())
            ->maxLength('document_number', $form['document_number'], 80, 'O documento deve ter no máximo 80 caracteres.')
            ->maxLength('notes', $form['notes'], 500, 'As observações devem ter no máximo 500 caracteres.');

        $errors = array_merge($errors, $headerValidator->errors());

        $productMap = [];

        foreach ($products as $product) {
            $productMap[(int) $product['id']] = $product;
        }

        $seenLots = [];
        $hasItem = false;

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
                ->decimal($prefix . 'quantity', $item['quantity'], 3, 'Informe a quantidade.')
                ->decimal($prefix . 'unit_cost', $item['unit_cost'], 2, 'Informe o custo unitário.');

            $errors = array_merge($errors, $rowValidator->errors());

            $quantity = str_replace(',', '.', trim($item['quantity']));
            if (
                preg_match('/^\d+(\.\d{1,3})?$/', $quantity)
                && bccomp($quantity, '0', 3) <= 0
            ) {
                $errors[$prefix . 'quantity'] = 'A quantidade deve ser maior que zero.';
            }

            if ($product !== null && (int) $product['controls_lots'] === 1) {
                $lotValidator = (new Validator())
                    ->required($prefix . 'lot_code', $item['lot_code'], 'Informe o lote.')
                    ->maxLength($prefix . 'lot_code', $item['lot_code'], 80, 'O lote deve ter no máximo 80 caracteres.');

                $errors = array_merge($errors, $lotValidator->errors());

                if ($item['expires_at'] === '') {
                    $errors[$prefix . 'expires_at'] = 'Informe a validade.';
                } elseif (!$this->isValidDate($item['expires_at'])) {
                    $errors[$prefix . 'expires_at'] = 'Informe uma validade válida.';
                }

                if ($item['lot_code'] !== '') {
                    $lotKey = (int) $productId . '|' . strtolower(trim($item['lot_code']));

                    if (isset($seenLots[$lotKey])) {
                        $errors[$prefix . 'lot_code'] = 'Lote repetido para este produto na compra.';
                    } else {
                        $seenLots[$lotKey] = true;
                    }
                }
            }
        }

        if (!$hasItem) {
            $errors['items'] = 'Adicione pelo menos um item à compra.';
        }

        return $errors;
    }

    private function formData(): array
    {
        return [
            'supplier_id' => Request::string('supplier_id'),
            'purchased_at' => Request::string('purchased_at'),
            'document_number' => Request::string('document_number'),
            'notes' => Request::string('notes'),
            'items' => $this->itemsFromRequest(),
        ];
    }

    private function itemsFromRequest(): array
    {
        $productIds = $_POST['product_id'] ?? [];
        $quantities = $_POST['quantity'] ?? [];
        $unitCosts = $_POST['unit_cost'] ?? [];
        $lotCodes = $_POST['lot_code'] ?? [];
        $expiresDates = $_POST['expires_at'] ?? [];

        $productIds = is_array($productIds) ? $productIds : [];
        $quantities = is_array($quantities) ? $quantities : [];
        $unitCosts = is_array($unitCosts) ? $unitCosts : [];
        $lotCodes = is_array($lotCodes) ? $lotCodes : [];
        $expiresDates = is_array($expiresDates) ? $expiresDates : [];

        $count = max(
            count($productIds),
            count($quantities),
            count($unitCosts),
            count($lotCodes),
            count($expiresDates)
        );

        if ($count === 0) {
            return [$this->emptyItem()];
        }

        $items = [];

        for ($index = 0; $index < $count; $index++) {
            $items[] = [
                'product_id' => $this->scalarString($productIds[$index] ?? ''),
                'quantity' => $this->scalarString($quantities[$index] ?? ''),
                'unit_cost' => $this->scalarString($unitCosts[$index] ?? ''),
                'lot_code' => $this->scalarString($lotCodes[$index] ?? ''),
                'expires_at' => $this->scalarString($expiresDates[$index] ?? ''),
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

            $lotCode = trim($item['lot_code']);
            $expiresAt = trim($item['expires_at']);

            $normalized[] = [
                'product_id' => (int) $item['product_id'],
                'quantity' => $this->normalizeDecimal($item['quantity'], 3),
                'unit_cost' => $this->normalizeDecimal($item['unit_cost'], 2),
                'lot_code' => $lotCode === '' ? null : $lotCode,
                'expires_at' => $expiresAt === '' ? null : $expiresAt,
            ];
        }

        return $normalized;
    }

    private function emptyItem(): array
    {
        return [
            'product_id' => '',
            'quantity' => '',
            'unit_cost' => '',
            'lot_code' => '',
            'expires_at' => '',
        ];
    }

    private function isEmptyItem(array $item): bool
    {
        return $item['product_id'] === ''
            && $item['quantity'] === ''
            && $item['unit_cost'] === ''
            && $item['lot_code'] === ''
            && $item['expires_at'] === '';
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
        ?array $suppliers = null,
        ?array $products = null
    ): void {
        View::render('purchases/create', [
            'title' => 'Nova compra',
            'form' => $form,
            'errors' => $errors,
            'generalError' => $generalError,
            'suppliers' => $suppliers ?? Supplier::allActive(),
            'products' => $products ?? Product::allActiveForSelect(),
        ]);
    }

    private function findOr404(int $id): array
    {
        try {
            return $this->purchases->find($id);
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

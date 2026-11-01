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
use App\Services\AdjustmentService;
use DateTimeImmutable;
use DomainException;

final class AdjustmentController
{
    private AdjustmentService $adjustments;

    public function __construct()
    {
        $this->adjustments = new AdjustmentService();
    }

    public function index(): void
    {
        $rawDateFrom = Request::string('data_inicio');
        $rawDateTo = Request::string('data_fim');
        $type = Request::string('tipo');

        if (!in_array($type, ['ajuste_positivo', 'ajuste_negativo', 'perda'], true)) {
            $type = '';
        }

        $filters = [
            'date_from' => $this->isValidDate($rawDateFrom) ? $rawDateFrom : null,
            'date_to' => $this->isValidDate($rawDateTo) ? $rawDateTo : null,
            'adjustment_type' => $type !== '' ? $type : null,
        ];

        $page = max(1, (int) ($_GET['page'] ?? 1));
        $result = $this->adjustments->paginate($filters, $page);

        View::render('adjustments/index', [
            'title' => 'Ajustes e perdas',
            'adjustments' => $result['items'],
            'total' => $result['total'],
            'page' => $result['page'],
            'pages' => $result['pages'],
            'filters' => [
                'date_from' => $rawDateFrom,
                'date_to' => $rawDateTo,
                'adjustment_type' => $type,
            ],
        ]);
    }

    public function create(): void
    {
        $this->renderCreate([
            'product_id' => '',
            'adjustment_type' => 'ajuste_positivo',
            'quantity' => '',
            'unit_cost' => '',
            'lot_code' => '',
            'expires_at' => '',
            'reason' => '',
        ]);
    }

    public function store(): void
    {
        $this->checkCsrf('/ajustes/novo');

        $form = $this->formData();
        $products = Product::allActiveForSelect();
        $errors = $this->validate($form, $products);

        if ($errors !== []) {
            $this->renderCreate($form, $errors, null, $products);
            return;
        }

        try {
            $this->adjustments->create(
                $form['adjustment_type'],
                (int) $form['product_id'],
                $this->normalizeDecimal($form['quantity'], 3),
                $form['adjustment_type'] === 'ajuste_positivo' && $form['unit_cost'] !== ''
                    ? $this->normalizeDecimal($form['unit_cost'], 2)
                    : null,
                $form['lot_code'] !== '' ? $form['lot_code'] : null,
                $form['expires_at'] !== '' ? $form['expires_at'] : null,
                $form['reason'],
                (int) Auth::id()
            );
        } catch (DomainException $exception) {
            $this->renderCreate($form, [], $exception->getMessage(), $products);
            return;
        }

        Flash::success('Ajuste registrado e estoque atualizado.');
        Response::redirect('/ajustes');
    }

    private function validate(array $form, array $products): array
    {
        $errors = [];
        $productMap = [];

        foreach ($products as $product) {
            $productMap[(int) $product['id']] = $product;
        }

        $productId = $form['product_id'];
        $product = null;

        if ($productId === '') {
            $errors['product_id'] = 'Selecione um produto.';
        } elseif (!ctype_digit($productId) || !isset($productMap[(int) $productId])) {
            $errors['product_id'] = 'Selecione um produto válido e ativo.';
        } else {
            $product = $productMap[(int) $productId];
        }

        if (!in_array($form['adjustment_type'], ['ajuste_positivo', 'ajuste_negativo', 'perda'], true)) {
            $errors['adjustment_type'] = 'Selecione o tipo de ajuste.';
        }

        $validator = (new Validator())
            ->decimal('quantity', $form['quantity'], 3, 'Informe a quantidade.')
            ->required('reason', $form['reason'], 'Informe o motivo.')
            ->minLength('reason', $form['reason'], 5, 'O motivo deve ter pelo menos 5 caracteres.')
            ->maxLength('reason', $form['reason'], 500, 'O motivo deve ter no máximo 500 caracteres.');

        $errors = array_merge($errors, $validator->errors());

        $quantity = str_replace(',', '.', trim($form['quantity']));
        $quantityIsValid = preg_match('/^\d+(\.\d{1,3})?$/', $quantity) === 1;

        if ($quantityIsValid && Money::compare($quantity, '0', 3) <= 0) {
            $errors['quantity'] = 'A quantidade deve ser maior que zero.';
        }

        if ($form['adjustment_type'] === 'ajuste_positivo') {
            if ($form['unit_cost'] !== '') {
                $costValidator = (new Validator())
                    ->decimal('unit_cost', $form['unit_cost'], 2, 'Informe um custo válido.');

                $errors = array_merge($errors, $costValidator->errors());
            }

            if ($product !== null && (int) $product['controls_lots'] === 1) {
                $lotValidator = (new Validator())
                    ->required('lot_code', $form['lot_code'], 'Informe o lote.')
                    ->maxLength('lot_code', $form['lot_code'], 80, 'O lote deve ter no máximo 80 caracteres.');

                $errors = array_merge($errors, $lotValidator->errors());

                if ($form['expires_at'] === '') {
                    $errors['expires_at'] = 'Informe a validade.';
                } elseif (!$this->isValidDate($form['expires_at'])) {
                    $errors['expires_at'] = 'Informe uma validade válida.';
                }
            }
        }

        return $errors;
    }

    private function formData(): array
    {
        return [
            'product_id' => Request::string('product_id'),
            'adjustment_type' => Request::string('adjustment_type'),
            'quantity' => Request::string('quantity'),
            'unit_cost' => Request::string('unit_cost'),
            'lot_code' => Request::string('lot_code'),
            'expires_at' => Request::string('expires_at'),
            'reason' => Request::string('reason'),
        ];
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

    private function renderCreate(
        array $form,
        array $errors = [],
        ?string $generalError = null,
        ?array $products = null
    ): void {
        View::render('adjustments/create', [
            'title' => 'Novo ajuste',
            'form' => $form,
            'errors' => $errors,
            'generalError' => $generalError,
            'products' => $products ?? Product::allActiveForSelect(),
        ]);
    }

    private function checkCsrf(string $fallback): void
    {
        if (!Csrf::validate($_POST['_csrf'] ?? null)) {
            Flash::error('Sessão expirada. Tente novamente.');
            Response::redirect($fallback);
        }
    }
}

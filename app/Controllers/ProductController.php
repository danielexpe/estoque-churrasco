<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Csrf;
use App\Core\Flash;
use App\Core\Request;
use App\Core\Response;
use App\Core\Validator;
use App\Core\View;
use App\Models\Product;
use App\Models\ProductLot;
use App\Models\StockMovement;
use App\Services\CategoryService;
use App\Services\ProductService;
use DomainException;

final class ProductController
{
    private ProductService $products;
    private CategoryService $categories;

    public function __construct()
    {
        $this->products = new ProductService();
        $this->categories = new CategoryService();
    }

    public function index(): void
    {
        $filters = $this->filters();
        $page = max(1, (int) ($_GET['page'] ?? 1));
        $result = $this->products->paginate($filters, $page);

        View::render('products/index', [
            'title' => 'Produtos',
            'products' => $result['items'],
            'total' => $result['total'],
            'page' => $result['page'],
            'pages' => $result['pages'],
            'filters' => $filters,
            'categories' => $this->categories->all(),
        ]);
    }

    public function show(string $id): void
    {
        $product = $this->findOr404((int) $id);

        View::render('products/show', [
            'title' => $product['name'],
            'product' => $product,
            'lots' => ProductLot::forProduct((int) $product['id']),
            'movements' => StockMovement::forProduct((int) $product['id']),
        ]);
    }

    public function create(): void
    {
        $this->renderCreate($this->emptyForm());
    }

    public function store(): void
    {
        $this->checkCsrf('/produtos/novo');

        $form = $this->formData();
        $errors = $this->validate($form);

        if ($errors !== []) {
            $this->renderCreate($form, $errors);
            return;
        }

        try {
            $id = $this->products->create($this->toData($form));
        } catch (DomainException $exception) {
            [$fieldErrors, $generalError] = $this->mapException($exception);
            $this->renderCreate($form, $fieldErrors, $generalError);
            return;
        }

        Flash::success('Produto criado com sucesso.');
        Response::redirect('/produtos/' . $id);
    }

    public function edit(string $id): void
    {
        $product = $this->findOr404((int) $id);

        $this->renderEdit($product, $this->formFromProduct($product));
    }

    public function update(string $id): void
    {
        $this->checkCsrf('/produtos');
        $product = $this->findOr404((int) $id);

        $form = $this->formData();
        $errors = $this->validate($form);

        if ($errors !== []) {
            $this->renderEdit($product, $form, $errors);
            return;
        }

        try {
            $this->products->update((int) $product['id'], $this->toData($form));
        } catch (DomainException $exception) {
            [$fieldErrors, $generalError] = $this->mapException($exception);
            $this->renderEdit($product, $form, $fieldErrors, $generalError);
            return;
        }

        Flash::success('Produto atualizado com sucesso.');
        Response::redirect('/produtos/' . (int) $product['id']);
    }

    public function deactivate(string $id): void
    {
        $this->checkCsrf('/produtos');
        $product = $this->findOr404((int) $id);

        $this->products->setActive((int) $product['id'], false);

        Flash::success('Produto desativado.');
        Response::redirect('/produtos');
    }

    public function activate(string $id): void
    {
        $this->checkCsrf('/produtos');
        $product = $this->findOr404((int) $id);

        $this->products->setActive((int) $product['id'], true);

        Flash::success('Produto ativado.');
        Response::redirect('/produtos');
    }

    private function filters(): array
    {
        $category = $_GET['categoria'] ?? null;
        $categoryId = is_scalar($category) && (int) $category > 0 ? (int) $category : null;

        $status = $_GET['status'] ?? 'active';
        if (!is_string($status) || !in_array($status, ['active', 'inactive', 'all'], true)) {
            $status = 'active';
        }

        return [
            'q' => Request::string('q'),
            'category_id' => $categoryId,
            'status' => $status,
            'low_stock' => isset($_GET['estoque_baixo']) && $_GET['estoque_baixo'] === '1',
        ];
    }

    private function validate(array $form): array
    {
        $validator = (new Validator())
            ->required('name', $form['name'], 'Informe o nome.')
            ->minLength('name', $form['name'], 3, 'O nome deve ter pelo menos 3 caracteres.')
            ->maxLength('name', $form['name'], 160, 'O nome deve ter no máximo 160 caracteres.')
            ->maxLength('sku', $form['sku'], 60, 'O SKU deve ter no máximo 60 caracteres.')
            ->maxLength('barcode', $form['barcode'], 60, 'O código de barras deve ter no máximo 60 caracteres.')
            ->in('unit', $form['unit'], Product::UNITS, 'Selecione uma unidade válida.')
            ->decimal('sale_price', $form['sale_price'], 2, 'Informe um preço de venda válido.')
            ->decimal('cost_price', $form['cost_price'], 2, 'Informe um custo válido.')
            ->decimal('min_stock', $form['min_stock'], 3, 'Informe um estoque mínimo válido.');

        $category = (string) ($form['category_id'] ?? '');
        if ($category !== '' && (!ctype_digit($category) || (int) $category <= 0)) {
            $validator->add('category_id', 'Selecione uma categoria válida.');
        }

        return $validator->errors();
    }

    private function formData(): array
    {
        $category = $_POST['category_id'] ?? '';

        return [
            'name' => Request::string('name'),
            'category_id' => is_scalar($category) ? (string) $category : '',
            'sku' => Request::string('sku'),
            'barcode' => Request::string('barcode'),
            'unit' => Request::string('unit'),
            'sale_price' => Request::string('sale_price'),
            'cost_price' => Request::string('cost_price'),
            'min_stock' => Request::string('min_stock'),
            'controls_lots' => (string) ($_POST['controls_lots'] ?? '0') === '1',
        ];
    }

    private function emptyForm(): array
    {
        return [
            'name' => '',
            'category_id' => '',
            'sku' => '',
            'barcode' => '',
            'unit' => 'un',
            'sale_price' => '0.00',
            'cost_price' => '0.00',
            'min_stock' => '0.000',
            'controls_lots' => false,
        ];
    }

    private function formFromProduct(array $product): array
    {
        return [
            'name' => (string) $product['name'],
            'category_id' => $product['category_id'] !== null ? (string) $product['category_id'] : '',
            'sku' => (string) ($product['sku'] ?? ''),
            'barcode' => (string) ($product['barcode'] ?? ''),
            'unit' => (string) $product['unit'],
            'sale_price' => number_format((float) $product['sale_price'], 2, '.', ''),
            'cost_price' => number_format((float) $product['cost_price'], 2, '.', ''),
            'min_stock' => number_format((float) $product['min_stock'], 3, '.', ''),
            'controls_lots' => (int) $product['controls_lots'] === 1,
        ];
    }

    private function toData(array $form): array
    {
        return [
            'category_id' => $form['category_id'] === '' ? null : (int) $form['category_id'],
            'name' => $form['name'],
            'sku' => $form['sku'],
            'barcode' => $form['barcode'],
            'unit' => $form['unit'],
            'sale_price' => $this->normalizeDecimal($form['sale_price'], 2),
            'cost_price' => $this->normalizeDecimal($form['cost_price'], 2),
            'min_stock' => $this->normalizeDecimal($form['min_stock'], 3),
            'controls_lots' => $form['controls_lots'] ? 1 : 0,
        ];
    }

    private function normalizeDecimal(string $value, int $scale): string
    {
        $normalized = str_replace(',', '.', trim($value));

        return number_format((float) $normalized, $scale, '.', '');
    }

    private function mapException(DomainException $exception): array
    {
        $field = match ($exception->getCode()) {
            ProductService::SKU_TAKEN => 'sku',
            ProductService::BARCODE_TAKEN => 'barcode',
            ProductService::CATEGORY_INVALID => 'category_id',
            default => null,
        };

        if ($field !== null) {
            return [[$field => $exception->getMessage()], null];
        }

        return [[], $exception->getMessage()];
    }

    private function renderCreate(array $form, array $errors = [], ?string $generalError = null): void
    {
        View::render('products/create', [
            'title' => 'Novo produto',
            'form' => $form,
            'errors' => $errors,
            'generalError' => $generalError,
            'isEdit' => false,
            'action' => '/produtos',
            'categories' => $this->categories->allActive(),
            'hasMovements' => false,
        ]);
    }

    private function renderEdit(array $product, array $form, array $errors = [], ?string $generalError = null): void
    {
        View::render('products/edit', [
            'title' => 'Editar produto',
            'product' => $product,
            'form' => $form,
            'errors' => $errors,
            'generalError' => $generalError,
            'isEdit' => true,
            'action' => '/produtos/' . (int) $product['id'],
            'categories' => $this->categories->all(),
            'hasMovements' => $this->products->hasMovements((int) $product['id']),
        ]);
    }

    private function findOr404(int $id): array
    {
        try {
            return $this->products->find($id);
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

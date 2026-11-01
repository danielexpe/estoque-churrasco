<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Csrf;
use App\Core\Flash;
use App\Core\Request;
use App\Core\Response;
use App\Core\Validator;
use App\Core\View;
use App\Services\CategoryService;
use DomainException;

final class CategoryController
{
    private CategoryService $categories;

    public function __construct()
    {
        $this->categories = new CategoryService();
    }

    public function index(): void
    {
        View::render('categories/index', [
            'title' => 'Categorias',
            'categories' => $this->categories->all(),
        ]);
    }

    public function create(): void
    {
        $this->renderCreate([
            'name' => '',
            'description' => '',
            'default_controls_lots' => false,
        ]);
    }

    public function store(): void
    {
        $this->checkCsrf('/categorias/nova');

        $form = $this->formData();
        $errors = $this->validate($form);

        if ($errors !== []) {
            $this->renderCreate($form, $errors);
            return;
        }

        try {
            $this->categories->create(
                $form['name'],
                $form['description'],
                $form['default_controls_lots']
            );
        } catch (DomainException $exception) {
            [$fieldErrors, $generalError] = $this->mapException($exception);
            $this->renderCreate($form, $fieldErrors, $generalError);
            return;
        }

        Flash::success('Categoria criada com sucesso.');
        Response::redirect('/categorias');
    }

    public function edit(string $id): void
    {
        $category = $this->findOr404((int) $id);

        $this->renderEdit($category, [
            'name' => (string) $category['name'],
            'description' => (string) ($category['description'] ?? ''),
            'default_controls_lots' => (int) $category['default_controls_lots'] === 1,
        ]);
    }

    public function update(string $id): void
    {
        $this->checkCsrf('/categorias');
        $category = $this->findOr404((int) $id);

        $form = $this->formData();
        $errors = $this->validate($form);

        if ($errors !== []) {
            $this->renderEdit($category, $form, $errors);
            return;
        }

        try {
            $this->categories->update(
                (int) $category['id'],
                $form['name'],
                $form['description'],
                $form['default_controls_lots']
            );
        } catch (DomainException $exception) {
            [$fieldErrors, $generalError] = $this->mapException($exception);
            $this->renderEdit($category, $form, $fieldErrors, $generalError);
            return;
        }

        Flash::success('Categoria atualizada com sucesso.');
        Response::redirect('/categorias');
    }

    public function deactivate(string $id): void
    {
        $this->checkCsrf('/categorias');
        $category = $this->findOr404((int) $id);

        $this->categories->setActive((int) $category['id'], false);

        Flash::success('Categoria desativada.');
        Response::redirect('/categorias');
    }

    public function activate(string $id): void
    {
        $this->checkCsrf('/categorias');
        $category = $this->findOr404((int) $id);

        $this->categories->setActive((int) $category['id'], true);

        Flash::success('Categoria ativada.');
        Response::redirect('/categorias');
    }

    private function validate(array $form): array
    {
        return (new Validator())
            ->required('name', $form['name'], 'Informe o nome.')
            ->minLength('name', $form['name'], 2, 'O nome deve ter pelo menos 2 caracteres.')
            ->maxLength('name', $form['name'], 80, 'O nome deve ter no máximo 80 caracteres.')
            ->maxLength('description', $form['description'], 255, 'A descrição deve ter no máximo 255 caracteres.')
            ->errors();
    }

    private function formData(): array
    {
        return [
            'name' => Request::string('name'),
            'description' => Request::string('description'),
            'default_controls_lots' => ($_POST['default_controls_lots'] ?? '0') === '1',
        ];
    }

    private function mapException(DomainException $exception): array
    {
        if ($exception->getCode() === CategoryService::NAME_TAKEN) {
            return [['name' => $exception->getMessage()], null];
        }

        return [[], $exception->getMessage()];
    }

    private function renderCreate(array $form, array $errors = [], ?string $generalError = null): void
    {
        View::render('categories/create', [
            'title' => 'Nova categoria',
            'form' => $form,
            'errors' => $errors,
            'generalError' => $generalError,
            'isEdit' => false,
            'action' => '/categorias',
        ]);
    }

    private function renderEdit(array $category, array $form, array $errors = [], ?string $generalError = null): void
    {
        View::render('categories/edit', [
            'title' => 'Editar categoria',
            'category' => $category,
            'form' => $form,
            'errors' => $errors,
            'generalError' => $generalError,
            'isEdit' => true,
            'action' => '/categorias/' . (int) $category['id'],
        ]);
    }

    private function findOr404(int $id): array
    {
        try {
            return $this->categories->find($id);
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

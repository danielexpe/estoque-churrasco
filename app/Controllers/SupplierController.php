<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Csrf;
use App\Core\Flash;
use App\Core\Request;
use App\Core\Response;
use App\Core\Validator;
use App\Core\View;
use App\Services\SupplierService;
use DomainException;

final class SupplierController
{
    private SupplierService $suppliers;

    public function __construct()
    {
        $this->suppliers = new SupplierService();
    }

    public function index(): void
    {
        $search = Request::string('busca');

        View::render('suppliers/index', [
            'title' => 'Fornecedores',
            'suppliers' => $this->suppliers->all($search !== '' ? $search : null),
            'search' => $search,
        ]);
    }

    public function create(): void
    {
        $this->renderCreate([
            'name' => '',
            'document' => '',
            'phone' => '',
            'email' => '',
            'notes' => '',
        ]);
    }

    public function store(): void
    {
        $this->checkCsrf('/fornecedores/novo');

        $form = $this->formData();
        $errors = $this->validate($form);

        if ($errors !== []) {
            $this->renderCreate($form, $errors);
            return;
        }

        $this->suppliers->create($form);

        Flash::success('Fornecedor criado com sucesso.');
        Response::redirect('/fornecedores');
    }

    public function edit(string $id): void
    {
        $supplier = $this->findOr404((int) $id);

        $this->renderEdit($supplier, [
            'name' => (string) $supplier['name'],
            'document' => (string) ($supplier['document'] ?? ''),
            'phone' => (string) ($supplier['phone'] ?? ''),
            'email' => (string) ($supplier['email'] ?? ''),
            'notes' => (string) ($supplier['notes'] ?? ''),
        ]);
    }

    public function update(string $id): void
    {
        $this->checkCsrf('/fornecedores');
        $supplier = $this->findOr404((int) $id);

        $form = $this->formData();
        $errors = $this->validate($form);

        if ($errors !== []) {
            $this->renderEdit($supplier, $form, $errors);
            return;
        }

        $this->suppliers->update((int) $supplier['id'], $form);

        Flash::success('Fornecedor atualizado com sucesso.');
        Response::redirect('/fornecedores');
    }

    public function deactivate(string $id): void
    {
        $this->checkCsrf('/fornecedores');
        $supplier = $this->findOr404((int) $id);

        $this->suppliers->setActive((int) $supplier['id'], false);

        Flash::success('Fornecedor desativado.');
        Response::redirect('/fornecedores');
    }

    public function activate(string $id): void
    {
        $this->checkCsrf('/fornecedores');
        $supplier = $this->findOr404((int) $id);

        $this->suppliers->setActive((int) $supplier['id'], true);

        Flash::success('Fornecedor ativado.');
        Response::redirect('/fornecedores');
    }

    private function validate(array $form): array
    {
        return (new Validator())
            ->required('name', $form['name'], 'Informe o nome.')
            ->minLength('name', $form['name'], 2, 'O nome deve ter pelo menos 2 caracteres.')
            ->maxLength('name', $form['name'], 160, 'O nome deve ter no máximo 160 caracteres.')
            ->maxLength('document', $form['document'], 20, 'O documento deve ter no máximo 20 caracteres.')
            ->maxLength('phone', $form['phone'], 30, 'O telefone deve ter no máximo 30 caracteres.')
            ->maxLength('email', $form['email'], 160, 'O e-mail deve ter no máximo 160 caracteres.')
            ->email('email', $form['email'], 'Informe um e-mail válido.')
            ->maxLength('notes', $form['notes'], 500, 'As observações devem ter no máximo 500 caracteres.')
            ->errors();
    }

    private function formData(): array
    {
        return [
            'name' => Request::string('name'),
            'document' => Request::string('document'),
            'phone' => Request::string('phone'),
            'email' => Request::string('email'),
            'notes' => Request::string('notes'),
        ];
    }

    private function renderCreate(array $form, array $errors = []): void
    {
        View::render('suppliers/create', [
            'title' => 'Novo fornecedor',
            'form' => $form,
            'errors' => $errors,
            'isEdit' => false,
            'action' => '/fornecedores',
        ]);
    }

    private function renderEdit(array $supplier, array $form, array $errors = []): void
    {
        View::render('suppliers/edit', [
            'title' => 'Editar fornecedor',
            'supplier' => $supplier,
            'form' => $form,
            'errors' => $errors,
            'isEdit' => true,
            'action' => '/fornecedores/' . (int) $supplier['id'],
        ]);
    }

    private function findOr404(int $id): array
    {
        try {
            return $this->suppliers->find($id);
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

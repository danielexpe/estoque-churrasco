<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Csrf;
use App\Core\Flash;
use App\Core\Request;
use App\Core\Response;
use App\Core\Validator;
use App\Core\View;
use App\Services\UserService;
use DomainException;

final class UserController
{
    private UserService $users;

    public function __construct()
    {
        $this->users = new UserService();
    }

    public function index(): void
    {
        View::render('users/index', [
            'title' => 'Usuários',
            'users' => $this->users->all(),
        ]);
    }

    public function create(): void
    {
        $this->renderCreate([
            'name' => '',
            'email' => '',
            'role' => 'operador',
        ]);
    }

    public function store(): void
    {
        $this->checkCsrf('/usuarios/novo');

        $form = $this->formData();
        $password = $this->stringInput('password');
        $confirmation = $this->stringInput('password_confirmation');

        $validator = (new Validator())
            ->required('name', $form['name'], 'Informe o nome.')
            ->minLength('name', $form['name'], 3, 'O nome deve ter pelo menos 3 caracteres.')
            ->maxLength('name', $form['name'], 120, 'O nome deve ter no máximo 120 caracteres.')
            ->required('email', $form['email'], 'Informe o e-mail.')
            ->email('email', $form['email'], 'Informe um e-mail válido.')
            ->maxLength('email', $form['email'], 160, 'O e-mail deve ter no máximo 160 caracteres.')
            ->required('role', $form['role'], 'Selecione o perfil.')
            ->in('role', $form['role'], ['admin', 'operador'], 'Perfil inválido.')
            ->required('password', $password, 'Informe a senha.')
            ->minLength('password', $password, 8, 'A senha deve ter pelo menos 8 caracteres.')
            ->confirmed('password', $password, $confirmation, 'A confirmação da senha não confere.');

        if ($validator->fails()) {
            $this->renderCreate($form, $validator->errors());
            return;
        }

        try {
            $this->users->create($form['name'], $form['email'], $form['role'], $password);
        } catch (DomainException $exception) {
            $errors = $exception->getCode() === UserService::EMAIL_TAKEN
                ? ['email' => $exception->getMessage()]
                : [];

            $generalError = $errors === [] ? $exception->getMessage() : null;

            $this->renderCreate($form, $errors, $generalError);
            return;
        }

        Flash::success('Usuário criado com sucesso.');
        Response::redirect('/usuarios');
    }

    public function edit(string $id): void
    {
        $user = $this->findOr404((int) $id);

        View::render('users/edit', [
            'title' => 'Editar usuário',
            'user' => $user,
            'form' => [
                'name' => (string) $user['name'],
                'email' => (string) $user['email'],
                'role' => (string) $user['role'],
            ],
            'errors' => [],
            'generalError' => null,
            'isEdit' => true,
            'action' => '/usuarios/' . (int) $user['id'],
        ]);
    }

    public function update(string $id): void
    {
        $this->checkCsrf('/usuarios');
        $user = $this->findOr404((int) $id);

        $form = $this->formData();
        $password = $this->stringInput('password');
        $confirmation = $this->stringInput('password_confirmation');

        $validator = (new Validator())
            ->required('name', $form['name'], 'Informe o nome.')
            ->minLength('name', $form['name'], 3, 'O nome deve ter pelo menos 3 caracteres.')
            ->maxLength('name', $form['name'], 120, 'O nome deve ter no máximo 120 caracteres.')
            ->required('email', $form['email'], 'Informe o e-mail.')
            ->email('email', $form['email'], 'Informe um e-mail válido.')
            ->maxLength('email', $form['email'], 160, 'O e-mail deve ter no máximo 160 caracteres.')
            ->required('role', $form['role'], 'Selecione o perfil.')
            ->in('role', $form['role'], ['admin', 'operador'], 'Perfil inválido.');

        if ($password !== '') {
            $validator
                ->minLength('password', $password, 8, 'A senha deve ter pelo menos 8 caracteres.')
                ->confirmed('password', $password, $confirmation, 'A confirmação da senha não confere.');
        }

        if ($validator->fails()) {
            $this->renderEdit($user, $form, $validator->errors());
            return;
        }

        try {
            $this->users->update(
                (int) $user['id'],
                $form['name'],
                $form['email'],
                $form['role'],
                $password !== '' ? $password : null
            );
        } catch (DomainException $exception) {
            $errors = $exception->getCode() === UserService::EMAIL_TAKEN
                ? ['email' => $exception->getMessage()]
                : [];

            $generalError = $exception->getCode() === UserService::EMAIL_TAKEN
                ? null
                : $exception->getMessage();

            $this->renderEdit($user, $form, $errors, $generalError);
            return;
        }

        Flash::success('Usuário atualizado com sucesso.');
        Response::redirect('/usuarios');
    }

    public function deactivate(string $id): void
    {
        $this->checkCsrf('/usuarios');
        $user = $this->findOr404((int) $id);

        try {
            $this->users->setActive((int) $user['id'], false);
        } catch (DomainException $exception) {
            Flash::error($exception->getMessage());
            Response::redirect('/usuarios');
        }

        Flash::success('Usuário desativado.');
        Response::redirect('/usuarios');
    }

    public function activate(string $id): void
    {
        $this->checkCsrf('/usuarios');
        $user = $this->findOr404((int) $id);

        $this->users->setActive((int) $user['id'], true);

        Flash::success('Usuário ativado.');
        Response::redirect('/usuarios');
    }

    private function renderCreate(array $form, array $errors = [], ?string $generalError = null): void
    {
        View::render('users/create', [
            'title' => 'Novo usuário',
            'form' => $form,
            'errors' => $errors,
            'generalError' => $generalError,
            'isEdit' => false,
            'action' => '/usuarios',
        ]);
    }

    private function renderEdit(array $user, array $form, array $errors = [], ?string $generalError = null): void
    {
        View::render('users/edit', [
            'title' => 'Editar usuário',
            'user' => $user,
            'form' => $form,
            'errors' => $errors,
            'generalError' => $generalError,
            'isEdit' => true,
            'action' => '/usuarios/' . (int) $user['id'],
        ]);
    }

    private function formData(): array
    {
        return [
            'name' => Request::string('name'),
            'email' => Request::string('email'),
            'role' => Request::string('role'),
        ];
    }

    private function findOr404(int $id): array
    {
        try {
            return $this->users->find($id);
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

    private function stringInput(string $key): string
    {
        $value = $_POST[$key] ?? '';

        return is_string($value) ? $value : '';
    }
}

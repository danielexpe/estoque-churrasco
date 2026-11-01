<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Csrf;
use App\Core\Flash;
use App\Core\Response;
use App\Core\Validator;
use App\Core\View;

final class AuthController
{
    public function showLogin(): void
    {
        View::render('auth/login', ['title' => 'Entrar'], 'layouts/auth');

        unset($_SESSION['_old']);
    }

    public function login(): void
    {
        if (!Csrf::validate($_POST['_csrf'] ?? null)) {
            Flash::error('Sessão expirada. Tente novamente.');
            Response::redirect('/login');
        }

        $email = isset($_POST['email']) && is_string($_POST['email'])
            ? trim($_POST['email'])
            : '';

        $password = isset($_POST['password']) && is_string($_POST['password'])
            ? $_POST['password']
            : '';

        $validator = (new Validator())
            ->required('email', $email, 'Informe o e-mail.')
            ->email('email', $email, 'Informe um e-mail válido.')
            ->required('password', $password, 'Informe a senha.');

        if ($validator->fails()) {
            $_SESSION['_old'] = ['email' => $email];
            Flash::error('Verifique os campos destacados.');
            Response::redirect('/login');
        }

        if (!Auth::attempt($email, $password)) {
            $_SESSION['_old'] = ['email' => $email];
            Flash::error('E-mail ou senha inválidos.');
            Response::redirect('/login');
        }

        unset($_SESSION['_old']);
        Flash::success('Bem-vindo ao Estoque Churrasco.');
        Response::redirect('/');
    }

    public function logout(): void
    {
        if (!Csrf::validate($_POST['_csrf'] ?? null)) {
            Flash::error('Sessão expirada. Tente novamente.');
            Response::redirect('/');
        }

        Auth::logout();
        Flash::success('Sessão encerrada.');
        Response::redirect('/login');
    }
}

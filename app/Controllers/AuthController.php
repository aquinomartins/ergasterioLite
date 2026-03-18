<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Session;
use App\Requests\LoginRequest;
use App\Requests\RegisterRequest;
use App\Requests\UpdateProfileRequest;
use App\Services\AuthService;

final class AuthController extends Controller
{
    private AuthService $service;

    public function __construct()
    {
        $this->service = new AuthService();
    }

    public function showRegister(): void
    {
        $this->view('auth.register', [
            'pageTitle' => 'Criar conta',
            'old' => Session::get('old', []),
            'errors' => Session::get('errors', []),
        ]);
        Session::forget('old');
        Session::forget('errors');
    }

    public function register(): void
    {
        [$errors, $data] = (new RegisterRequest())->validate($_POST);

        if ($errors === []) {
            $errors = $this->service->register($data);
        }

        if ($errors !== []) {
            Session::set('old', $data);
            Session::set('errors', $errors);
            Session::flash('error', 'Revise os dados do cadastro.');
            $this->redirectTo('/register');
        }

        Session::flash('success', 'Cadastro realizado com sucesso.');
        $this->redirectTo('/dashboard');
    }

    public function showLogin(): void
    {
        $this->view('auth.login', [
            'pageTitle' => 'Entrar',
            'old' => Session::get('old', []),
            'errors' => Session::get('errors', []),
        ]);
        Session::forget('old');
        Session::forget('errors');
    }

    public function login(): void
    {
        [$errors, $data] = (new LoginRequest())->validate($_POST);

        if ($errors === [] && ! $this->service->login($data)) {
            $errors['email'][] = 'Credenciais inválidas.';
        }

        if ($errors !== []) {
            Session::set('old', ['email' => $data['email']]);
            Session::set('errors', $errors);
            Session::flash('error', 'Não foi possível autenticar.');
            $this->redirectTo('/login');
        }

        Session::flash('success', 'Login realizado com sucesso.');
        $this->redirectTo('/dashboard');
    }

    public function logout(): void
    {
        $config = require BASE_PATH . '/app/Config/config.php';
        Auth::logout();
        Session::start($config['session']);
        Session::flash('success', 'Sessão encerrada.');
        $this->redirectTo('/');
    }

    public function dashboard(): void
    {
        $this->view('dashboard.index', [
            'pageTitle' => 'Dashboard',
            'user' => Auth::user(),
        ]);
    }

    public function editProfile(): void
    {
        $user = Auth::user();
        $this->view('profile.edit', [
            'pageTitle' => 'Editar perfil',
            'user' => $user,
            'old' => Session::get('old', []),
            'errors' => Session::get('errors', []),
        ]);
        Session::forget('old');
        Session::forget('errors');
    }

    public function updateProfile(): void
    {
        $userId = Auth::id();

        if ($userId === null) {
            $this->redirectTo('/login');
        }

        [$errors, $data] = (new UpdateProfileRequest())->validate($_POST);

        if ($errors === []) {
            $errors = $this->service->updateProfile($userId, $data);
        }

        if ($errors !== []) {
            Session::set('old', $data);
            Session::set('errors', $errors);
            Session::flash('error', 'Não foi possível salvar o perfil.');
            $this->redirectTo('/profile/edit');
        }

        Session::flash('success', 'Perfil atualizado com sucesso.');
        $this->redirectTo('/dashboard');
    }
}

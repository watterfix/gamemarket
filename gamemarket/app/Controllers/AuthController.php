<?php
namespace App\Controllers;

use Core\Controller;
use Core\Session;
use App\Models\User;

class AuthController extends Controller
{
    public function showLogin(): void
    {
        $success = isset($_GET['registered'])
            ? 'Регистрация прошла успешно! Теперь войдите в аккаунт.'
            : null;

        $this->view('login', ['success' => $success]);
    }

    public function login(): void
    {
        $login    = trim($_POST['login'] ?? '');
        $password = $_POST['password'] ?? '';

        if (User::verify($login, $password)) {
            Session::login($login);
            $this->redirect('index.php?r=/');
        }

        $this->view('login', ['error' => 'Неверный логин или пароль']);
    }

    public function showRegister(): void
    {
        $this->view('register');
    }

    public function register(): void
    {
        $login    = trim($_POST['login'] ?? '');
        $password = $_POST['password'] ?? '';

        if (mb_strlen($login) < 3 || mb_strlen($password) < 4) {
            $this->view('register', ['error' => 'Логин — от 3 символов, пароль — от 4 символов']);
            return;
        }

        if (!User::create($login, $password)) {
            $this->view('register', ['error' => 'Пользователь уже существует']);
            return;
        }

        $this->redirect('index.php?r=/login&registered=1');
    }

    public function logout(): void
    {
        Session::logout();
        $this->redirect('index.php?r=/');
    }
}

<?php
namespace App\Controllers;

use Core\Controller;
use Core\Session;
use App\Models\User;

class HomeController extends Controller
{
    public function index(): void
    {
        $login   = Session::user();
        $cart    = [];
        $library = [];
        $balance = 0;

        if ($login) {
            $user = User::find($login);
            if ($user) {
                $cart    = $user['cart'];
                $library = $user['library'];
                $balance = $user['balance'];
            }
        }

        $this->view('home', [
            'user'    => $login,
            'balance' => $balance,
            'games'   => User::catalog(),
            'cart'    => $cart,
            'library' => $library,
        ]);
    }
}

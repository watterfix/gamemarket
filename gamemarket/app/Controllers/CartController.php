<?php
namespace App\Controllers;

use Core\Controller;
use Core\Session;
use App\Models\User;

class CartController extends Controller
{
    public function index(): void
    {
        Session::requireLogin();

        $user  = User::find(Session::user());
        $items = [];
        $total = 0;

        foreach ($user['cart'] as $id) {
            $game = User::findGame($id);
            if ($game) {
                $items[] = $game;
                $total  += $game['price'];
            }
        }

        $this->view('cart', [
            'user'    => Session::user(),
            'balance' => $user['balance'],
            'items'   => $items,
            'total'   => $total,
        ]);
    }

    public function add(): void
    {
        Session::requireLogin();

        $gameId = $_POST['game'] ?? '';
        if ($gameId === '') {
            $this->json(['ok' => false, 'message' => 'Не указана игра']);
            return;
        }

        $this->json(User::addToCart(Session::user(), $gameId));
    }

    public function remove(): void
    {
        Session::requireLogin();

        $gameId = $_POST['game'] ?? '';
        $this->json(User::removeFromCart(Session::user(), $gameId));
    }

    public function checkout(): void
    {
        Session::requireLogin();
        $this->json(User::checkout(Session::user()));
    }
}

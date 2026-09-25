<?php
namespace App\Controllers;

use Core\Controller;
use Core\Session;
use App\Models\User;

class BalanceController extends Controller
{
    public function topUp(): void
    {
        Session::requireLogin();

        $amount = (int)($_POST['amount'] ?? 0);
        $card = [
            'card_number' => $_POST['card_number'] ?? '',
            'card_name'   => $_POST['card_name'] ?? '',
            'card_expiry' => $_POST['card_expiry'] ?? '',
            'card_cvv'    => $_POST['card_cvv'] ?? '',
        ];

        $this->json(User::topUp(Session::user(), $amount, $card));
    }
}

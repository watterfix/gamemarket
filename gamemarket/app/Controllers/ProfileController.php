<?php
namespace App\Controllers;

use Core\Controller;
use Core\Session;
use App\Models\User;

class ProfileController extends Controller
{
    /**
     * Единая страница профиля: аватар, история покупок, библиотека, смена пароля.
     */
    public function profile(): void
    {
        Session::requireLogin();

        $user  = User::find(Session::user());
        $games = [];

        foreach ($user['library'] as $id) {
            $game = User::findGame($id);
            if ($game) $games[] = $game;
        }

        $this->view('profile', [
            'user'      => Session::user(),
            'balance'   => $user['balance'],
            'avatar'    => $user['avatar'],
            'role'      => $user['role'],
            'games'     => $games,
            'keys'      => $user['keys'] ?? [],
            'purchases' => array_reverse($user['purchases'] ?? []),
        ]);
    }

    /**
     * Старый маршрут /library — для обратной совместимости ведём в профиль.
     */
    public function library(): void
    {
        Session::requireLogin();
        $this->redirect('index.php?r=/profile');
    }

    public function updateAvatar(): void
    {
        Session::requireLogin();

        // Либо готовая иконка (emoji), либо загруженное изображение (data URI)
        $avatar = $_POST['avatar_data'] ?? ($_POST['avatar'] ?? '');
        $avatar = trim((string)$avatar);

        $this->json(User::setAvatar(Session::user(), $avatar));
    }

    public function changePassword(): void
    {
        Session::requireLogin();

        $old = (string)($_POST['old_password'] ?? '');
        $new = (string)($_POST['new_password'] ?? '');

        $this->json(User::changePassword(Session::user(), $old, $new));
    }
}

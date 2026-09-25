<?php
namespace App\Controllers;

use Core\Controller;
use Core\Session;
use App\Models\User;
use App\Models\Game;

class AdminController extends Controller
{
    private function guard(): void
    {
        Session::requireLogin();
        if (!User::isAdmin(Session::user())) {
            http_response_code(403);
            die('Доступ только для администраторов');
        }
    }

    public function index(): void
    {
        $this->guard();

        $me = User::find(Session::user());

        $this->view('admin', [
            'user'    => Session::user(),
            'balance' => $me['balance'] ?? 0,
            'games'   => Game::all(),
        ]);
    }

    public function create(): void
    {
        $this->guard();
        $this->json(Game::create($this->withUploads($_POST)));
    }

    public function update(): void
    {
        $this->guard();

        $id = $_POST['id'] ?? '';
        if ($id === '') {
            $this->json(['ok' => false, 'message' => 'Не указан ID игры']);
            return;
        }

        $this->json(Game::update($id, $this->withUploads($_POST)));
    }

    /**
     * Достаёт загруженные PNG/JPG-файлы из $_FILES (обложка + скриншоты)
     * и подмешивает их в массив данных формы, чтобы Game::create()/update()
     * могли сохранить их вместе с остальными полями.
     */
    private function withUploads(array $data): array
    {
        $data['uploaded_screenshots'] = Game::storeUploadedImages($_FILES['screenshot_files'] ?? null);

        $cover = Game::storeUploadedImages($_FILES['cover_file'] ?? null, 1);
        if ($cover) {
            $data['uploaded_cover'] = $cover[0];
        }

        return $data;
    }

    public function delete(): void
    {
        $this->guard();

        $id = $_POST['id'] ?? '';
        if ($id === '') {
            $this->json(['ok' => false, 'message' => 'Не указан ID игры']);
            return;
        }

        $this->json(Game::delete($id));
    }

    // ---------- Пользователи ----------

    public function users(): void
    {
        $this->guard();
        $this->json(['ok' => true, 'users' => User::publicList()]);
    }

    public function setRole(): void
    {
        $this->guard();

        $login = trim((string)($_POST['login'] ?? ''));
        $role  = trim((string)($_POST['role'] ?? ''));

        if ($login === '') {
            $this->json(['ok' => false, 'message' => 'Не указан пользователь']);
            return;
        }

        $this->json(User::setRole($login, $role, Session::user()));
    }

    // ---------- Статистика продаж ----------

    public function stats(): void
    {
        $this->guard();
        $this->json(['ok' => true] + User::salesStats());
    }
}

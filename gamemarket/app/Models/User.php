<?php
namespace App\Models;

use App\Support\CardValidator;
use App\Support\KeyGenerator;

class User
{
    private static string $file = __DIR__ . '/../../storage/users.json';
    private const START_BALANCE = 1000;

    // ---------- Каталог игр ----------

    public static function catalog(): array
    {
        return Game::all();
    }

    public static function findGame(string $id): ?array
    {
        return Game::find($id);
    }

    // ---------- Базовые операции с файлом ----------

    private static function rawAll(): array
    {
        if (!file_exists(self::$file)) return [];
        return json_decode(file_get_contents(self::$file), true) ?: [];
    }

    private static function normalize(array $u): array
    {
        $validIds = array_column(self::catalog(), 'id');

        $u['balance']   = $u['balance'] ?? self::START_BALANCE;
        $u['role']      = $u['role'] ?? 'user';
        $u['avatar']    = $u['avatar'] ?? null;
        $u['purchases'] = $u['purchases'] ?? [];
        $u['library'] = array_values(array_unique(array_intersect($u['library'] ?? [], $validIds)));
        $u['cart']    = array_values(array_unique(array_intersect($u['cart'] ?? [], $validIds)));

        // игра не может быть одновременно в корзине и уже купленной
        $u['cart'] = array_values(array_diff($u['cart'], $u['library']));

        // ключи оставляем только для игр, которые реально есть в библиотеке
        $keys = $u['keys'] ?? [];
        $u['keys'] = array_intersect_key($keys, array_flip($u['library']));

        return $u;
    }

    public static function all(): array
    {
        return array_map([self::class, 'normalize'], self::rawAll());
    }

    public static function save(array $users): void
    {
        file_put_contents(
            self::$file,
            json_encode($users, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
        );
    }

    public static function find(string $login): ?array
    {
        foreach (self::all() as $u) {
            if ($u['login'] === $login) return $u;
        }
        return null;
    }

    public static function isAdmin(?string $login): bool
    {
        if (!$login) return false;
        $u = self::find($login);
        return $u !== null && ($u['role'] ?? 'user') === 'admin';
    }

    // ---------- Регистрация / вход ----------

    public static function create(string $login, string $password): bool
    {
        if ($login === '' || $password === '') return false;
        if (self::find($login)) return false;

        $users   = self::rawAll();
        $users[] = [
            'login'    => $login,
            'password' => password_hash($password, PASSWORD_DEFAULT),
            'role'     => 'user',
            'cart'     => [],
            'library'  => [],
            'keys'     => [],
            'balance'  => self::START_BALANCE,
        ];
        self::save($users);
        return true;
    }

    public static function verify(string $login, string $password): bool
    {
        $u = self::find($login);
        return $u && password_verify($password, $u['password']);
    }

    // ---------- Общий помощник для изменения данных одного юзера ----------

    private static function mutate(string $login, callable $fn): array
    {
        $raw    = self::rawAll();
        $result = ['ok' => false, 'message' => 'Пользователь не найден'];

        foreach ($raw as $i => $u) {
            if ($u['login'] === $login) {
                $u      = self::normalize($u);
                $result = $fn($u);
                $raw[$i] = $u;
                self::save($raw);
                return $result;
            }
        }

        return $result;
    }

    // ---------- Корзина / покупки / баланс ----------

    public static function addToCart(string $login, string $gameId): array
    {
        return self::mutate($login, function (array &$u) use ($gameId) {
            $game = self::findGame($gameId);
            if (!$game) {
                return ['ok' => false, 'message' => 'Игра не найдена'];
            }
            if (in_array($gameId, $u['library'], true)) {
                return ['ok' => false, 'message' => 'Игра уже куплена'];
            }
            if (in_array($gameId, $u['cart'], true)) {
                return ['ok' => false, 'message' => 'Игра уже в корзине'];
            }
            if (!Game::hasStock($gameId)) {
                return ['ok' => false, 'message' => "Ключи на «{$game['title']}» закончились"];
            }

            $u['cart'][] = $gameId;
            return ['ok' => true, 'message' => 'Добавлено в корзину', 'cartCount' => count($u['cart'])];
        });
    }

    public static function removeFromCart(string $login, string $gameId): array
    {
        return self::mutate($login, function (array &$u) use ($gameId) {
            $u['cart'] = array_values(array_diff($u['cart'], [$gameId]));
            return ['ok' => true, 'message' => 'Удалено из корзины', 'cartCount' => count($u['cart'])];
        });
    }

    public static function checkout(string $login): array
    {
        return self::mutate($login, function (array &$u) {
            if (empty($u['cart'])) {
                return ['ok' => false, 'message' => 'Корзина пуста'];
            }

            // Проверяем, что ключи ещё есть на все игры в корзине
            foreach ($u['cart'] as $id) {
                $game = self::findGame($id);
                if ($game && !Game::hasStock($id)) {
                    return [
                        'ok'      => false,
                        'message' => "Ключи на игру «{$game['title']}» закончились. Уберите её из корзины.",
                    ];
                }
            }

            $total = 0;
            foreach ($u['cart'] as $id) {
                $game = self::findGame($id);
                if ($game) $total += $game['price'];
            }

            if ($u['balance'] < $total) {
                $need = $total - $u['balance'];
                return ['ok' => false, 'message' => "Недостаточно средств. Не хватает {$need} ₽"];
            }

            $u['balance'] -= $total;

            // Генерируем ключи активации для платных игр и списываем остаток
            $newKeys = [];
            foreach ($u['cart'] as $id) {
                $game = self::findGame($id);
                if (!$game) continue;

                if ($game['price'] > 0) {
                    $key = KeyGenerator::generate();
                    $u['keys'][$id] = $key;
                    $newKeys[] = ['id' => $id, 'title' => $game['title'], 'key' => $key];
                    Game::decrementStock($id);
                }
            }

            $u['library'] = array_values(array_unique(array_merge($u['library'], $u['cart'])));

            $purchasedItems = [];
            foreach ($u['cart'] as $id) {
                $game = self::findGame($id);
                if ($game) {
                    $purchasedItems[] = ['id' => $id, 'title' => $game['title'], 'price' => $game['price']];
                }
            }
            $u['purchases'][] = [
                'date'  => date('Y-m-d H:i'),
                'items' => $purchasedItems,
                'total' => $total,
            ];

            $u['cart']    = [];

            return [
                'ok'      => true,
                'message' => "Покупка успешна! Списано {$total} ₽",
                'balance' => $u['balance'],
                'keys'    => $newKeys,
            ];
        });
    }

    // ---------- Профиль: аватар и смена пароля ----------

    public static function setAvatar(string $login, string $avatar): array
    {
        if ($avatar === '') {
            return ['ok' => false, 'message' => 'Пустой аватар'];
        }
        // Data URI (загруженное изображение) ограничиваем по размеру, чтобы не раздувать JSON-хранилище
        if (str_starts_with($avatar, 'data:image') && strlen($avatar) > 400000) {
            return ['ok' => false, 'message' => 'Изображение слишком большое. Выберите файл поменьше'];
        }

        return self::mutate($login, function (array &$u) use ($avatar) {
            $u['avatar'] = $avatar;
            return ['ok' => true, 'message' => 'Аватар обновлён', 'avatar' => $avatar];
        });
    }

    public static function changePassword(string $login, string $oldPassword, string $newPassword): array
    {
        if (mb_strlen($newPassword) < 4) {
            return ['ok' => false, 'message' => 'Новый пароль — от 4 символов'];
        }

        $raw = self::rawAll();
        foreach ($raw as $i => $u) {
            if ($u['login'] !== $login) continue;

            if (!password_verify($oldPassword, $u['password'])) {
                return ['ok' => false, 'message' => 'Текущий пароль указан неверно'];
            }

            $raw[$i]['password'] = password_hash($newPassword, PASSWORD_DEFAULT);
            self::save($raw);
            return ['ok' => true, 'message' => 'Пароль успешно изменён'];
        }

        return ['ok' => false, 'message' => 'Пользователь не найден'];
    }

    // ---------- Админ: пользователи и статистика продаж ----------

    /**
     * Список пользователей для админ-панели, без хешей паролей.
     */
    public static function publicList(): array
    {
        return array_map(function (array $u) {
            $spent = 0;
            foreach ($u['purchases'] as $p) {
                $spent += (int)($p['total'] ?? 0);
            }
            return [
                'login'         => $u['login'],
                'role'          => $u['role'],
                'avatar'        => $u['avatar'],
                'balance'       => $u['balance'],
                'libraryCount'  => count($u['library']),
                'purchaseCount' => count($u['purchases']),
                'totalSpent'    => $spent,
            ];
        }, self::all());
    }

    public static function setRole(string $login, string $role, ?string $actingLogin = null): array
    {
        if (!in_array($role, ['user', 'admin'], true)) {
            return ['ok' => false, 'message' => 'Некорректная роль'];
        }
        if ($actingLogin !== null && $actingLogin === $login) {
            return ['ok' => false, 'message' => 'Нельзя изменить роль самому себе'];
        }

        return self::mutate($login, function (array &$u) use ($role) {
            $u['role'] = $role;
            return ['ok' => true, 'message' => 'Роль пользователя «' . $u['login'] . '» изменена на ' . $role, 'role' => $role];
        });
    }

    /**
     * Общая статистика продаж по всем пользователям — для админ-панели.
     */
    public static function salesStats(): array
    {
        $users = self::all();

        $totalRevenue = 0;
        $totalSales   = 0;
        $totalItems   = 0;
        $perGame      = []; // id => ['title'=>..., 'count'=>..., 'revenue'=>...]

        foreach ($users as $u) {
            foreach ($u['purchases'] as $p) {
                $totalSales++;
                $totalRevenue += (int)($p['total'] ?? 0);
                foreach (($p['items'] ?? []) as $item) {
                    $totalItems++;
                    $id = $item['id'] ?? $item['title'];
                    if (!isset($perGame[$id])) {
                        $perGame[$id] = ['title' => $item['title'], 'count' => 0, 'revenue' => 0];
                    }
                    $perGame[$id]['count']++;
                    $perGame[$id]['revenue'] += (int)($item['price'] ?? 0);
                }
            }
        }

        uasort($perGame, fn($a, $b) => $b['count'] <=> $a['count']);
        $topGames = array_slice(array_values($perGame), 0, 5);

        $admins = 0;
        foreach ($users as $u) {
            if (($u['role'] ?? 'user') === 'admin') $admins++;
        }

        return [
            'totalRevenue' => $totalRevenue,
            'totalSales'   => $totalSales,
            'totalItems'   => $totalItems,
            'usersCount'   => count($users),
            'adminsCount'  => $admins,
            'gamesCount'   => count(Game::all()),
            'topGames'     => $topGames,
        ];
    }

    /**
     * Пополнение баланса через демо-форму банковской карты.
     * Реальных списаний не происходит — только проверка формата данных карты.
     */
    public static function topUp(string $login, int $amount, array $card): array
    {
        if ($amount <= 0 || $amount > 100000) {
            return ['ok' => false, 'message' => 'Сумма должна быть от 1 до 100 000 ₽'];
        }

        $check = CardValidator::validate($card);
        if (!$check['ok']) {
            return $check;
        }

        return self::mutate($login, function (array &$u) use ($amount, $check) {
            $u['balance'] += $amount;
            return [
                'ok'      => true,
                'message' => "Оплата картой {$check['brand']} •••• {$check['last4']} прошла успешно. Баланс пополнен на {$amount} ₽",
                'balance' => $u['balance'],
            ];
        });
    }
}

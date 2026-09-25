<?php
session_start();

require __DIR__ . '/../core/Router.php';
require __DIR__ . '/../core/Controller.php';
require __DIR__ . '/../core/Session.php';
require __DIR__ . '/../app/Support/KeyGenerator.php';
require __DIR__ . '/../app/Support/CardValidator.php';
require __DIR__ . '/../app/Models/Game.php';
require __DIR__ . '/../app/Models/User.php';
require __DIR__ . '/../app/Controllers/HomeController.php';
require __DIR__ . '/../app/Controllers/AuthController.php';
require __DIR__ . '/../app/Controllers/CartController.php';
require __DIR__ . '/../app/Controllers/BalanceController.php';
require __DIR__ . '/../app/Controllers/ProfileController.php';
require __DIR__ . '/../app/Controllers/AdminController.php';

use Core\Router;
use App\Controllers\HomeController;
use App\Controllers\AuthController;
use App\Controllers\CartController;
use App\Controllers\BalanceController;
use App\Controllers\ProfileController;
use App\Controllers\AdminController;

$router = new Router();

$router->get('/',              [HomeController::class, 'index']);

$router->get('/login',         [AuthController::class, 'showLogin']);
$router->post('/login',        [AuthController::class, 'login']);
$router->get('/register',      [AuthController::class, 'showRegister']);
$router->post('/register',     [AuthController::class, 'register']);
$router->post('/logout',       [AuthController::class, 'logout']);

$router->get('/cart',          [CartController::class, 'index']);
$router->post('/cart/add',     [CartController::class, 'add']);
$router->post('/cart/remove',  [CartController::class, 'remove']);
$router->post('/cart/checkout',[CartController::class, 'checkout']);

$router->post('/balance/topup',[BalanceController::class, 'topUp']);

$router->get('/library',       [ProfileController::class, 'library']);
$router->get('/profile',       [ProfileController::class, 'profile']);
$router->post('/profile/avatar',   [ProfileController::class, 'updateAvatar']);
$router->post('/profile/password', [ProfileController::class, 'changePassword']);

$router->get('/admin',              [AdminController::class, 'index']);
$router->post('/admin/game/create', [AdminController::class, 'create']);
$router->post('/admin/game/update', [AdminController::class, 'update']);
$router->post('/admin/game/delete', [AdminController::class, 'delete']);
$router->get('/admin/users',        [AdminController::class, 'users']);
$router->post('/admin/user/role',   [AdminController::class, 'setRole']);
$router->get('/admin/stats',        [AdminController::class, 'stats']);

$router->dispatch($_SERVER['REQUEST_URI'], $_SERVER['REQUEST_METHOD']);

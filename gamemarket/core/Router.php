<?php
namespace Core;

class Router
{
    private array $routes = ['GET' => [], 'POST' => []];

    public function get(string $path, array $action): void
    {
        $this->routes['GET'][$path] = $action;
    }

    public function post(string $path, array $action): void
    {
        $this->routes['POST'][$path] = $action;
    }

    public function dispatch(string $uri, string $method): void
    {
        // 1. Если передан ?r=/login — берём его
        if (isset($_GET['r'])) {
            $path = '/' . trim($_GET['r'], '/');
        } else {
            // 2. Иначе берём путь из URL и обрезаем префикс подпапки + index.php
            $path = parse_url($uri, PHP_URL_PATH);

            // убираем /gamemarket/public
            $path = preg_replace('#^/gamemarket/public#', '', $path) ?: '/';

            // убираем /index.php
            $path = preg_replace('#^/index\.php#', '', $path) ?: '/';
        }

        // Приводим "/" к "/" и "/login/" к "/login"
        $path = '/' . trim($path, '/');

        if (!isset($this->routes[$method][$path])) {
            http_response_code(404);
            echo "404 Not Found — маршрут: " . htmlspecialchars($path);
            return;
        }

        [$class, $fn] = $this->routes[$method][$path];
        (new $class())->$fn();
    }
}

<?php
namespace Core;

class Controller
{
    protected function view(string $name, array $data = []): void
    {
        extract($data);
        require __DIR__ . '/../app/Views/' . $name . '.php';
    }

    protected function redirect(string $url): void
    {
        header("Location: $url");
        exit;
    }

    protected function json($data): void
    {
        header('Content-Type: application/json');
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
    }
}

<?php
namespace Core;

class Session
{
    public static function user(): ?string
    {
        return $_SESSION['user'] ?? null;
    }

    public static function login(string $login): void
    {
        // защита от session fixation
        session_regenerate_id(true);
        $_SESSION['user'] = $login;
    }

    public static function logout(): void
    {
        $_SESSION = [];
        session_destroy();
    }

    public static function requireLogin(): void
    {
        if (!self::user()) {
            http_response_code(401);
            die("Сначала войдите");
        }
    }
}

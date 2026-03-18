<?php

declare(strict_types=1);

namespace App\Core;

final class Csrf
{
    private const KEY = '_csrf_token';

    public static function boot(): void
    {
        self::token();
    }

    public static function token(): string
    {
        $token = Session::get(self::KEY);

        if (! is_string($token) || $token === '') {
            $token = bin2hex(random_bytes(32));
            Session::set(self::KEY, $token);
        }

        return $token;
    }

    public static function input(): string
    {
        return '<input type="hidden" name="_token" value="' . htmlspecialchars(self::token(), ENT_QUOTES, 'UTF-8') . '">';
    }

    public static function verifyFromRequest(): bool
    {
        $token = $_POST['_token'] ?? '';

        return is_string($token) && hash_equals((string) Session::get(self::KEY, ''), $token);
    }
}

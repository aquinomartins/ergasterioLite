<?php

declare(strict_types=1);

namespace App\Core;

use App\Repositories\UserRepository;

final class Auth
{
    private const KEY = 'auth_user_id';

    public static function boot(): void
    {
        View::share('auth', [
            'check' => self::check(),
        ]);
    }

    public static function attempt(string $email, string $password): bool
    {
        $repository = new UserRepository();
        $user = $repository->findByEmail($email);

        if ($user === null || ! password_verify($password, $user['password_hash'])) {
            return false;
        }

        Session::regenerate();
        Session::set(self::KEY, (int) $user['id']);

        return true;
    }

    public static function login(int $userId): void
    {
        Session::regenerate();
        Session::set(self::KEY, $userId);
    }

    public static function logout(): void
    {
        Session::forget(self::KEY);
        Session::invalidate();
    }

    public static function id(): ?int
    {
        $id = Session::get(self::KEY);

        return $id !== null ? (int) $id : null;
    }

    public static function check(): bool
    {
        return self::id() !== null;
    }

    public static function user(): ?array
    {
        $id = self::id();

        if ($id === null) {
            return null;
        }

        return (new UserRepository())->findWithProfileById($id);
    }
}

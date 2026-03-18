<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Auth;
use App\Core\Database;
use App\Models\Profile;
use App\Models\User;
use App\Repositories\ProfileRepository;
use App\Repositories\UserRepository;
use RuntimeException;

final class AuthService
{
    private UserRepository $users;
    private ProfileRepository $profiles;

    public function __construct()
    {
        $this->users = new UserRepository();
        $this->profiles = new ProfileRepository();
    }

    public function register(array $data): array
    {
        if ($this->users->findByEmail($data['email']) !== null) {
            return ['email' => ['Este e-mail já está em uso.']];
        }

        if ($this->profiles->findByUsername($data['username']) !== null) {
            return ['username' => ['Este username já está em uso.']];
        }

        $pdo = Database::connection();
        $pdo->beginTransaction();

        try {
            $userId = $this->users->create(new User(
                null,
                $data['email'],
                password_hash($data['password'], PASSWORD_DEFAULT),
                'active'
            ));

            $this->profiles->create(new Profile(
                null,
                $userId,
                $data['display_name'],
                $data['username'],
                ''
            ));

            $pdo->commit();
            Auth::login($userId);
        } catch (\Throwable $throwable) {
            $pdo->rollBack();
            throw new RuntimeException('Não foi possível concluir o cadastro.', 0, $throwable);
        }

        return [];
    }

    public function login(array $data): bool
    {
        return Auth::attempt($data['email'], $data['password']);
    }

    public function updateProfile(int $userId, array $data): array
    {
        $existing = $this->profiles->findByUsername($data['username']);

        if ($existing !== null && (int) $existing['user_id'] !== $userId) {
            return ['username' => ['Este username já está em uso.']];
        }

        $current = $this->profiles->findByUserId($userId);

        if ($current === null) {
            throw new RuntimeException('Perfil não encontrado.');
        }

        $this->profiles->updateByUserId($userId, new Profile(
            (int) $current['id'],
            $userId,
            $data['display_name'],
            $data['username'],
            $data['bio']
        ));

        return [];
    }
}

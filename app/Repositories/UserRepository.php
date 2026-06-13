<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;
use App\Models\User;
use PDO;

final class UserRepository
{
    private ?PDO $pdo;
    private static ?bool $usersRoleColumnExists = null;

    public function __construct(?PDO $pdo = null)
    {
        $this->pdo = $pdo;
    }

    private function connection(): PDO
    {
        return $this->pdo ?? Database::connection();
    }

    public function findByEmail(string $email): ?array
    {
        $statement = $this->connection()->prepare('SELECT * FROM users WHERE email = :email LIMIT 1');
        $statement->execute(['email' => $email]);
        $user = $statement->fetch();

        return $user ?: null;
    }

    public function findById(int $id): ?array
    {
        $statement = $this->connection()->prepare('SELECT * FROM users WHERE id = :id LIMIT 1');
        $statement->execute(['id' => $id]);
        $user = $statement->fetch();

        return $user ?: null;
    }

    public function findWithProfileById(int $id): ?array
    {
        $roleSelect = $this->usersRoleColumnExists() ? 'u.role' : "'user' AS role";
        $statement = $this->connection()->prepare(
            'SELECT u.id, u.email, u.status, ' . $roleSelect . ', u.created_at, u.updated_at,
                    p.display_name, p.username, p.bio
             FROM users u
             LEFT JOIN profiles p ON p.user_id = u.id
             WHERE u.id = :id
             LIMIT 1'
        );
        $statement->execute(['id' => $id]);
        $user = $statement->fetch();

        return $user ?: null;
    }

    private function usersRoleColumnExists(): bool
    {
        if (self::$usersRoleColumnExists !== null) {
            return self::$usersRoleColumnExists;
        }

        $statement = $this->connection()->prepare(
            'SELECT COUNT(*)
             FROM INFORMATION_SCHEMA.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE()
               AND TABLE_NAME = :table
               AND COLUMN_NAME = :column'
        );
        $statement->execute([
            'table' => 'users',
            'column' => 'role',
        ]);

        self::$usersRoleColumnExists = ((int) $statement->fetchColumn()) > 0;

        return self::$usersRoleColumnExists;
    }

    public function create(User $user): int
    {
        $statement = $this->connection()->prepare(
            'INSERT INTO users (email, password_hash, status, created_at, updated_at)
             VALUES (:email, :password_hash, :status, NOW(), NOW())'
        );
        $statement->execute([
            'email' => $user->email,
            'password_hash' => $user->passwordHash,
            'status' => $user->status,
        ]);

        return (int) $this->connection()->lastInsertId();
    }
}

<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;
use App\Models\User;
use PDO;

final class UserRepository
{
    private ?PDO $pdo;
    /** @var array<string, bool> */
    private static array $columnExistsCache = [];

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
        $selectColumns = [
            'u.id',
            'u.email',
            $this->columnExists('users', 'status') ? 'u.status' : "'active' AS status",
            $this->columnExists('users', 'role') ? 'u.role' : "'user' AS role",
            $this->columnExists('users', 'created_at') ? 'u.created_at' : 'NULL AS created_at',
            $this->columnExists('users', 'updated_at') ? 'u.updated_at' : 'NULL AS updated_at',
            $this->columnExists('profiles', 'display_name') ? 'p.display_name' : 'NULL AS display_name',
            $this->columnExists('profiles', 'username') ? 'p.username' : 'NULL AS username',
            $this->columnExists('profiles', 'bio') ? 'p.bio' : 'NULL AS bio',
        ];

        $statement = $this->connection()->prepare(
            'SELECT ' . implode(', ', $selectColumns) . '
             FROM users u
             LEFT JOIN profiles p ON p.user_id = u.id
             WHERE u.id = :id
             LIMIT 1'
        );
        $statement->execute(['id' => $id]);
        $user = $statement->fetch();

        return $user ?: null;
    }

    private function columnExists(string $table, string $column): bool
    {
        $cacheKey = $table . '.' . $column;

        if (array_key_exists($cacheKey, self::$columnExistsCache)) {
            return self::$columnExistsCache[$cacheKey];
        }

        $statement = $this->connection()->prepare(
            'SELECT COUNT(*)
             FROM INFORMATION_SCHEMA.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE()
               AND TABLE_NAME = :table
               AND COLUMN_NAME = :column'
        );
        $statement->execute([
            'table' => $table,
            'column' => $column,
        ]);

        self::$columnExistsCache[$cacheKey] = ((int) $statement->fetchColumn()) > 0;

        return self::$columnExistsCache[$cacheKey];
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

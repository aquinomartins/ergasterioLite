<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;
use App\Models\UserBalance;
use PDO;

final class UserBalanceRepository
{
    private ?PDO $pdo;

    public function __construct(?PDO $pdo = null)
    {
        $this->pdo = $pdo;
    }

    private function connection(): PDO
    {
        return $this->pdo ?? Database::connection();
    }

    public function getByUserId(int $userId): ?array
    {
        $statement = $this->connection()->prepare(
            'SELECT * FROM user_balances WHERE user_id = :user_id LIMIT 1'
        );
        $statement->execute(['user_id' => $userId]);

        $balance = $statement->fetch();

        return $balance ?: null;
    }

    public function create(UserBalance $userBalance): int
    {
        $statement = $this->connection()->prepare(
            'INSERT INTO user_balances (user_id, balance, updated_at)
             VALUES (:user_id, :balance, NOW())'
        );
        $statement->execute([
            'user_id' => $userBalance->userId,
            'balance' => $userBalance->balance,
        ]);

        return (int) $this->connection()->lastInsertId();
    }

    public function updateBalance(int $userId, float $balance): bool
    {
        $statement = $this->connection()->prepare(
            'UPDATE user_balances SET balance = :balance, updated_at = NOW() WHERE user_id = :user_id'
        );

        return $statement->execute([
            'user_id' => $userId,
            'balance' => $balance,
        ]);
    }

    public function decreaseBalance(int $userId, float $amount): bool
    {
        $statement = $this->connection()->prepare(
            'UPDATE user_balances
             SET balance = balance - :amount, updated_at = NOW()
             WHERE user_id = :user_id AND balance >= :amount'
        );
        $statement->execute([
            'user_id' => $userId,
            'amount' => $amount,
        ]);

        return $statement->rowCount() > 0;
    }

    public function increaseBalance(int $userId, float $amount): bool
    {
        $statement = $this->connection()->prepare(
            'UPDATE user_balances
             SET balance = balance + :amount, updated_at = NOW()
             WHERE user_id = :user_id'
        );

        return $statement->execute([
            'user_id' => $userId,
            'amount' => $amount,
        ]);
    }
}

<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;
use App\Models\Position;
use PDO;

final class PositionRepository
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

    public function create(Position $position): int
    {
        $statement = $this->connection()->prepare(
            'INSERT INTO positions (user_id, market_id, option_id, shares_amount, created_at)
             VALUES (:user_id, :market_id, :option_id, :shares_amount, NOW())'
        );

        $statement->execute([
            'user_id' => $position->userId,
            'market_id' => $position->marketId,
            'option_id' => $position->optionId,
            'shares_amount' => $position->sharesAmount,
        ]);

        return (int) $this->connection()->lastInsertId();
    }

    public function getByUserId(int $userId): array
    {
        $statement = $this->connection()->prepare(
            'SELECT p.*, m.title AS market_title, m.slug AS market_slug, mo.label AS option_label
             FROM positions p
             INNER JOIN markets m ON m.id = p.market_id
             INNER JOIN market_options mo ON mo.id = p.option_id
             WHERE p.user_id = :user_id
             ORDER BY p.created_at DESC, p.id DESC'
        );
        $statement->execute(['user_id' => $userId]);

        return $statement->fetchAll();
    }

    public function getByMarketId(int $marketId): array
    {
        $statement = $this->connection()->prepare(
            'SELECT p.*, mo.label AS option_label,
                    COALESCE(pr.display_name, pr.username, u.email) AS user_name
             FROM positions p
             INNER JOIN market_options mo ON mo.id = p.option_id
             INNER JOIN users u ON u.id = p.user_id
             LEFT JOIN profiles pr ON pr.user_id = u.id
             WHERE p.market_id = :market_id
             ORDER BY p.created_at DESC, p.id DESC'
        );
        $statement->execute(['market_id' => $marketId]);

        return $statement->fetchAll();
    }

    public function getUserPositionsByMarket(int $userId, int $marketId): array
    {
        $statement = $this->connection()->prepare(
            'SELECT p.*, mo.label AS option_label
             FROM positions p
             INNER JOIN market_options mo ON mo.id = p.option_id
             WHERE p.user_id = :user_id AND p.market_id = :market_id
             ORDER BY p.created_at DESC, p.id DESC'
        );
        $statement->execute([
            'user_id' => $userId,
            'market_id' => $marketId,
        ]);

        return $statement->fetchAll();
    }
}

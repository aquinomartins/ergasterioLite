<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;
use PDO;

final class ReputationLogRepository
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

    public function create(int $userId, ?int $marketId, string $reason, float $pointsDelta): int
    {
        $statement = $this->connection()->prepare(
            'INSERT INTO reputation_logs (user_id, market_id, reason, points_delta, created_at)
             VALUES (:user_id, :market_id, :reason, :points_delta, NOW())'
        );
        $statement->execute([
            'user_id' => $userId,
            'market_id' => $marketId,
            'reason' => $reason,
            'points_delta' => $pointsDelta,
        ]);

        return (int) $this->connection()->lastInsertId();
    }

    public function getByUserId(int $userId): array
    {
        $statement = $this->connection()->prepare(
            'SELECT rl.*, m.title AS market_title
             FROM reputation_logs rl
             LEFT JOIN markets m ON m.id = rl.market_id
             WHERE rl.user_id = :user_id
             ORDER BY rl.created_at DESC, rl.id DESC'
        );
        $statement->execute(['user_id' => $userId]);

        return $statement->fetchAll();
    }
}

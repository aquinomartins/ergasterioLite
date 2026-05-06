<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;
use PDO;

final class RankingRepository
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

    public function findByUserId(int $userId): ?array
    {
        $statement = $this->connection()->prepare('SELECT * FROM rankings WHERE user_id = :user_id LIMIT 1');
        $statement->execute(['user_id' => $userId]);

        $ranking = $statement->fetch();

        return $ranking ?: null;
    }

    public function createIfNotExists(int $userId): void
    {
        if ($this->findByUserId($userId) !== null) {
            return;
        }

        $statement = $this->connection()->prepare(
            'INSERT INTO rankings (
                user_id, total_payoff, total_markets_participated, total_markets_won, reputation_score, updated_at
             ) VALUES (:user_id, 0, 0, 0, 0, NOW())'
        );
        $statement->execute(['user_id' => $userId]);
    }

    public function incrementPayoff(int $userId, float $amount): bool
    {
        return $this->incrementField($userId, 'total_payoff', $amount);
    }

    public function incrementMarketsParticipated(int $userId): bool
    {
        return $this->incrementField($userId, 'total_markets_participated', 1.0);
    }

    public function incrementMarketsWon(int $userId): bool
    {
        return $this->incrementField($userId, 'total_markets_won', 1.0);
    }

    public function incrementReputationScore(int $userId, float $points): bool
    {
        return $this->incrementField($userId, 'reputation_score', $points);
    }

    public function getLeaderboard(int $limit = 50): array
    {
        $statement = $this->connection()->prepare(
            'SELECT r.*, COALESCE(p.display_name, p.username, u.email) AS user_name, u.email
             FROM rankings r
             INNER JOIN users u ON u.id = r.user_id
             LEFT JOIN profiles p ON p.user_id = u.id
             ORDER BY r.total_payoff DESC, r.reputation_score DESC, r.total_markets_won DESC
             LIMIT :lim'
        );
        $statement->bindValue(':lim', max(1, $limit), PDO::PARAM_INT);
        $statement->execute();

        return $statement->fetchAll();
    }

    private function incrementField(int $userId, string $field, float $amount): bool
    {
        $allowed = ['total_payoff', 'total_markets_participated', 'total_markets_won', 'reputation_score'];

        if (! in_array($field, $allowed, true)) {
            return false;
        }

        $statement = $this->connection()->prepare(
            "UPDATE rankings SET {$field} = {$field} + :amount, updated_at = NOW() WHERE user_id = :user_id"
        );

        return $statement->execute([
            'user_id' => $userId,
            'amount' => $amount,
        ]);
    }
}

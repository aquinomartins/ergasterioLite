<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;
use PDO;

final class MarketResolutionRepository
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

    public function create(int $marketId, int $winningOptionId, int $resolvedBy, ?string $notes): int
    {
        $statement = $this->connection()->prepare(
            'INSERT INTO market_resolutions (
                market_id, winning_option_id, resolved_by, resolution_notes, resolved_at, created_at
             ) VALUES (
                :market_id, :winning_option_id, :resolved_by, :resolution_notes, NOW(), NOW()
             )'
        );
        $statement->execute([
            'market_id' => $marketId,
            'winning_option_id' => $winningOptionId,
            'resolved_by' => $resolvedBy,
            'resolution_notes' => $notes,
        ]);

        return (int) $this->connection()->lastInsertId();
    }

    public function findByMarketId(int $marketId): ?array
    {
        $statement = $this->connection()->prepare(
            'SELECT mr.*, mo.label AS winning_option_label,
                    COALESCE(p.display_name, p.username, u.email) AS resolved_by_name
             FROM market_resolutions mr
             INNER JOIN market_options mo ON mo.id = mr.winning_option_id
             INNER JOIN users u ON u.id = mr.resolved_by
             LEFT JOIN profiles p ON p.user_id = u.id
             WHERE mr.market_id = :market_id
             LIMIT 1'
        );
        $statement->execute(['market_id' => $marketId]);

        $resolution = $statement->fetch();

        return $resolution ?: null;
    }
}

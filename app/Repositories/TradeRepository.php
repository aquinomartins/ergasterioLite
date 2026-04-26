<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;
use App\Models\Trade;
use PDO;

final class TradeRepository
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

    public function create(Trade $trade): int
    {
        $statement = $this->connection()->prepare(
            'INSERT INTO trades (user_id, market_id, option_id, shares_amount, created_at)
             VALUES (:user_id, :market_id, :option_id, :shares_amount, NOW())'
        );

        $statement->execute([
            'user_id' => $trade->userId,
            'market_id' => $trade->marketId,
            'option_id' => $trade->optionId,
            'shares_amount' => $trade->sharesAmount,
        ]);

        return (int) $this->connection()->lastInsertId();
    }

    public function getByMarketId(int $marketId): array
    {
        $statement = $this->connection()->prepare(
            'SELECT t.*, mo.label AS option_label,
                    COALESCE(pr.display_name, pr.username, u.email) AS user_name
             FROM trades t
             INNER JOIN market_options mo ON mo.id = t.option_id
             INNER JOIN users u ON u.id = t.user_id
             LEFT JOIN profiles pr ON pr.user_id = u.id
             WHERE t.market_id = :market_id
             ORDER BY t.created_at DESC, t.id DESC'
        );
        $statement->execute(['market_id' => $marketId]);

        return $statement->fetchAll();
    }
}

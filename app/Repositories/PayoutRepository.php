<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;
use App\Models\Payout;
use PDO;

final class PayoutRepository
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

    public function create(Payout $payout): int
    {
        $statement = $this->connection()->prepare(
            'INSERT INTO payouts (
                user_id, market_id, position_id, option_id, shares_amount,
                gross_amount, fee_amount, net_amount, status, created_at
             ) VALUES (
                :user_id, :market_id, :position_id, :option_id, :shares_amount,
                :gross_amount, :fee_amount, :net_amount, :status, NOW()
             )'
        );
        $statement->execute([
            'user_id' => $payout->userId,
            'market_id' => $payout->marketId,
            'position_id' => $payout->positionId,
            'option_id' => $payout->optionId,
            'shares_amount' => $payout->sharesAmount,
            'gross_amount' => $payout->grossAmount,
            'fee_amount' => $payout->feeAmount,
            'net_amount' => $payout->netAmount,
            'status' => $payout->status,
        ]);

        return (int) $this->connection()->lastInsertId();
    }

    public function getByMarketId(int $marketId): array
    {
        $statement = $this->connection()->prepare(
            'SELECT py.*, mo.label AS option_label,
                    COALESCE(pr.display_name, pr.username, u.email) AS user_name
             FROM payouts py
             INNER JOIN users u ON u.id = py.user_id
             LEFT JOIN profiles pr ON pr.user_id = u.id
             INNER JOIN market_options mo ON mo.id = py.option_id
             WHERE py.market_id = :market_id
             ORDER BY py.created_at DESC, py.id DESC'
        );
        $statement->execute(['market_id' => $marketId]);

        return $statement->fetchAll();
    }

    public function getByUserId(int $userId): array
    {
        $statement = $this->connection()->prepare(
            'SELECT py.*, m.title AS market_title, m.slug AS market_slug, mo.label AS option_label
             FROM payouts py
             INNER JOIN markets m ON m.id = py.market_id
             INNER JOIN market_options mo ON mo.id = py.option_id
             WHERE py.user_id = :user_id
             ORDER BY py.created_at DESC, py.id DESC'
        );
        $statement->execute(['user_id' => $userId]);

        return $statement->fetchAll();
    }
}

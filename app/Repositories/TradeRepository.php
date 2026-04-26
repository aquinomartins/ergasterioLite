<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;
use App\Models\Trade;
use PDO;

final class TradeRepository
{
    private ?PDO $pdo;
    private ?array $columns = null;

    public function __construct(?PDO $pdo = null)
    {
        $this->pdo = $pdo;
    }

    private function connection(): PDO
    {
        return $this->pdo ?? Database::connection();
    }

    private function columns(): array
    {
        if ($this->columns !== null) {
            return $this->columns;
        }

        $statement = $this->connection()->query('SHOW COLUMNS FROM trades');
        $columns = $statement !== false ? $statement->fetchAll(PDO::FETCH_COLUMN) : [];

        $this->columns = array_map('strval', $columns);

        return $this->columns;
    }

    private function optionColumn(): string
    {
        $columns = $this->columns();

        if (in_array('option_id', $columns, true)) {
            return 'option_id';
        }

        if (in_array('market_option_id', $columns, true)) {
            return 'market_option_id';
        }

        return 'option_id';
    }

    private function sharesColumn(): string
    {
        $columns = $this->columns();

        if (in_array('shares_amount', $columns, true)) {
            return 'shares_amount';
        }

        if (in_array('amount', $columns, true)) {
            return 'amount';
        }

        return 'shares_amount';
    }

    public function create(Trade $trade): int
    {
        $optionColumn = $this->optionColumn();
        $sharesColumn = $this->sharesColumn();
        $statement = $this->connection()->prepare(
            "INSERT INTO trades (user_id, market_id, {$optionColumn}, {$sharesColumn}, created_at)
             VALUES (:user_id, :market_id, :option_id, :shares_amount, NOW())"
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
        $optionColumn = $this->optionColumn();
        $sharesColumn = $this->sharesColumn();
        $statement = $this->connection()->prepare(
            "SELECT t.*,
                    t.{$optionColumn} AS option_id,
                    t.{$sharesColumn} AS shares_amount,
                    mo.label AS option_label,
                    COALESCE(pr.display_name, pr.username, u.email) AS user_name
             FROM trades t
             INNER JOIN market_options mo ON mo.id = t.{$optionColumn}
             INNER JOIN users u ON u.id = t.user_id
             LEFT JOIN profiles pr ON pr.user_id = u.id
             WHERE t.market_id = :market_id
             ORDER BY t.created_at DESC, t.id DESC"
        );
        $statement->execute(['market_id' => $marketId]);

        return $statement->fetchAll();
    }
}

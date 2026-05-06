<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;
use App\Models\Position;
use PDO;

final class PositionRepository
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

        $statement = $this->connection()->query('SHOW COLUMNS FROM positions');
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

    public function create(Position $position): int
    {
        $optionColumn = $this->optionColumn();
        $sharesColumn = $this->sharesColumn();
        $statement = $this->connection()->prepare(
            "INSERT INTO positions (user_id, market_id, {$optionColumn}, {$sharesColumn}, created_at)
             VALUES (:user_id, :market_id, :option_id, :shares_amount, NOW())"
        );

        $statement->execute([
            'user_id' => $position->userId,
            'market_id' => $position->marketId,
            'option_id' => $position->optionId,
            'shares_amount' => $position->sharesAmount,
        ]);

        return (int) $this->connection()->lastInsertId();
    }

    public function findByUserMarketOption(int $userId, int $marketId, int $optionId): ?array
    {
        $optionColumn = $this->optionColumn();
        $sharesColumn = $this->sharesColumn();
        $statement = $this->connection()->prepare(
            "SELECT *,
                    {$optionColumn} AS option_id,
                    {$sharesColumn} AS shares_amount
             FROM positions
             WHERE user_id = :user_id AND market_id = :market_id AND {$optionColumn} = :option_id
             LIMIT 1"
        );
        $statement->execute([
            'user_id' => $userId,
            'market_id' => $marketId,
            'option_id' => $optionId,
        ]);

        $position = $statement->fetch();

        return $position ?: null;
    }

    public function increaseShares(int $positionId, float $sharesAmount): bool
    {
        $sharesColumn = $this->sharesColumn();
        $statement = $this->connection()->prepare(
            "UPDATE positions
             SET {$sharesColumn} = {$sharesColumn} + :shares_amount
             WHERE id = :id"
        );

        return $statement->execute([
            'id' => $positionId,
            'shares_amount' => $sharesAmount,
        ]);
    }

    public function getByUserId(int $userId): array
    {
        $optionColumn = $this->optionColumn();
        $sharesColumn = $this->sharesColumn();
        $statement = $this->connection()->prepare(
            "SELECT p.*,
                    p.{$optionColumn} AS option_id,
                    p.{$sharesColumn} AS shares_amount,
                    m.title AS market_title, m.slug AS market_slug, mo.label AS option_label
             FROM positions p
             INNER JOIN markets m ON m.id = p.market_id
             INNER JOIN market_options mo ON mo.id = p.{$optionColumn}
             WHERE p.user_id = :user_id
             ORDER BY p.created_at DESC, p.id DESC"
        );
        $statement->execute(['user_id' => $userId]);

        return $statement->fetchAll();
    }

    public function getByMarketId(int $marketId): array
    {
        $optionColumn = $this->optionColumn();
        $sharesColumn = $this->sharesColumn();
        $statement = $this->connection()->prepare(
            "SELECT p.*,
                    p.{$optionColumn} AS option_id,
                    p.{$sharesColumn} AS shares_amount,
                    mo.label AS option_label,
                    COALESCE(pr.display_name, pr.username, u.email) AS user_name
             FROM positions p
             INNER JOIN market_options mo ON mo.id = p.{$optionColumn}
             INNER JOIN users u ON u.id = p.user_id
             LEFT JOIN profiles pr ON pr.user_id = u.id
             WHERE p.market_id = :market_id
             ORDER BY p.created_at DESC, p.id DESC"
        );
        $statement->execute(['market_id' => $marketId]);

        return $statement->fetchAll();
    }

    public function getUserPositionsByMarket(int $userId, int $marketId): array
    {
        $optionColumn = $this->optionColumn();
        $sharesColumn = $this->sharesColumn();
        $statement = $this->connection()->prepare(
            "SELECT p.*,
                    p.{$optionColumn} AS option_id,
                    p.{$sharesColumn} AS shares_amount,
                    mo.label AS option_label
             FROM positions p
             INNER JOIN market_options mo ON mo.id = p.{$optionColumn}
             WHERE p.user_id = :user_id AND p.market_id = :market_id
             ORDER BY p.created_at DESC, p.id DESC"
        );
        $statement->execute([
            'user_id' => $userId,
            'market_id' => $marketId,
        ]);

        return $statement->fetchAll();
    }

    public function getWinningPositions(int $marketId, int $winningOptionId): array
    {
        $optionColumn = $this->optionColumn();
        $sharesColumn = $this->sharesColumn();
        $statement = $this->connection()->prepare(
            "SELECT p.*, p.{$optionColumn} AS option_id, p.{$sharesColumn} AS shares_amount,
                    COALESCE(pr.display_name, pr.username, u.email) AS user_name
             FROM positions p
             INNER JOIN users u ON u.id = p.user_id
             LEFT JOIN profiles pr ON pr.user_id = u.id
             WHERE p.market_id = :market_id AND p.{$optionColumn} = :option_id
             ORDER BY p.id ASC"
        );
        $statement->execute([
            'market_id' => $marketId,
            'option_id' => $winningOptionId,
        ]);

        return $statement->fetchAll();
    }

    public function countUserMarketParticipation(int $userId, int $marketId): int
    {
        $statement = $this->connection()->prepare(
            'SELECT COUNT(*) FROM positions WHERE user_id = :user_id AND market_id = :market_id'
        );
        $statement->execute([
            'user_id' => $userId,
            'market_id' => $marketId,
        ]);

        return (int) $statement->fetchColumn();
    }

    public function getUsersWhoParticipatedInMarket(int $marketId): array
    {
        $statement = $this->connection()->prepare(
            'SELECT DISTINCT user_id FROM positions WHERE market_id = :market_id'
        );
        $statement->execute(['market_id' => $marketId]);

        return array_map('intval', $statement->fetchAll(PDO::FETCH_COLUMN));
    }

}

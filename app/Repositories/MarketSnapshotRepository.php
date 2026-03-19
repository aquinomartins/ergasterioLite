<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;
use App\Models\MarketSnapshot;
use PDO;

final class MarketSnapshotRepository
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

    public function createSnapshot(MarketSnapshot $snapshot): int
    {
        $statement = $this->connection()->prepare(
            'INSERT INTO market_snapshots (market_id, snapshot_json, created_at)
             VALUES (:market_id, :snapshot_json, NOW())'
        );
        $statement->execute([
            'market_id' => $snapshot->marketId,
            'snapshot_json' => $snapshot->snapshotJson,
        ]);

        return (int) $this->connection()->lastInsertId();
    }

    public function getByMarketId(int $marketId): array
    {
        $statement = $this->connection()->prepare(
            'SELECT * FROM market_snapshots WHERE market_id = :market_id ORDER BY created_at DESC, id DESC'
        );
        $statement->execute(['market_id' => $marketId]);

        return $statement->fetchAll();
    }
}

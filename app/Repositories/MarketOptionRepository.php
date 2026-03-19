<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;
use App\Models\MarketOption;
use PDO;

final class MarketOptionRepository
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

    public function createMany(array $options): void
    {
        $statement = $this->connection()->prepare(
            'INSERT INTO market_options (
                market_id, option_type, artwork_id, artist_id, label,
                weight_value, probability_value, sort_order, created_at, updated_at
             ) VALUES (
                :market_id, :option_type, :artwork_id, :artist_id, :label,
                :weight_value, :probability_value, :sort_order, NOW(), NOW()
             )'
        );

        foreach ($options as $option) {
            if (! $option instanceof MarketOption) {
                continue;
            }

            $statement->execute([
                'market_id' => $option->marketId,
                'option_type' => $option->optionType,
                'artwork_id' => $option->artworkId,
                'artist_id' => $option->artistId,
                'label' => $option->label,
                'weight_value' => $option->weightValue,
                'probability_value' => $option->probabilityValue,
                'sort_order' => $option->sortOrder,
            ]);
        }
    }

    public function getByMarketId(int $marketId): array
    {
        $statement = $this->connection()->prepare(
            'SELECT mo.*, aw.title AS artwork_title, aw.slug AS artwork_slug,
                    ar.display_name AS artist_name, ar.slug AS artist_slug
             FROM market_options mo
             LEFT JOIN artworks aw ON aw.id = mo.artwork_id
             LEFT JOIN artists ar ON ar.id = mo.artist_id
             WHERE mo.market_id = :market_id
             ORDER BY mo.sort_order ASC, mo.id ASC'
        );
        $statement->execute(['market_id' => $marketId]);

        return $statement->fetchAll();
    }

    public function updateWeightsAndProbabilities(array $options): void
    {
        $statement = $this->connection()->prepare(
            'UPDATE market_options
             SET weight_value = :weight_value, probability_value = :probability_value, updated_at = NOW()
             WHERE id = :id'
        );

        foreach ($options as $option) {
            $statement->execute([
                'id' => (int) $option['id'],
                'weight_value' => $option['weight_value'],
                'probability_value' => $option['probability_value'],
            ]);
        }
    }

    public function findById(int $id): ?array
    {
        $statement = $this->connection()->prepare(
            'SELECT mo.*, aw.title AS artwork_title, aw.slug AS artwork_slug,
                    ar.display_name AS artist_name, ar.slug AS artist_slug
             FROM market_options mo
             LEFT JOIN artworks aw ON aw.id = mo.artwork_id
             LEFT JOIN artists ar ON ar.id = mo.artist_id
             WHERE mo.id = :id
             LIMIT 1'
        );
        $statement->execute(['id' => $id]);
        $option = $statement->fetch();

        return $option ?: null;
    }
}

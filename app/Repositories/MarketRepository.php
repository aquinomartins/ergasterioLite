<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;
use App\Models\Market;
use PDO;

final class MarketRepository
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

    public function create(Market $market): int
    {
        $statement = $this->connection()->prepare(
            'INSERT INTO markets (
                title, slug, description, market_type, status, resolution_mode,
                opens_at, closes_at, resolved_option_id, created_by, created_at, updated_at
             ) VALUES (
                :title, :slug, :description, :market_type, :status, :resolution_mode,
                :opens_at, :closes_at, :resolved_option_id, :created_by, NOW(), NOW()
             )'
        );
        $statement->execute([
            'title' => $market->title,
            'slug' => $market->slug,
            'description' => $market->description,
            'market_type' => $market->marketType,
            'status' => $market->status,
            'resolution_mode' => $market->resolutionMode,
            'opens_at' => $market->opensAt,
            'closes_at' => $market->closesAt,
            'resolved_option_id' => $market->resolvedOptionId,
            'created_by' => $market->createdBy,
        ]);

        return (int) $this->connection()->lastInsertId();
    }

    public function update(int $id, array $data): bool
    {
        if ($data === []) {
            return false;
        }

        $allowed = [
            'title',
            'slug',
            'description',
            'market_type',
            'status',
            'resolution_mode',
            'opens_at',
            'closes_at',
            'resolved_option_id',
        ];

        $fields = [];
        $bindings = ['id' => $id];

        foreach ($allowed as $field) {
            if (! array_key_exists($field, $data)) {
                continue;
            }

            $fields[] = $field . ' = :' . $field;
            $bindings[$field] = $data[$field];
        }

        if ($fields === []) {
            return false;
        }

        $statement = $this->connection()->prepare(
            'UPDATE markets SET ' . implode(', ', $fields) . ', updated_at = NOW() WHERE id = :id'
        );

        return $statement->execute($bindings);
    }

    public function findById(int $id): ?array
    {
        $statement = $this->connection()->prepare(
            'SELECT m.*, u.email AS creator_email,
                    COALESCE(p.display_name, p.username, u.email) AS creator_name,
                    mo.label AS resolved_option_label
             FROM markets m
             INNER JOIN users u ON u.id = m.created_by
             LEFT JOIN profiles p ON p.user_id = u.id
             LEFT JOIN market_options mo ON mo.id = m.resolved_option_id
             WHERE m.id = :id
             LIMIT 1'
        );
        $statement->execute(['id' => $id]);
        $market = $statement->fetch();

        return $market ?: null;
    }

    public function findBySlug(string $slug): ?array
    {
        $statement = $this->connection()->prepare(
            'SELECT m.*, u.email AS creator_email,
                    COALESCE(p.display_name, p.username, u.email) AS creator_name,
                    mo.label AS resolved_option_label
             FROM markets m
             INNER JOIN users u ON u.id = m.created_by
             LEFT JOIN profiles p ON p.user_id = u.id
             LEFT JOIN market_options mo ON mo.id = m.resolved_option_id
             WHERE m.slug = :slug
             LIMIT 1'
        );
        $statement->execute(['slug' => $slug]);
        $market = $statement->fetch();

        return $market ?: null;
    }

    public function getAll(): array
    {
        $statement = $this->connection()->query(
            'SELECT m.*, u.email AS creator_email,
                    COALESCE(p.display_name, p.username, u.email) AS creator_name,
                    (SELECT COUNT(*) FROM market_options mo WHERE mo.market_id = m.id) AS options_count
             FROM markets m
             INNER JOIN users u ON u.id = m.created_by
             LEFT JOIN profiles p ON p.user_id = u.id
             ORDER BY m.created_at DESC, m.id DESC'
        );

        return $statement->fetchAll();
    }

    public function getOpenMarkets(): array
    {
        $statement = $this->connection()->prepare(
            'SELECT m.*, u.email AS creator_email,
                    COALESCE(p.display_name, p.username, u.email) AS creator_name,
                    (SELECT COUNT(*) FROM market_options mo WHERE mo.market_id = m.id) AS options_count
             FROM markets m
             INNER JOIN users u ON u.id = m.created_by
             LEFT JOIN profiles p ON p.user_id = u.id
             WHERE m.status = :status
               AND (m.opens_at IS NULL OR m.opens_at <= NOW())
               AND m.closes_at >= NOW()
             ORDER BY m.closes_at ASC, m.id DESC'
        );
        $statement->execute(['status' => 'open']);

        return $statement->fetchAll();
    }

    public function getByStatus(string $status): array
    {
        $statement = $this->connection()->prepare(
            'SELECT m.*, u.email AS creator_email,
                    COALESCE(p.display_name, p.username, u.email) AS creator_name,
                    (SELECT COUNT(*) FROM market_options mo WHERE mo.market_id = m.id) AS options_count
             FROM markets m
             INNER JOIN users u ON u.id = m.created_by
             LEFT JOIN profiles p ON p.user_id = u.id
             WHERE m.status = :status
             ORDER BY m.created_at DESC, m.id DESC'
        );
        $statement->execute(['status' => $status]);

        return $statement->fetchAll();
    }


    public function getMarketWithOptions(int $id): ?array
    {
        $market = $this->findById($id);

        if ($market === null) {
            return null;
        }

        $statement = $this->connection()->prepare(
            'SELECT * FROM market_options WHERE market_id = :market_id ORDER BY sort_order ASC, id ASC'
        );
        $statement->execute(['market_id' => $id]);
        $market['options'] = $statement->fetchAll();

        return $market;
    }

    public function slugExists(string $slug): bool
    {
        $statement = $this->connection()->prepare('SELECT id FROM markets WHERE slug = :slug LIMIT 1');
        $statement->execute(['slug' => $slug]);

        return (bool) $statement->fetchColumn();
    }

    public function setStatus(int $id, string $status): bool
    {
        $statement = $this->connection()->prepare(
            'UPDATE markets SET status = :status, updated_at = NOW() WHERE id = :id'
        );

        return $statement->execute([
            'id' => $id,
            'status' => $status,
        ]);
    }

    public function setResolvedOption(int $id, int $resolvedOptionId): bool
    {
        $statement = $this->connection()->prepare(
            'UPDATE markets
             SET resolved_option_id = :resolved_option_id, status = :status, updated_at = NOW()
             WHERE id = :id'
        );

        return $statement->execute([
            'id' => $id,
            'resolved_option_id' => $resolvedOptionId,
            'status' => 'resolved',
        ]);
    }
}

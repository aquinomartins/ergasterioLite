<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;
use App\Models\Artist;
use PDO;

final class ArtistRepository
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

    public function create(Artist $artist): int
    {
        $statement = $this->connection()->prepare(
            'INSERT INTO artists (user_id, display_name, slug, biography, created_at, updated_at)
             VALUES (:user_id, :display_name, :slug, :biography, NOW(), NOW())'
        );
        $statement->execute([
            'user_id' => $artist->userId,
            'display_name' => $artist->displayName,
            'slug' => $artist->slug,
            'biography' => $artist->biography,
        ]);

        return (int) $this->connection()->lastInsertId();
    }

    public function findById(int $id): ?array
    {
        $statement = $this->connection()->prepare('SELECT * FROM artists WHERE id = :id LIMIT 1');
        $statement->execute(['id' => $id]);
        $artist = $statement->fetch();

        return $artist ?: null;
    }

    public function findBySlug(string $slug): ?array
    {
        $statement = $this->connection()->prepare('SELECT * FROM artists WHERE slug = :slug LIMIT 1');
        $statement->execute(['slug' => $slug]);
        $artist = $statement->fetch();

        return $artist ?: null;
    }

    public function getAll(): array
    {
        $statement = $this->connection()->query(
            'SELECT a.*, COUNT(aw.id) AS artworks_count
             FROM artists a
             LEFT JOIN artworks aw ON aw.artist_id = a.id
             GROUP BY a.id
             ORDER BY a.display_name ASC'
        );

        return $statement->fetchAll();
    }

    public function getByUserId(int $userId): array
    {
        $statement = $this->connection()->prepare(
            'SELECT a.*, COUNT(aw.id) AS artworks_count
             FROM artists a
             LEFT JOIN artworks aw ON aw.artist_id = a.id
             WHERE a.user_id = :user_id
             GROUP BY a.id
             ORDER BY a.display_name ASC'
        );
        $statement->execute(['user_id' => $userId]);

        return $statement->fetchAll();
    }

    public function slugExists(string $slug): bool
    {
        $statement = $this->connection()->prepare('SELECT id FROM artists WHERE slug = :slug LIMIT 1');
        $statement->execute(['slug' => $slug]);

        return (bool) $statement->fetchColumn();
    }
}

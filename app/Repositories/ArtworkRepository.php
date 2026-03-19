<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;
use App\Models\Artwork;
use PDO;

final class ArtworkRepository
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

    public function create(Artwork $artwork): int
    {
        $statement = $this->connection()->prepare(
            'INSERT INTO artworks (artist_id, title, slug, description, image_path, created_at, updated_at)
             VALUES (:artist_id, :title, :slug, :description, :image_path, NOW(), NOW())'
        );
        $statement->execute([
            'artist_id' => $artwork->artistId,
            'title' => $artwork->title,
            'slug' => $artwork->slug,
            'description' => $artwork->description,
            'image_path' => $artwork->imagePath,
        ]);

        return (int) $this->connection()->lastInsertId();
    }

    public function findById(int $id): ?array
    {
        $statement = $this->connection()->prepare(
            'SELECT aw.*, a.display_name AS artist_name, a.slug AS artist_slug
             FROM artworks aw
             INNER JOIN artists a ON a.id = aw.artist_id
             WHERE aw.id = :id
             LIMIT 1'
        );
        $statement->execute(['id' => $id]);
        $artwork = $statement->fetch();

        return $artwork ?: null;
    }

    public function findBySlug(string $slug): ?array
    {
        $statement = $this->connection()->prepare(
            'SELECT aw.*, a.display_name AS artist_name, a.slug AS artist_slug
             FROM artworks aw
             INNER JOIN artists a ON a.id = aw.artist_id
             WHERE aw.slug = :slug
             LIMIT 1'
        );
        $statement->execute(['slug' => $slug]);
        $artwork = $statement->fetch();

        return $artwork ?: null;
    }

    public function getAll(): array
    {
        $statement = $this->connection()->query(
            'SELECT aw.*, a.display_name AS artist_name, a.slug AS artist_slug
             FROM artworks aw
             INNER JOIN artists a ON a.id = aw.artist_id
             ORDER BY aw.created_at DESC, aw.id DESC'
        );

        return $statement->fetchAll();
    }

    public function getByArtistId(int $artistId): array
    {
        $statement = $this->connection()->prepare(
            'SELECT aw.*, a.display_name AS artist_name, a.slug AS artist_slug
             FROM artworks aw
             INNER JOIN artists a ON a.id = aw.artist_id
             WHERE aw.artist_id = :artist_id
             ORDER BY aw.created_at DESC, aw.id DESC'
        );
        $statement->execute(['artist_id' => $artistId]);

        return $statement->fetchAll();
    }

    public function slugExists(string $slug): bool
    {
        $statement = $this->connection()->prepare('SELECT id FROM artworks WHERE slug = :slug LIMIT 1');
        $statement->execute(['slug' => $slug]);

        return (bool) $statement->fetchColumn();
    }
}

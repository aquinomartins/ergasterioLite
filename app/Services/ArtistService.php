<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Artist;
use App\Repositories\ArtistRepository;

final class ArtistService
{
    private ArtistRepository $artists;

    public function __construct()
    {
        $this->artists = new ArtistRepository();
    }

    public function createArtist(array $data, ?int $userId = null): array
    {
        $slug = $this->generateUniqueSlug($data['display_name']);
        $artistId = $this->artists->create(new Artist(
            null,
            $userId,
            $data['display_name'],
            $slug,
            $data['biography']
        ));

        return $this->getArtistById($artistId) ?? [];
    }

    public function getArtistById(int $id): ?array
    {
        return $this->artists->findById($id);
    }

    public function getArtistBySlug(string $slug): ?array
    {
        return $this->artists->findBySlug($slug);
    }

    public function listArtists(): array
    {
        return $this->artists->getAll();
    }

    public function listArtistsByUser(int $userId): array
    {
        return $this->artists->getByUserId($userId);
    }

    private function generateUniqueSlug(string $name): string
    {
        $baseSlug = $this->slugify($name);
        $slug = $baseSlug;
        $suffix = 2;

        while ($this->artists->slugExists($slug)) {
            $slug = $baseSlug . '-' . $suffix;
            $suffix++;
        }

        return $slug;
    }

    private function slugify(string $value): string
    {
        $normalized = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value) ?: $value;
        $slug = strtolower((string) preg_replace('/[^a-zA-Z0-9]+/', '-', $normalized));
        $slug = trim($slug, '-');

        return $slug !== '' ? $slug : 'artista';
    }
}

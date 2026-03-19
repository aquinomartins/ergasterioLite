<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Artwork;
use App\Repositories\ArtistRepository;
use App\Repositories\ArtworkRepository;

final class ArtworkService
{
    private const MAX_IMAGE_SIZE = 5_242_880;
    private const ALLOWED_IMAGE_TYPES = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
    ];

    private ArtworkRepository $artworks;
    private ArtistRepository $artists;

    public function __construct()
    {
        $this->artworks = new ArtworkRepository();
        $this->artists = new ArtistRepository();
    }

    public function createArtwork(array $data, array $file, int $currentUserId): array
    {
        $artist = $this->artists->findById((int) $data['artist_id']);

        if ($artist === null) {
            return ['errors' => ['artist_id' => ['O artista informado não existe.']]];
        }

        if ($artist['user_id'] !== null && (int) $artist['user_id'] !== $currentUserId) {
            return ['errors' => ['artist_id' => ['Você só pode publicar obras do seu próprio artista.']]];
        }

        $upload = $this->storeUploadedImage($file);

        if ($upload['errors'] !== []) {
            return $upload;
        }

        $slug = $this->generateUniqueSlug($data['title']);
        $artworkId = $this->artworks->create(new Artwork(
            null,
            (int) $artist['id'],
            $data['title'],
            $slug,
            $data['description'],
            $upload['path']
        ));

        return [
            'errors' => [],
            'artwork' => $this->getArtworkById($artworkId),
        ];
    }

    public function getArtworkById(int $id): ?array
    {
        return $this->artworks->findById($id);
    }

    public function getArtworkBySlug(string $slug): ?array
    {
        return $this->artworks->findBySlug($slug);
    }

    public function listArtworks(): array
    {
        return $this->artworks->getAll();
    }

    public function listByArtist(int $artistId): array
    {
        return $this->artworks->getByArtistId($artistId);
    }

    private function storeUploadedImage(array $file): array
    {
        $errorCode = (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE);

        if ($errorCode !== UPLOAD_ERR_OK) {
            return ['errors' => ['image' => ['Falha ao enviar a imagem.']]];
        }

        $size = (int) ($file['size'] ?? 0);

        if ($size <= 0 || $size > self::MAX_IMAGE_SIZE) {
            return ['errors' => ['image' => ['A imagem deve ter no máximo 5 MB.']]];
        }

        $tmpName = (string) ($file['tmp_name'] ?? '');
        $mimeType = $tmpName !== '' ? (string) mime_content_type($tmpName) : '';
        $extension = self::ALLOWED_IMAGE_TYPES[$mimeType] ?? null;

        if ($extension === null) {
            return ['errors' => ['image' => ['Envie uma imagem JPG ou PNG válida.']]];
        }

        $directory = BASE_PATH . '/public/uploads/artworks';

        if (! is_dir($directory)) {
            mkdir($directory, 0775, true);
        }

        $filename = date('YmdHis') . '_' . bin2hex(random_bytes(8)) . '.' . $extension;
        $destination = $directory . '/' . $filename;

        if (! move_uploaded_file($tmpName, $destination)) {
            return ['errors' => ['image' => ['Não foi possível salvar a imagem enviada.']]];
        }

        return [
            'errors' => [],
            'path' => '/uploads/artworks/' . $filename,
        ];
    }

    private function generateUniqueSlug(string $title): string
    {
        $baseSlug = $this->slugify($title);
        $slug = $baseSlug;
        $suffix = 2;

        while ($this->artworks->slugExists($slug)) {
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

        return $slug !== '' ? $slug : 'obra';
    }
}

<?php

declare(strict_types=1);

namespace App\Models;

final class Artwork
{
    public ?int $id;
    public int $artistId;
    public string $title;
    public string $slug;
    public string $description;
    public string $imagePath;
    public ?string $createdAt;
    public ?string $updatedAt;

    public function __construct(
        ?int $id,
        int $artistId,
        string $title,
        string $slug,
        string $description,
        string $imagePath,
        ?string $createdAt = null,
        ?string $updatedAt = null
    ) {
        $this->id = $id;
        $this->artistId = $artistId;
        $this->title = $title;
        $this->slug = $slug;
        $this->description = $description;
        $this->imagePath = $imagePath;
        $this->createdAt = $createdAt;
        $this->updatedAt = $updatedAt;
    }
}

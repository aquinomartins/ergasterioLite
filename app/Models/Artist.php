<?php

declare(strict_types=1);

namespace App\Models;

final class Artist
{
    public ?int $id;
    public ?int $userId;
    public string $displayName;
    public string $slug;
    public string $biography;
    public ?string $createdAt;
    public ?string $updatedAt;

    public function __construct(
        ?int $id,
        ?int $userId,
        string $displayName,
        string $slug,
        string $biography = '',
        ?string $createdAt = null,
        ?string $updatedAt = null
    ) {
        $this->id = $id;
        $this->userId = $userId;
        $this->displayName = $displayName;
        $this->slug = $slug;
        $this->biography = $biography;
        $this->createdAt = $createdAt;
        $this->updatedAt = $updatedAt;
    }
}

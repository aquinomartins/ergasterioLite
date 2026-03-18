<?php

declare(strict_types=1);

namespace App\Models;

final class Profile
{
    public ?int $id;
    public int $userId;
    public string $displayName;
    public string $username;
    public string $bio;
    public ?string $createdAt;
    public ?string $updatedAt;

    public function __construct(
        ?int $id,
        int $userId,
        string $displayName,
        string $username,
        string $bio = '',
        ?string $createdAt = null,
        ?string $updatedAt = null
    ) {
        $this->id = $id;
        $this->userId = $userId;
        $this->displayName = $displayName;
        $this->username = $username;
        $this->bio = $bio;
        $this->createdAt = $createdAt;
        $this->updatedAt = $updatedAt;
    }
}

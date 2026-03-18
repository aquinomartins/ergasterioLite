<?php

declare(strict_types=1);

namespace App\Models;

final class Profile
{
    public function __construct(
        public readonly ?int $id,
        public readonly int $userId,
        public readonly string $displayName,
        public readonly string $username,
        public readonly string $bio = '',
        public readonly ?string $createdAt = null,
        public readonly ?string $updatedAt = null,
    ) {
    }
}

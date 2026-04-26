<?php

declare(strict_types=1);

namespace App\Models;

final class UserBalance
{
    public ?int $id;
    public int $userId;
    public float $balance;
    public ?string $updatedAt;

    public function __construct(?int $id, int $userId, float $balance, ?string $updatedAt = null)
    {
        $this->id = $id;
        $this->userId = $userId;
        $this->balance = $balance;
        $this->updatedAt = $updatedAt;
    }
}

<?php

declare(strict_types=1);

namespace App\Models;

final class Trade
{
    public ?int $id;
    public int $userId;
    public int $marketId;
    public int $optionId;
    public float $sharesAmount;
    public ?string $createdAt;

    public function __construct(
        ?int $id,
        int $userId,
        int $marketId,
        int $optionId,
        float $sharesAmount,
        ?string $createdAt = null
    ) {
        $this->id = $id;
        $this->userId = $userId;
        $this->marketId = $marketId;
        $this->optionId = $optionId;
        $this->sharesAmount = $sharesAmount;
        $this->createdAt = $createdAt;
    }
}

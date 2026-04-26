<?php

declare(strict_types=1);

namespace App\Models;

final class ReputationLog
{
    public ?int $id;
    public int $userId;
    public ?int $marketId;
    public string $reason;
    public float $pointsDelta;
    public ?string $createdAt;

    public function __construct(
        ?int $id,
        int $userId,
        ?int $marketId,
        string $reason,
        float $pointsDelta,
        ?string $createdAt = null
    ) {
        $this->id = $id;
        $this->userId = $userId;
        $this->marketId = $marketId;
        $this->reason = $reason;
        $this->pointsDelta = $pointsDelta;
        $this->createdAt = $createdAt;
    }
}

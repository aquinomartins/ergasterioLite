<?php

declare(strict_types=1);

namespace App\Models;

final class MarketResolution
{
    public ?int $id;
    public int $marketId;
    public int $winningOptionId;
    public int $resolvedBy;
    public ?string $resolutionNotes;
    public ?string $resolvedAt;
    public ?string $createdAt;

    public function __construct(
        ?int $id,
        int $marketId,
        int $winningOptionId,
        int $resolvedBy,
        ?string $resolutionNotes = null,
        ?string $resolvedAt = null,
        ?string $createdAt = null
    ) {
        $this->id = $id;
        $this->marketId = $marketId;
        $this->winningOptionId = $winningOptionId;
        $this->resolvedBy = $resolvedBy;
        $this->resolutionNotes = $resolutionNotes;
        $this->resolvedAt = $resolvedAt;
        $this->createdAt = $createdAt;
    }
}

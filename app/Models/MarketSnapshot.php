<?php

declare(strict_types=1);

namespace App\Models;

final class MarketSnapshot
{
    public ?int $id;
    public int $marketId;
    public string $snapshotJson;
    public ?string $createdAt;

    public function __construct(
        ?int $id,
        int $marketId,
        string $snapshotJson,
        ?string $createdAt = null
    ) {
        $this->id = $id;
        $this->marketId = $marketId;
        $this->snapshotJson = $snapshotJson;
        $this->createdAt = $createdAt;
    }
}

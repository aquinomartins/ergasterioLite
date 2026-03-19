<?php

declare(strict_types=1);

namespace App\Models;

final class MarketOption
{
    public ?int $id;
    public int $marketId;
    public string $optionType;
    public ?int $artworkId;
    public ?int $artistId;
    public string $label;
    public float $weightValue;
    public float $probabilityValue;
    public int $sortOrder;
    public ?string $createdAt;
    public ?string $updatedAt;

    public function __construct(
        ?int $id,
        int $marketId,
        string $optionType,
        ?int $artworkId,
        ?int $artistId,
        string $label,
        float $weightValue,
        float $probabilityValue,
        int $sortOrder,
        ?string $createdAt = null,
        ?string $updatedAt = null
    ) {
        $this->id = $id;
        $this->marketId = $marketId;
        $this->optionType = $optionType;
        $this->artworkId = $artworkId;
        $this->artistId = $artistId;
        $this->label = $label;
        $this->weightValue = $weightValue;
        $this->probabilityValue = $probabilityValue;
        $this->sortOrder = $sortOrder;
        $this->createdAt = $createdAt;
        $this->updatedAt = $updatedAt;
    }
}

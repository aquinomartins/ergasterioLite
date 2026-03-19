<?php

declare(strict_types=1);

namespace App\Models;

final class Market
{
    public ?int $id;
    public string $title;
    public string $slug;
    public string $description;
    public string $marketType;
    public string $status;
    public string $resolutionMode;
    public ?string $opensAt;
    public string $closesAt;
    public ?int $resolvedOptionId;
    public int $createdBy;
    public ?string $createdAt;
    public ?string $updatedAt;

    public function __construct(
        ?int $id,
        string $title,
        string $slug,
        string $description,
        string $marketType,
        string $status,
        string $resolutionMode,
        ?string $opensAt,
        string $closesAt,
        ?int $resolvedOptionId,
        int $createdBy,
        ?string $createdAt = null,
        ?string $updatedAt = null
    ) {
        $this->id = $id;
        $this->title = $title;
        $this->slug = $slug;
        $this->description = $description;
        $this->marketType = $marketType;
        $this->status = $status;
        $this->resolutionMode = $resolutionMode;
        $this->opensAt = $opensAt;
        $this->closesAt = $closesAt;
        $this->resolvedOptionId = $resolvedOptionId;
        $this->createdBy = $createdBy;
        $this->createdAt = $createdAt;
        $this->updatedAt = $updatedAt;
    }
}

<?php

declare(strict_types=1);

namespace App\Models;

final class Payout
{
    public ?int $id;
    public int $userId;
    public int $marketId;
    public ?int $positionId;
    public int $optionId;
    public float $sharesAmount;
    public float $grossAmount;
    public float $feeAmount;
    public float $netAmount;
    public string $status;
    public ?string $createdAt;

    public function __construct(
        ?int $id,
        int $userId,
        int $marketId,
        ?int $positionId,
        int $optionId,
        float $sharesAmount,
        float $grossAmount,
        float $feeAmount,
        float $netAmount,
        string $status = 'pending',
        ?string $createdAt = null
    ) {
        $this->id = $id;
        $this->userId = $userId;
        $this->marketId = $marketId;
        $this->positionId = $positionId;
        $this->optionId = $optionId;
        $this->sharesAmount = $sharesAmount;
        $this->grossAmount = $grossAmount;
        $this->feeAmount = $feeAmount;
        $this->netAmount = $netAmount;
        $this->status = $status;
        $this->createdAt = $createdAt;
    }
}

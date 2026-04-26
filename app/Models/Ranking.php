<?php

declare(strict_types=1);

namespace App\Models;

final class Ranking
{
    public ?int $id;
    public int $userId;
    public float $totalPayoff;
    public int $totalMarketsParticipated;
    public int $totalMarketsWon;
    public float $reputationScore;
    public ?string $updatedAt;

    public function __construct(
        ?int $id,
        int $userId,
        float $totalPayoff = 0.0,
        int $totalMarketsParticipated = 0,
        int $totalMarketsWon = 0,
        float $reputationScore = 0.0,
        ?string $updatedAt = null
    ) {
        $this->id = $id;
        $this->userId = $userId;
        $this->totalPayoff = $totalPayoff;
        $this->totalMarketsParticipated = $totalMarketsParticipated;
        $this->totalMarketsWon = $totalMarketsWon;
        $this->reputationScore = $reputationScore;
        $this->updatedAt = $updatedAt;
    }
}

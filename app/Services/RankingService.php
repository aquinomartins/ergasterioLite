<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\RankingRepository;

final class RankingService
{
    private RankingRepository $rankings;

    public function __construct()
    {
        $this->rankings = new RankingRepository();
    }

    public function leaderboard(int $limit = 50): array
    {
        return $this->rankings->getLeaderboard($limit);
    }
}

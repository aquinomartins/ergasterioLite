<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Services\RankingService;

final class RankingController extends Controller
{
    private RankingService $service;

    public function __construct()
    {
        $this->service = new RankingService();
    }

    public function index(): void
    {
        $this->view('rankings.index', [
            'pageTitle' => 'Ranking',
            'leaderboard' => $this->service->leaderboard(),
        ]);
    }
}

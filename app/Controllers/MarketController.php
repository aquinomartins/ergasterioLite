<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;

final class MarketController extends Controller
{
    public function index(): void
    {
        http_response_code(501);
        echo 'Módulo de mercados será implementado futuramente.';
    }
}

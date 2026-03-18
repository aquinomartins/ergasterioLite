<?php

namespace App\Http\Controllers;

use App\Domain\Artists\Models\Artist;
use App\Domain\Artworks\Models\Artwork;
use App\Domain\Markets\Models\Market;
use Illuminate\Contracts\View\View;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        return view('dashboard', [
            'artistCount' => Artist::query()->count(),
            'artworkCount' => Artwork::query()->count(),
            'marketCount' => Market::query()->count(),
        ]);
    }
}

<?php

namespace App\Http\Controllers\Admin;

use App\Domain\Artists\Models\Artist;
use App\Domain\Artworks\Models\Artwork;
use App\Domain\Markets\Models\Market;
use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;

class AdminDashboardController extends Controller
{
    public function __invoke(): View
    {
        return view('admin.dashboard', [
            'artists' => Artist::query()->latest()->limit(5)->get(),
            'artworks' => Artwork::query()->with('artist')->latest()->limit(5)->get(),
            'markets' => Market::query()->withCount('options')->latest()->limit(5)->get(),
        ]);
    }
}

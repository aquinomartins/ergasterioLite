<?php

namespace App\Http\Controllers;

use App\Domain\Artists\Models\Artist;
use App\Domain\Artworks\Models\Artwork;
use App\Domain\Markets\Models\Market;
use Illuminate\Contracts\View\View;

class HomeController extends Controller
{
    public function __invoke(): View
    {
        return view('home', [
            'artists' => Artist::query()->where('status', 'published')->latest()->limit(3)->get(),
            'artworks' => Artwork::query()->where('status', 'published')->latest()->limit(3)->get(),
            'markets' => Market::query()->whereIn('status', ['open', 'closed', 'resolved'])->latest()->limit(3)->get(),
        ]);
    }
}

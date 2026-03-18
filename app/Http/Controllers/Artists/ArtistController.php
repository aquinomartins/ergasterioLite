<?php

namespace App\Http\Controllers\Artists;

use App\Domain\Artists\Models\Artist;
use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;

class ArtistController extends Controller
{
    public function index(): View
    {
        return view('artists.index', [
            'artists' => Artist::query()->where('status', 'published')->orderBy('display_name')->paginate(12),
        ]);
    }

    public function show(Artist $artist): View
    {
        $this->authorize('view', $artist);

        return view('artists.show', [
            'artist' => $artist->load('artworks'),
        ]);
    }
}

<?php

namespace App\Http\Controllers\Artworks;

use App\Domain\Artworks\Models\Artwork;
use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;

class ArtworkController extends Controller
{
    public function index(): View
    {
        return view('artworks.index', [
            'artworks' => Artwork::query()->with('artist')->where('status', 'published')->latest()->paginate(12),
        ]);
    }

    public function show(Artwork $artwork): View
    {
        $this->authorize('view', $artwork);

        return view('artworks.show', [
            'artwork' => $artwork->load('artist'),
        ]);
    }
}

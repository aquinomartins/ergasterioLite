<?php

namespace App\Http\Controllers\Admin;

use App\Domain\Artists\Models\Artist;
use App\Domain\Artworks\Models\Artwork;
use App\Http\Controllers\Controller;
use App\Http\Requests\Artworks\StoreArtworkRequest;
use App\Http\Requests\Artworks\UpdateArtworkRequest;
use App\Services\ArtworkImageService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Str;

class AdminArtworkController extends Controller
{
    public function index(): View
    {
        $this->authorize('viewAny', Artwork::class);

        return view('admin.artworks.index', [
            'artworks' => Artwork::query()->with('artist')->latest()->paginate(20),
        ]);
    }

    public function create(): View
    {
        $this->authorize('create', Artwork::class);

        return view('admin.artworks.form', [
            'artwork' => new Artwork(),
            'artists' => Artist::query()->orderBy('display_name')->get(),
            'action' => route('admin.artworks.store'),
            'method' => 'POST',
        ]);
    }

    public function store(StoreArtworkRequest $request, ArtworkImageService $imageService): RedirectResponse
    {
        Artwork::query()->create([
            ...$request->safe()->except('image'),
            'slug' => $request->validated('slug') ?: Str::slug($request->validated('title')),
            'image_path' => $imageService->store($request->file('image')),
        ]);

        return redirect()->route('admin.artworks.index')->with('status', 'Obra criada com sucesso.');
    }

    public function edit(Artwork $artwork): View
    {
        $this->authorize('update', $artwork);

        return view('admin.artworks.form', [
            'artwork' => $artwork,
            'artists' => Artist::query()->orderBy('display_name')->get(),
            'action' => route('admin.artworks.update', $artwork),
            'method' => 'PATCH',
        ]);
    }

    public function update(UpdateArtworkRequest $request, Artwork $artwork, ArtworkImageService $imageService): RedirectResponse
    {
        $artwork->update([
            ...$request->safe()->except('image'),
            'slug' => $request->validated('slug') ?: Str::slug($request->validated('title')),
            'image_path' => $imageService->replace($artwork->image_path, $request->file('image')),
        ]);

        return redirect()->route('admin.artworks.index')->with('status', 'Obra atualizada com sucesso.');
    }
}

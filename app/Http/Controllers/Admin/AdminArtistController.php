<?php

namespace App\Http\Controllers\Admin;

use App\Domain\Artists\Models\Artist;
use App\Http\Controllers\Controller;
use App\Http\Requests\Artists\StoreArtistRequest;
use App\Http\Requests\Artists\UpdateArtistRequest;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Str;

class AdminArtistController extends Controller
{
    public function index(): View
    {
        $this->authorize('viewAny', Artist::class);

        return view('admin.artists.index', [
            'artists' => Artist::query()->with('user')->latest()->paginate(20),
        ]);
    }

    public function create(): View
    {
        $this->authorize('create', Artist::class);

        return view('admin.artists.form', [
            'artist' => new Artist(),
            'users' => User::query()->orderBy('name')->get(),
            'action' => route('admin.artists.store'),
            'method' => 'POST',
        ]);
    }

    public function store(StoreArtistRequest $request): RedirectResponse
    {
        Artist::query()->create([
            ...$request->validated(),
            'slug' => $request->validated('slug') ?: Str::slug($request->validated('display_name')),
        ]);

        return redirect()->route('admin.artists.index')->with('status', 'Artista criado com sucesso.');
    }

    public function edit(Artist $artist): View
    {
        $this->authorize('update', $artist);

        return view('admin.artists.form', [
            'artist' => $artist,
            'users' => User::query()->orderBy('name')->get(),
            'action' => route('admin.artists.update', $artist),
            'method' => 'PATCH',
        ]);
    }

    public function update(UpdateArtistRequest $request, Artist $artist): RedirectResponse
    {
        $artist->update([
            ...$request->validated(),
            'slug' => $request->validated('slug') ?: Str::slug($request->validated('display_name')),
        ]);

        return redirect()->route('admin.artists.index')->with('status', 'Artista atualizado com sucesso.');
    }
}

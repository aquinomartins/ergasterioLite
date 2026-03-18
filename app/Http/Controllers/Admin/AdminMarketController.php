<?php

namespace App\Http\Controllers\Admin;

use App\Domain\Artists\Models\Artist;
use App\Domain\Artworks\Models\Artwork;
use App\Domain\Markets\Models\Market;
use App\Http\Controllers\Controller;
use App\Http\Requests\Markets\StoreMarketRequest;
use App\Http\Requests\Markets\UpdateMarketRequest;
use App\Services\MarketService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Str;

class AdminMarketController extends Controller
{
    public function index(): View
    {
        $this->authorize('viewAny', Market::class);

        return view('admin.markets.index', [
            'markets' => Market::query()->withCount('options')->latest()->paginate(20),
        ]);
    }

    public function create(): View
    {
        $this->authorize('create', Market::class);

        return view('admin.markets.form', [
            'market' => new Market(),
            'artists' => Artist::query()->orderBy('display_name')->get(),
            'artworks' => Artwork::query()->orderBy('title')->get(),
            'action' => route('admin.markets.store'),
            'method' => 'POST',
        ]);
    }

    public function store(StoreMarketRequest $request, MarketService $marketService): RedirectResponse
    {
        $marketService->create([
            ...$request->validated(),
            'slug' => $request->validated('slug') ?: Str::slug($request->validated('title')),
            'created_by' => $request->user()->id,
        ]);

        return redirect()->route('admin.markets.index')->with('status', 'Mercado criado com sucesso.');
    }

    public function edit(Market $market): View
    {
        $this->authorize('update', $market);

        return view('admin.markets.form', [
            'market' => $market->load('options'),
            'artists' => Artist::query()->orderBy('display_name')->get(),
            'artworks' => Artwork::query()->orderBy('title')->get(),
            'action' => route('admin.markets.update', $market),
            'method' => 'PATCH',
        ]);
    }

    public function update(UpdateMarketRequest $request, Market $market, MarketService $marketService): RedirectResponse
    {
        $marketService->update($market, [
            ...$request->validated(),
            'slug' => $request->validated('slug') ?: Str::slug($request->validated('title')),
            'created_by' => $market->created_by,
        ]);

        return redirect()->route('admin.markets.index')->with('status', 'Mercado atualizado com sucesso.');
    }
}

<?php

namespace App\Http\Controllers\Markets;

use App\Domain\Markets\Models\Market;
use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;

class MarketController extends Controller
{
    public function index(): View
    {
        return view('markets.index', [
            'markets' => Market::query()->withCount('options')->whereIn('status', ['open', 'closed', 'resolved'])->latest()->paginate(12),
        ]);
    }

    public function show(Market $market): View
    {
        $this->authorize('view', $market);

        return view('markets.show', [
            'market' => $market->load('options.artwork', 'options.artist', 'creator'),
        ]);
    }
}

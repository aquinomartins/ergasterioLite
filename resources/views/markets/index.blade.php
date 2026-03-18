@extends('layouts.app')

@section('content')
    <section>
        <h1 class="text-3xl font-bold">Mercados</h1>
        <div class="mt-6 grid gap-4 md:grid-cols-2 xl:grid-cols-3">
            @foreach ($markets as $market)
                <a href="{{ route('markets.show', $market) }}" class="rounded-xl border border-slate-800 bg-slate-900 p-5 hover:border-sky-500">
                    <div class="text-sm uppercase tracking-wide text-sky-300">{{ $market->status }}</div>
                    <h2 class="mt-2 text-xl font-semibold">{{ $market->title }}</h2>
                    <p class="mt-2 text-sm text-slate-400">{{ $market->options_count }} opções · fecha em {{ $market->closes_at?->format('d/m/Y H:i') }}</p>
                </a>
            @endforeach
        </div>
        <div class="mt-6">{{ $markets->links() }}</div>
    </section>
@endsection

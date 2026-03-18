@extends('layouts.app')

@section('content')
    <section>
        <h1 class="text-3xl font-bold">Dashboard</h1>
        <div class="mt-6 grid gap-4 md:grid-cols-3">
            <div class="rounded-xl border border-slate-800 bg-slate-900 p-6">
                <div class="text-sm text-slate-400">Artistas</div>
                <div class="mt-2 text-3xl font-semibold">{{ $artistCount }}</div>
            </div>
            <div class="rounded-xl border border-slate-800 bg-slate-900 p-6">
                <div class="text-sm text-slate-400">Obras</div>
                <div class="mt-2 text-3xl font-semibold">{{ $artworkCount }}</div>
            </div>
            <div class="rounded-xl border border-slate-800 bg-slate-900 p-6">
                <div class="text-sm text-slate-400">Mercados</div>
                <div class="mt-2 text-3xl font-semibold">{{ $marketCount }}</div>
            </div>
        </div>
    </section>
@endsection

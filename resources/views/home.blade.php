@extends('layouts.app')

@section('content')
    <section class="space-y-10">
        <div class="rounded-2xl border border-slate-800 bg-slate-900 p-8">
            <p class="text-sm uppercase tracking-[0.2em] text-sky-300">Mercado preditivo em arte</p>
            <h1 class="mt-3 text-4xl font-bold">Fundação do Ergastério Lite pronta para o MVP.</h1>
            <p class="mt-4 max-w-3xl text-slate-300">Monólito modular simples em convenção Laravel, preparado para artistas, obras, mercados e operação em hospedagem compartilhada.</p>
        </div>

        <div class="grid gap-8 lg:grid-cols-3">
            <div>
                <h2 class="mb-4 text-xl font-semibold">Artistas em destaque</h2>
                @include('partials.public-list', ['items' => $artists, 'empty' => 'Nenhum artista publicado ainda.'])
            </div>
            <div>
                <h2 class="mb-4 text-xl font-semibold">Obras recentes</h2>
                @include('partials.public-list', ['items' => $artworks, 'empty' => 'Nenhuma obra publicada ainda.'])
            </div>
            <div>
                <h2 class="mb-4 text-xl font-semibold">Mercados ativos</h2>
                @include('partials.public-list', ['items' => $markets, 'empty' => 'Nenhum mercado disponível ainda.'])
            </div>
        </div>
    </section>
@endsection

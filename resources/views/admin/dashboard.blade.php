@extends('layouts.app')

@section('content')
    <section class="space-y-8">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm uppercase tracking-[0.2em] text-sky-300">Admin</p>
                <h1 class="text-3xl font-bold">Painel administrativo</h1>
            </div>
        </div>
        <div class="grid gap-8 lg:grid-cols-3">
            <div>
                <h2 class="mb-4 text-xl font-semibold">Artistas</h2>
                @include('partials.public-list', ['items' => $artists, 'empty' => 'Sem artistas cadastrados.'])
            </div>
            <div>
                <h2 class="mb-4 text-xl font-semibold">Obras</h2>
                @include('partials.public-list', ['items' => $artworks, 'empty' => 'Sem obras cadastradas.'])
            </div>
            <div>
                <h2 class="mb-4 text-xl font-semibold">Mercados</h2>
                @include('partials.public-list', ['items' => $markets, 'empty' => 'Sem mercados cadastrados.'])
            </div>
        </div>
    </section>
@endsection

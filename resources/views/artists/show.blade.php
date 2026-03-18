@extends('layouts.app')

@section('content')
    <article class="space-y-6">
        <div class="rounded-2xl border border-slate-800 bg-slate-900 p-8">
            <p class="text-sm uppercase tracking-wide text-sky-300">Artista</p>
            <h1 class="mt-2 text-4xl font-bold">{{ $artist->display_name }}</h1>
            <p class="mt-4 text-slate-300">{{ $artist->biography }}</p>
        </div>

        <section>
            <h2 class="text-2xl font-semibold">Obras relacionadas</h2>
            <div class="mt-4 grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                @forelse ($artist->artworks as $artwork)
                    <a href="{{ route('artworks.show', $artwork) }}" class="rounded-xl border border-slate-800 bg-slate-900 p-4">
                        <div class="font-medium">{{ $artwork->title }}</div>
                        <div class="text-sm text-slate-400">{{ $artwork->artwork_type }}</div>
                    </a>
                @empty
                    <p class="text-sm text-slate-400">Este artista ainda não possui obras públicas.</p>
                @endforelse
            </div>
        </section>
    </article>
@endsection

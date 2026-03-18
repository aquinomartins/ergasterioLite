@extends('layouts.app')

@section('content')
    <article class="grid gap-8 lg:grid-cols-[2fr,1fr]">
        <div class="rounded-2xl border border-slate-800 bg-slate-900 p-8">
            <p class="text-sm uppercase tracking-wide text-sky-300">Obra</p>
            <h1 class="mt-2 text-4xl font-bold">{{ $artwork->title }}</h1>
            <p class="mt-4 text-slate-300">{{ $artwork->description }}</p>
            @if ($artwork->image_path)
                <div class="mt-6 rounded-xl border border-slate-800 bg-slate-950 p-3">
                    <img src="{{ Storage::disk('public')->url($artwork->image_path) }}" alt="{{ $artwork->title }}" class="w-full rounded-lg object-cover" />
                </div>
            @endif
        </div>
        <aside class="rounded-2xl border border-slate-800 bg-slate-900 p-8">
            <div class="text-sm text-slate-400">Artista</div>
            <a href="{{ route('artists.show', $artwork->artist) }}" class="mt-2 block text-xl font-semibold">{{ $artwork->artist->display_name }}</a>
            <div class="mt-6 text-sm text-slate-400">Tipo</div>
            <div class="mt-1 font-medium">{{ $artwork->artwork_type }}</div>
            <div class="mt-6 text-sm text-slate-400">Técnica</div>
            <div class="mt-1 font-medium">{{ $artwork->medium ?: 'Não informada' }}</div>
        </aside>
    </article>
@endsection

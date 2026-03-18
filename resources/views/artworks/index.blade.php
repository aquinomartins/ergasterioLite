@extends('layouts.app')

@section('content')
    <section>
        <h1 class="text-3xl font-bold">Obras</h1>
        <div class="mt-6 grid gap-4 md:grid-cols-2 xl:grid-cols-3">
            @foreach ($artworks as $artwork)
                <a href="{{ route('artworks.show', $artwork) }}" class="rounded-xl border border-slate-800 bg-slate-900 p-5 hover:border-sky-500">
                    <div class="text-sm text-slate-400">{{ $artwork->artist->display_name }}</div>
                    <h2 class="mt-1 text-xl font-semibold">{{ $artwork->title }}</h2>
                    <p class="mt-2 text-sm text-slate-400">{{ $artwork->artwork_type }} · {{ $artwork->medium }}</p>
                </a>
            @endforeach
        </div>
        <div class="mt-6">{{ $artworks->links() }}</div>
    </section>
@endsection

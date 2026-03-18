@extends('layouts.app')

@section('content')
    <section>
        <div class="flex items-center justify-between">
            <h1 class="text-3xl font-bold">Artistas</h1>
        </div>
        <div class="mt-6 grid gap-4 md:grid-cols-2 xl:grid-cols-3">
            @foreach ($artists as $artist)
                <a href="{{ route('artists.show', $artist) }}" class="rounded-xl border border-slate-800 bg-slate-900 p-5 hover:border-sky-500">
                    <h2 class="text-xl font-semibold">{{ $artist->display_name }}</h2>
                    <p class="mt-2 line-clamp-3 text-sm text-slate-400">{{ $artist->biography }}</p>
                </a>
            @endforeach
        </div>
        <div class="mt-6">{{ $artists->links() }}</div>
    </section>
@endsection

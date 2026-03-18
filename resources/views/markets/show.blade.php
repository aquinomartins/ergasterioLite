@extends('layouts.app')

@section('content')
    <article class="space-y-6">
        <div class="rounded-2xl border border-slate-800 bg-slate-900 p-8">
            <div class="text-sm uppercase tracking-wide text-sky-300">{{ $market->status }}</div>
            <h1 class="mt-2 text-4xl font-bold">{{ $market->title }}</h1>
            <p class="mt-4 max-w-3xl text-slate-300">{{ $market->description }}</p>
        </div>

        <section class="rounded-2xl border border-slate-800 bg-slate-900 p-8">
            <h2 class="text-2xl font-semibold">Opções do mercado</h2>
            <div class="mt-4 space-y-3">
                @foreach ($market->options as $option)
                    <div class="rounded-xl border border-slate-800 bg-slate-950 p-4">
                        <div class="font-medium">{{ $option->label }}</div>
                        <div class="mt-1 text-sm text-slate-400">
                            @if ($option->artist)
                                Artista: {{ $option->artist->display_name }}
                            @endif
                            @if ($option->artwork)
                                · Obra: {{ $option->artwork->title }}
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        </section>
    </article>
@endsection

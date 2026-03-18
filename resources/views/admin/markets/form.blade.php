@extends('layouts.app')

@section('content')
    @php
        $existingOptions = old('options', $market->exists ? $market->options->map(fn ($option) => [
            'label' => $option->label,
            'artist_id' => $option->artist_id,
            'artwork_id' => $option->artwork_id,
            'sort_order' => $option->sort_order,
        ])->toArray() : [
            ['label' => '', 'artist_id' => '', 'artwork_id' => '', 'sort_order' => 1],
            ['label' => '', 'artist_id' => '', 'artwork_id' => '', 'sort_order' => 2],
        ]);
    @endphp
    <section class="mx-auto max-w-4xl rounded-2xl border border-slate-800 bg-slate-900 p-8">
        <h1 class="text-2xl font-bold">{{ $market->exists ? 'Editar mercado' : 'Novo mercado' }}</h1>
        <form action="{{ $action }}" method="POST" class="mt-6 space-y-6">
            @csrf
            @if ($method !== 'POST') @method($method) @endif
            @include('partials.form.input', ['name' => 'title', 'label' => 'Título', 'value' => $market->title])
            @include('partials.form.input', ['name' => 'slug', 'label' => 'Slug', 'value' => $market->slug])
            @include('partials.form.textarea', ['name' => 'description', 'label' => 'Descrição', 'value' => $market->description])
            <div class="grid gap-4 md:grid-cols-2">
                <label class="block space-y-2">
                    <span class="text-sm font-medium">Tipo de mercado</span>
                    <select name="market_type" class="w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2">
                        <option value="multiple_choice" @selected(old('market_type', $market->market_type ?: 'multiple_choice') === 'multiple_choice')>Múltipla escolha</option>
                    </select>
                </label>
                <label class="block space-y-2">
                    <span class="text-sm font-medium">Status</span>
                    <select name="status" class="w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2">
                        @foreach (['draft', 'open', 'closed', 'resolved'] as $status)
                            <option value="{{ $status }}" @selected(old('status', $market->status ?: 'draft') === $status)>{{ ucfirst($status) }}</option>
                        @endforeach
                    </select>
                </label>
                @include('partials.form.input', ['name' => 'opens_at', 'label' => 'Abre em', 'type' => 'datetime-local', 'value' => optional($market->opens_at)->format('Y-m-d\TH:i')])
                @include('partials.form.input', ['name' => 'closes_at', 'label' => 'Fecha em', 'type' => 'datetime-local', 'value' => optional($market->closes_at)->format('Y-m-d\TH:i')])
            </div>
            <label class="block space-y-2">
                <span class="text-sm font-medium">Modo de resolução</span>
                <select name="resolution_mode" class="w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2">
                    <option value="manual" @selected(old('resolution_mode', $market->resolution_mode ?: 'manual') === 'manual')>Manual</option>
                </select>
            </label>

            <div class="space-y-4">
                <div>
                    <h2 class="text-xl font-semibold">Opções do mercado</h2>
                    <p class="text-sm text-slate-400">Defina ao menos duas opções de múltipla escolha.</p>
                </div>
                @foreach ($existingOptions as $index => $option)
                    <div class="grid gap-4 rounded-xl border border-slate-800 bg-slate-950 p-4 md:grid-cols-2">
                        @include('partials.form.input', ['name' => "options[$index][label]", 'label' => 'Rótulo', 'value' => $option['label'] ?? null])
                        @include('partials.form.input', ['name' => "options[$index][sort_order]", 'label' => 'Ordem', 'type' => 'number', 'value' => $option['sort_order'] ?? $index + 1])
                        <label class="block space-y-2">
                            <span class="text-sm font-medium">Artista relacionado</span>
                            <select name="options[{{ $index }}][artist_id]" class="w-full rounded-lg border border-slate-700 bg-slate-900 px-3 py-2">
                                <option value="">Nenhum</option>
                                @foreach ($artists as $artist)
                                    <option value="{{ $artist->id }}" @selected(($option['artist_id'] ?? null) == $artist->id)>{{ $artist->display_name }}</option>
                                @endforeach
                            </select>
                        </label>
                        <label class="block space-y-2">
                            <span class="text-sm font-medium">Obra relacionada</span>
                            <select name="options[{{ $index }}][artwork_id]" class="w-full rounded-lg border border-slate-700 bg-slate-900 px-3 py-2">
                                <option value="">Nenhuma</option>
                                @foreach ($artworks as $artwork)
                                    <option value="{{ $artwork->id }}" @selected(($option['artwork_id'] ?? null) == $artwork->id)>{{ $artwork->title }}</option>
                                @endforeach
                            </select>
                        </label>
                    </div>
                @endforeach
            </div>

            <button class="rounded-lg bg-sky-500 px-4 py-2 font-medium text-slate-950">Salvar mercado</button>
        </form>
    </section>
@endsection

@extends('layouts.app')

@section('content')
    <section class="mx-auto max-w-3xl rounded-2xl border border-slate-800 bg-slate-900 p-8">
        <h1 class="text-2xl font-bold">{{ $artwork->exists ? 'Editar obra' : 'Nova obra' }}</h1>
        <form action="{{ $action }}" method="POST" enctype="multipart/form-data" class="mt-6 space-y-4">
            @csrf
            @if ($method !== 'POST') @method($method) @endif
            <label class="block space-y-2">
                <span class="text-sm font-medium">Artista</span>
                <select name="artist_id" class="w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2">
                    @foreach ($artists as $artist)
                        <option value="{{ $artist->id }}" @selected(old('artist_id', $artwork->artist_id) == $artist->id)>{{ $artist->display_name }}</option>
                    @endforeach
                </select>
            </label>
            @include('partials.form.input', ['name' => 'title', 'label' => 'Título', 'value' => $artwork->title])
            @include('partials.form.input', ['name' => 'slug', 'label' => 'Slug', 'value' => $artwork->slug])
            @include('partials.form.textarea', ['name' => 'description', 'label' => 'Descrição', 'value' => $artwork->description])
            @include('partials.form.input', ['name' => 'artwork_type', 'label' => 'Tipo de obra', 'value' => $artwork->artwork_type])
            @include('partials.form.input', ['name' => 'medium', 'label' => 'Técnica / medium', 'value' => $artwork->medium])
            <label class="block space-y-2">
                <span class="text-sm font-medium">Imagem</span>
                <input type="file" name="image" class="w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2" />
            </label>
            <label class="block space-y-2">
                <span class="text-sm font-medium">Status</span>
                <select name="status" class="w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2">
                    @foreach (['draft' => 'Rascunho', 'published' => 'Publicado', 'archived' => 'Arquivado'] as $value => $label)
                        <option value="{{ $value }}" @selected(old('status', $artwork->status ?: 'draft') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </label>
            <button class="rounded-lg bg-sky-500 px-4 py-2 font-medium text-slate-950">Salvar obra</button>
        </form>
    </section>
@endsection

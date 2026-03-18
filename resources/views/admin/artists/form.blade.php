@extends('layouts.app')

@section('content')
    <section class="mx-auto max-w-3xl rounded-2xl border border-slate-800 bg-slate-900 p-8">
        <h1 class="text-2xl font-bold">{{ $artist->exists ? 'Editar artista' : 'Novo artista' }}</h1>
        <form action="{{ $action }}" method="POST" class="mt-6 space-y-4">
            @csrf
            @if ($method !== 'POST') @method($method) @endif
            <label class="block space-y-2">
                <span class="text-sm font-medium">Perfil vinculado</span>
                <select name="user_id" class="w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2">
                    <option value="">Sem vínculo</option>
                    @foreach ($users as $user)
                        <option value="{{ $user->id }}" @selected(old('user_id', $artist->user_id) == $user->id)>{{ $user->name }} ({{ $user->email }})</option>
                    @endforeach
                </select>
            </label>
            @include('partials.form.input', ['name' => 'display_name', 'label' => 'Nome público', 'value' => $artist->display_name])
            @include('partials.form.input', ['name' => 'slug', 'label' => 'Slug', 'value' => $artist->slug])
            @include('partials.form.textarea', ['name' => 'biography', 'label' => 'Biografia', 'value' => $artist->biography])
            <label class="block space-y-2">
                <span class="text-sm font-medium">Status</span>
                <select name="status" class="w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2">
                    @foreach (['draft' => 'Rascunho', 'published' => 'Publicado', 'archived' => 'Arquivado'] as $value => $label)
                        <option value="{{ $value }}" @selected(old('status', $artist->status ?: 'draft') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </label>
            <button class="rounded-lg bg-sky-500 px-4 py-2 font-medium text-slate-950">Salvar artista</button>
        </form>
    </section>
@endsection

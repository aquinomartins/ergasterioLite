@extends('layouts.app')

@section('content')
    <section class="mx-auto max-w-3xl rounded-2xl border border-slate-800 bg-slate-900 p-8">
        <h1 class="text-2xl font-bold">Perfil</h1>
        <form action="{{ route('profile.update') }}" method="POST" class="mt-6 space-y-4">
            @csrf
            @method('PATCH')
            @include('partials.form.input', ['name' => 'name', 'label' => 'Nome da conta', 'value' => old('name', $user->name)])
            @include('partials.form.input', ['name' => 'display_name', 'label' => 'Nome público', 'value' => old('display_name', $user->profile?->display_name)])
            @include('partials.form.textarea', ['name' => 'bio', 'label' => 'Bio', 'value' => old('bio', $user->profile?->bio)])
            <div class="text-sm text-slate-400">Papéis: {{ $user->roles->pluck('name')->join(', ') ?: 'Nenhum papel atribuído' }}</div>
            <button class="rounded-lg bg-sky-500 px-4 py-2 font-medium text-slate-950">Salvar perfil</button>
        </form>
    </section>
@endsection

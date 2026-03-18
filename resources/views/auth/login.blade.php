@extends('layouts.app')

@section('content')
    <section class="mx-auto max-w-lg rounded-2xl border border-slate-800 bg-slate-900 p-8">
        <h1 class="text-2xl font-bold">Entrar</h1>
        <form action="{{ route('login.store') }}" method="POST" class="mt-6 space-y-4">
            @csrf
            @include('partials.form.input', ['name' => 'email', 'label' => 'E-mail', 'type' => 'email'])
            @include('partials.form.input', ['name' => 'password', 'label' => 'Senha', 'type' => 'password'])
            <button class="rounded-lg bg-sky-500 px-4 py-2 font-medium text-slate-950">Acessar</button>
        </form>
    </section>
@endsection

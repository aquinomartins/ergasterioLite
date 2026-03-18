@extends('layouts.app')

@section('content')
    <section>
        <div class="flex items-center justify-between">
            <h1 class="text-3xl font-bold">Gerenciar artistas</h1>
            <a href="{{ route('admin.artists.create') }}" class="rounded-lg bg-sky-500 px-4 py-2 font-medium text-slate-950">Novo artista</a>
        </div>
        <div class="mt-6 overflow-hidden rounded-xl border border-slate-800">
            <table class="min-w-full divide-y divide-slate-800 bg-slate-900 text-left text-sm">
                <thead class="bg-slate-950 text-slate-400">
                    <tr><th class="px-4 py-3">Nome</th><th class="px-4 py-3">Perfil</th><th class="px-4 py-3">Status</th><th class="px-4 py-3"></th></tr>
                </thead>
                <tbody class="divide-y divide-slate-800">
                    @foreach ($artists as $artist)
                        <tr>
                            <td class="px-4 py-3">{{ $artist->display_name }}</td>
                            <td class="px-4 py-3">{{ $artist->user?->email ?: 'Sem vínculo' }}</td>
                            <td class="px-4 py-3">{{ $artist->status }}</td>
                            <td class="px-4 py-3 text-right"><a href="{{ route('admin.artists.edit', $artist) }}">Editar</a></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="mt-6">{{ $artists->links() }}</div>
    </section>
@endsection

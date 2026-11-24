@extends('layouts.app')

@section('title', 'Lista de Jogadores')

@section('content')
<div class="py-12">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
            
            <h2 class="text-xl font-bold text-gray-800 mb-4">Jogadores Cadastrados</h2>
            
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">ID</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Nome</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">E-mail</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Perfil</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($jogadores as $jogador)
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap">{{ $jogador->id_jogador }}</td>
                            <td class="px-6 py-4 whitespace-nowrap">{{ $jogador->nome }}</td>
                            <td class="px-6 py-4 whitespace-nowrap">{{ $jogador->email }}</td>
                            <td class="px-6 py-4 whitespace-nowrap capitalize">{{ $jogador->tipo_perfil }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-6 py-4 text-center text-gray-500">Nenhum jogador cadastrado.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>

        </div>
    </div>
</div>
@endsection
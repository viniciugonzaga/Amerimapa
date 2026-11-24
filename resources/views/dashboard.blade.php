@extends('layouts.app')

@section('title', 'Painel')

@section('content')
<div class="py-12">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        
        <h2 class="text-2xl font-bold text-gray-800 mb-6">Painel do AmeriMapa</h2>

        <!-- Cards de Estatísticas (Dados vindos do Banco) -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
            <div class="bg-blue-600 text-white p-6 rounded-lg shadow-lg">
                <h3 class="text-lg font-bold">Total de Jogadores</h3>
                <p class="text-4xl mt-2">{{ $totalJogadores }}</p>
            </div>
            <div class="bg-green-600 text-white p-6 rounded-lg shadow-lg">
                <h3 class="text-lg font-bold">Partidas Registradas</h3>
                <p class="text-4xl mt-2">{{ $totalPartidas }}</p>
            </div>
            <div class="bg-purple-600 text-white p-6 rounded-lg shadow-lg">
                <h3 class="text-lg font-bold">Regiões no Mapa</h3>
                <p class="text-4xl mt-2">{{ $totalRegioes }}</p>
            </div>
        </div>

        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
            <h3 class="text-lg font-semibold mb-4">Gerenciamento</h3>
            <a href="{{ route('jogadores.index') }}" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700 transition">
                Ver Lista de Jogadores
            </a>
        </div>

    </div>
</div>
@endsection
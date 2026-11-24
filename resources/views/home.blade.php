@extends('layouts.app')

@section('title', 'Bem-vindo ao AmeriMapa')

@section('content')
<div class="container mx-auto px-4 py-10 grid lg:grid-cols-2 gap-10 items-center">
    <!-- Coluna Esquerda -->
    <div>
        <div class="flex gap-4 text-sm font-semibold text-blue-800 mb-4">
            <span>☀️ Explore</span>
            <span>🔍 Descubra</span>
            <span>📚 Aprenda</span>
        </div>
        <h1 class="text-5xl font-extrabold text-gray-900 leading-tight">
            Bem-vindo ao <span class="text-blue-800">AmeriMapa!</span>
        </h1>
        <p class="mt-4 text-lg text-gray-600">
            Uma plataforma interativa que une tecnologia, educação e inclusão para tornar o aprendizado de geografia mais acessível, divertido e significativo para todos.
        </p>
        <div class="mt-6 grid grid-cols-2 md:grid-cols-4 gap-4 text-center text-sm">
            <div class="p-3 bg-blue-50 rounded-lg">🌎 Explore a América</div>
            <div class="p-3 bg-purple-50 rounded-lg">🎮 Atividades</div>
            <div class="p-3 bg-yellow-50 rounded-lg">🏆 Conquistas</div>
            <div class="p-3 bg-pink-50 rounded-lg">♿ Acessível</div>
        </div>
        <!-- Imagem Principal (Certifique-se de ter a imagem em public/images) -->
        <img src="{{ asset('images/hero-map.png') }}" alt="Mapa interativo" class="mt-8 rounded-xl shadow-lg w-full">
    </div>

    <!-- Coluna Direita: Login -->
    <div class="bg-white p-8 rounded-2xl shadow-xl border border-gray-100">
        <h2 class="text-2xl font-bold text-gray-800 mb-2">Acesse sua conta</h2>
        <p class="text-sm text-gray-500 mb-6">Escolha como deseja entrar no AmeriMapa.</p>

        <div class="grid grid-cols-2 gap-3 mb-6">
            <button class="flex flex-col items-center p-3 border rounded-xl hover:bg-blue-50">
                <span class="text-2xl">👩‍🎓</span>
                <span class="font-semibold text-sm">Aluno</span>
            </button>
            <button class="flex flex-col items-center p-3 border rounded-xl hover:bg-green-50">
                <span class="text-2xl">👨‍🏫</span>
                <span class="font-semibold text-sm">Professor</span>
            </button>
        </div>

        <form method="POST" action="{{ route('login') }}">
            @csrf
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700">E-mail</label>
                    <input type="email" name="email" required class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-800 focus:ring-blue-800">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Senha</label>
                    <input type="password" name="password" required class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-800 focus:ring-blue-800">
                </div>
                <div class="flex items-center justify-between">
                    <label class="flex items-center gap-2 text-sm">
                        <input type="checkbox" name="remember" class="rounded border-gray-300">
                        Lembrar de mim
                    </label>
                </div>
                <button type="submit" class="w-full bg-blue-800 text-white py-2 rounded-lg font-semibold hover:bg-blue-900 transition">
                    Entrar →
                </button>
            </div>
        </form>

        <div class="mt-6 text-center">
            <p class="text-sm text-gray-500">ou</p>
            <a href="{{ route('register') }}" class="mt-2 inline-block w-full border border-gray-300 py-2 rounded-lg text-sm font-semibold hover:bg-gray-50">
                Criar uma conta
            </a>
        </div>
    </div>
</div>
@endsection
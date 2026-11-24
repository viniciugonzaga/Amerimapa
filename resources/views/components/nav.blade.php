<nav class="bg-white shadow-sm">
    <div class="container mx-auto px-4 py-3 flex items-center justify-between">
        <a href="{{ route('home') }}" class="flex items-center gap-2">
            <img src="{{ asset('images/Amerimapa.png') }}" alt="AmeriMapa" class="h-10">
        </a>
        <div class="hidden md:flex gap-6 text-sm font-medium text-gray-600">
            <a href="#" class="hover:text-blue-800">Explore</a>
            <a href="#" class="hover:text-blue-800">Descubra</a>
            <a href="#" class="hover:text-blue-800">Aprenda</a>
        </div>
        <div>
            @auth
                <a href="{{ route('dashboard') }}" class="text-sm text-blue-800 font-semibold">Meu Painel</a>
            @else
                <a href="{{ route('login') }}" class="text-sm text-blue-800 font-semibold">Entrar</a>
            @endauth
        </div>
    </div>
</nav>
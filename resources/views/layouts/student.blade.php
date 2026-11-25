@extends('layouts.base')

@section('body')
<header class="topbar">
  <div class="container">
    <x-logo />
    <div class="row">
      @yield('top-extra')
      <div class="usermenu" data-dropdown>
        <button type="button" aria-haspopup="true" aria-expanded="false">
          <x-avatar :user="$me" />
          <span class="who">{{ $me?->displayName() ?? 'Visitante' }}<small>{{ $me?->roleLabel() ?? 'Sem progresso salvo' }}</small></span>
          <x-icon name="chevron-down" />
        </button>
        <div class="dropdown" role="menu">
          @if ($me)
            @if ($me->isStaff())
              <a href="{{ route('teacher.dashboard') }}"><x-icon name="chart" /> Painel do professor</a>
            @endif
            <a href="{{ route('profile') }}"><x-icon name="user" /> Meu perfil</a>
            <a href="{{ route('profile') }}#acessibilidade"><x-icon name="accessibility" /> Acessibilidade</a>
          @else
            <a href="{{ route('register') }}"><x-icon name="plus-circle" /> Criar conta e salvar progresso</a>
          @endif
          <form method="POST" action="{{ route('logout') }}">@csrf<button type="submit"><x-icon name="logout" /> Sair</button></form>
        </div>
      </div>
    </div>
  </div>
</header>

<main class="page" id="conteudo">
  <div class="container">
    @if (session('status'))<div class="alert success"><x-icon name="check-circle" /> {{ session('status') }}</div>@endif
    @if (!$me)<div class="alert info"><x-icon name="help" /> Você está jogando como visitante: seu progresso não será salvo. <a href="{{ route('register') }}">Criar conta</a></div>@endif
    @yield('content')
  </div>
</main>

<nav class="bottomnav" aria-label="Navegação principal">
  <div class="container">
    @php
        $nav = [
            ['student.home', 'home', 'Início', 'inicio'],
            ['activities', 'clipboard', 'Atividade', 'atividades*'],
            ['progress', 'chart', 'Progresso', 'progresso'],
            ['achievements', 'trophy', 'Conquistas', 'conquistas'],
            ['profile', 'user', 'Perfil', 'perfil'],
        ];
    @endphp
    @foreach ($nav as [$route, $icon, $label, $pattern])
      @php($active = request()->is($pattern) || ($pattern === 'atividades*' && (request()->is('jogar') || request()->is('explorar') || request()->is('partidas/*'))))
      <a href="{{ $me || $route === 'activities' ? route($route) : route('register') }}" class="{{ $active ? 'active' : '' }}" @if($active) aria-current="page" @endif>
        <x-icon :name="$icon" /> {{ $label }}
      </a>
    @endforeach
  </div>
</nav>
@endsection

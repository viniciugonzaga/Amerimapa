@extends('layouts.base')

@section('body')
<div class="t-shell">
  <aside class="t-side" id="t-side">
    <x-logo />
    <nav class="t-nav" aria-label="Menu do professor">
      @foreach ([
        ['teacher.dashboard', 'home', 'Visão geral', 'professor'],
        ['teacher.classes.index', 'users', 'Turmas e alunos', 'professor/turmas*'],
        ['teacher.activities.index', 'map', 'Atividades do mapa', 'professor/atividades*'],
        ['teacher.questions.index', 'help', 'Perguntas', 'professor/perguntas*'],
        ['teacher.regions.index', 'pin', 'Regiões e pinos', 'professor/regioes*'],
        ['teacher.performance', 'trophy', 'Desempenho e ranking', 'professor/desempenho*'],
        ['teacher.reports', 'file', 'Relatórios', 'professor/relatorios*'],
        ['teacher.device', 'cpu', 'Dispositivo IoT', 'professor/dispositivo*'],
      ] as [$route, $icon, $label, $pattern])
        <a href="{{ route($route) }}" class="{{ request()->is($pattern) ? 'active' : '' }}"><x-icon :name="$icon" /> {{ $label }}</a>
      @endforeach
    </nav>
    <div class="motto">
      <span class="handwrite">¡Grandes aprendizados começam com boas ferramentas!</span>
    </div>
  </aside>

  <div class="t-main">
    <header class="t-top">
      <button class="icon-btn t-menu-btn" type="button" data-toggle="#t-side" aria-label="Abrir menu"><x-icon name="menu" /></button>
      <form method="GET" action="{{ route('teacher.search') }}" role="search">
        <div class="input-icon"><x-icon name="search" /><input class="input" type="search" name="q" value="{{ request('q') }}" placeholder="Buscar por turma, aluno ou atividade..." aria-label="Buscar"></div>
      </form>
      <div class="grow"></div>
      <a class="icon-btn" href="{{ route('teacher.device') }}" aria-label="Dispositivo"><x-icon name="bell" /></a>
      <div class="usermenu" data-dropdown>
        <button type="button" aria-haspopup="true" aria-expanded="false">
          <x-avatar :user="$me" />
          <span class="who">{{ $me->nickname ?: $me->name }}<small>{{ $me->roleLabel() }}</small></span>
          <x-icon name="chevron-down" />
        </button>
        <div class="dropdown" role="menu">
          <a href="{{ route('profile') }}"><x-icon name="user" /> Meu perfil</a>
          <a href="{{ route('activities') }}"><x-icon name="play" /> Ver como aluno</a>
          <a href="{{ url('/docs/api') }}"><x-icon name="book" /> Documentação da API</a>
          <form method="POST" action="{{ route('logout') }}">@csrf<button type="submit"><x-icon name="logout" /> Sair</button></form>
        </div>
      </div>
    </header>
    <main class="t-content" id="conteudo">
      @if (session('status'))<div class="alert success"><x-icon name="check-circle" /> {{ session('status') }}</div>@endif
      @if ($errors->any())<div class="alert error"><x-icon name="x" /> {{ $errors->first() }}</div>@endif
      @yield('content')
    </main>
  </div>
</div>
@endsection

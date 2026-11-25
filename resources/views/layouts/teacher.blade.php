@extends('layouts.base')

@section('body')
<div class="t-shell">
  <aside class="t-side" id="t-side">
    <x-logo />
    <nav class="t-nav" aria-label="Menu do professor">
      @foreach (array_merge([
        ['teacher.dashboard', 'home', 'Visão geral', 'professor'],
        ['teacher.classes.index', 'users', 'Turmas e alunos', 'professor/turmas*'],
        ['teacher.activities.index', 'map', 'Atividades do mapa', 'professor/atividades*'],
        ['teacher.questions.index', 'help', 'Perguntas', 'professor/perguntas*'],
        ['teacher.regions.index', 'pin', 'Regiões e pinos', 'professor/regioes*'],
        ['teacher.performance', 'trophy', 'Desempenho e ranking', 'professor/desempenho*'],
        ['teacher.reports', 'file', 'Relatórios', 'professor/relatorios*'],
        ['teacher.device', 'cpu', 'Dispositivo IoT', 'professor/dispositivo*'],
      ], $me->role === \App\Models\User::ADMIN ? [['teacher.teachers.index', 'book', 'Professores', 'professor/professores*']] : []) as [$route, $icon, $label, $pattern])
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
      @php
          $notifications = app(\App\Services\StatsService::class)->notificationsFor($me);
          $seenAt = session('notifications_seen_at');
          $unread = $notifications->filter(fn ($n) => ! $seenAt || $n['at']->gt(\Illuminate\Support\Carbon::parse($seenAt)))->count();
      @endphp
      <div class="usermenu" data-dropdown>
        <button type="button" class="icon-btn" aria-haspopup="true" aria-expanded="false" aria-label="Notificações ({{ $unread }} novas)">
          <x-icon name="bell" />@if ($unread)<span class="badge-dot">{{ $unread }}</span>@endif
        </button>
        <div class="dropdown" role="menu" style="min-width:340px;max-width:380px">
          <div class="row between" style="padding:8px 10px"><b>Notificações</b>
            @if ($unread)<form method="POST" action="{{ route('teacher.notifications.read') }}">@csrf<button type="submit" class="small" style="padding:0;color:var(--blue)">Marcar como lidas</button></form>@endif
          </div>
          @forelse ($notifications as $n)
            <a href="{{ $n['url'] ?? '#' }}" style="align-items:flex-start;font-weight:600">
              <span class="chip {{ $n['color'] }}" style="padding:6px"><x-icon :name="$n['icon']" /></span>
              <span class="small">{{ $n['text'] }}<br><span class="tiny muted">{{ $n['at']->diffForHumans() }}</span></span>
            </a>
          @empty
            <p class="small muted" style="padding:10px">Nenhuma novidade por enquanto.</p>
          @endforelse
        </div>
      </div>
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

@extends('layouts.student')
@section('title', 'Início')

@section('content')
<div class="stack">
  <section class="card soft-blue hero-card">
    <div>
      <h1>Olá, {{ $me->displayName() }}!</h1>
      <p style="font-size:1.15rem" class="bold muted">Vamos explorar a América hoje?</p>
      <div class="row wrap">
        <a class="btn btn-primary" href="{{ route('activities') }}"><x-icon name="clipboard" /> Ver atividades</a>
        <a class="btn btn-outline" href="{{ route('explore') }}"><x-icon name="compass" /> Explorar o mapa</a>
      </div>
    </div>
    <div class="art map-box" style="padding:6px">@include('partials.americas-map')</div>
  </section>

  <div class="grid g-main">
    @php($m = $s['nextMission'])
    <section class="card soft-yellow">
      <span class="chip orange"><x-icon name="target" /> Sua próxima missão</span>
      @if ($m)
        <div class="row" style="align-items:flex-start;margin-top:10px">
          <div class="grow">
            <h2 style="font-size:1.7rem">{{ $m->title }}</h2>
            <p class="muted">{{ $m->description }}</p>
            <div class="meta"><span><x-icon name="clock" /> {{ $m->estimated_minutes }} min</span><span><x-icon name="star" /> +{{ $m->reward_points }} pontos</span><span><x-icon name="help" /> {{ $m->questions_count }} perguntas</span></div>
          </div>
          <div class="act"><div class="ico" style="width:72px;height:72px;background:#fff"><x-icon :name="$m->icon" style="width:40px;height:40px" /></div></div>
        </div>
        <a class="btn btn-green btn-block btn-lg" style="margin-top:16px" href="{{ route('play', ['atividade' => $m->id]) }}"><x-icon name="play" class="fill" /> Começar atividade <x-icon name="arrow-right" /></a>
      @else
        <p class="muted" style="margin-top:10px">Nenhuma atividade disponível ainda. Que tal explorar o mapa?</p>
      @endif
    </section>

    <section class="card">
      <div class="row">
        <x-ring :percent="$s['overallPercent']" />
        <div class="grow">
          <h3 style="margin:0">Progresso geral</h3>
          <p class="small muted">concluído</p>
          <div class="grid g-3" style="gap:8px">
            <div class="stat yellow tight" style="padding:8px"><div><div class="val" style="font-size:1.15rem">{{ number_format($s['points'], 0, ',', '.') }}</div><div class="lbl">pontos</div></div></div>
            <div class="stat blue" style="padding:8px"><div><div class="val" style="font-size:1.15rem">{{ $s['activitiesDone'] }}<small class="small"> de {{ $s['activitiesTotal'] }}</small></div><div class="lbl">atividades</div></div></div>
            <div class="stat green" style="padding:8px"><div><div class="val" style="font-size:1.15rem">{{ $s['achievementsUnlocked'] }}</div><div class="lbl">conquistas</div></div></div>
          </div>
        </div>
      </div>
    </section>
  </div>

  <section class="card">
    <div class="card-title"><h3><x-icon name="star" style="color:var(--yellow)" /> Minhas conquistas</h3><a href="{{ route('achievements') }}">Ver todas <x-icon name="arrow-right" /></a></div>
    <div class="medals">
      @foreach (collect($s['achievements'])->sortByDesc(fn ($a) => $a['status'] === 'UNLOCKED')->take(6) as $a)
        <x-medal :a="$a" />
      @endforeach
    </div>
  </section>

  @if ($s['recentMatches']->isNotEmpty())
  <section class="card">
    <div class="card-title"><h3><x-icon name="clock" /> Últimas partidas</h3></div>
    <div class="list">
      @foreach ($s['recentMatches'] as $match)
        <a class="list-item" href="{{ $match->status === 'IN_PROGRESS' ? route('play', ['partida' => $match->id]) : route('match.result', $match->id) }}" style="color:inherit;text-decoration:none">
          <div class="ico"><x-icon name="map" /></div>
          <div class="grow"><b>{{ $match->activity?->title ?? 'Fase '.$match->phase->number.' — '.$match->phase->title }}</b><div class="small muted">{{ $match->mode->name }} · {{ $match->started_at->diffForHumans() }}</div></div>
          @if ($match->status === 'IN_PROGRESS')<span class="chip yellow">Continuar</span>@elseif($match->status === 'ABANDONED')<span class="chip gray">Encerrada</span>@else<span class="chip green">{{ $match->total_score }} pts · {{ $match->accuracy() ?? 0 }}%</span>@endif
        </a>
      @endforeach
    </div>
  </section>
  @endif
</div>
@endsection

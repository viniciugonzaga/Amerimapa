@extends('layouts.student')
@section('title', 'Atividades')

@php
    $modeIcons = ['EXPLORATION' => 'compass', 'TRAINING' => 'refresh', 'QUIZ' => 'help', 'CHALLENGE' => 'fire', 'CLASSROOM' => 'users'];
    $modeColors = ['EXPLORATION' => 'green', 'TRAINING' => 'blue', 'QUIZ' => 'purple', 'CHALLENGE' => 'orange', 'CLASSROOM' => 'yellow'];
@endphp

@section('content')
<div class="page-head">
  <div><h1><x-icon name="clipboard" /> Atividades</h1><p class="muted">Escolha uma missão ou um modo de jogo. Responda tocando no mapa tátil!</p></div>
  <span class="handwrite">Você consegue!</span>
</div>

<div class="grid g-2">
  @forelse ($activities as $a)
    <article class="card {{ $a->unlocked ? '' : 'flat' }}">
      <div class="act {{ $a->unlocked ? '' : 'locked' }}">
        <div class="ico"><x-icon :name="$a->unlocked ? $a->icon : 'lock'" /></div>
        <div class="grow">
          <div class="row between"><h3 style="margin:0">{{ $a->title }}</h3>@if ($a->completed)<span class="chip green"><x-icon name="check" /> {{ $a->best_percent }}%</span>@endif</div>
          <p class="small muted" style="margin:4px 0 8px">{{ $a->description }}</p>
          <div class="meta">
            <span><x-icon name="flag" /> Fase {{ $a->phase->number }}</span>
            <span><x-icon :name="$modeIcons[$a->mode->code] ?? 'play'" /> {{ $a->mode->name }}</span>
            <span><x-icon name="help" /> {{ $a->questions_count }} perguntas</span>
            <span><x-icon name="clock" /> {{ $a->estimated_minutes }} min</span>
            @if ($a->schoolClass)<span class="chip purple">{{ $a->schoolClass->name }}</span>@endif
          </div>
          @if ($a->completed)<div style="margin-top:10px"><x-bar :percent="$a->best_percent" color="green" /></div>@endif
        </div>
      </div>
      <div class="row end" style="margin-top:14px">
        @if ($a->unlocked)
          @if ($a->completed)<a class="btn btn-outline btn-sm" href="{{ route('play', ['atividade' => $a->id, 'modo' => $modes->firstWhere('code', 'TRAINING')?->id]) }}"><x-icon name="refresh" /> Treinar</a>@endif
          <a class="btn btn-green btn-sm" href="{{ route('play', ['atividade' => $a->id]) }}"><x-icon name="play" class="fill" /> {{ $a->completed ? 'Jogar de novo' : 'Começar' }}</a>
        @else
          @php($req = collect($phases)->first(fn ($p) => $p['phase']->id === $a->phase_id)['requirement'] ?? null)
          <span class="small muted bold"><x-icon name="lock" /> Conclua a fase {{ $req['previousPhaseNumber'] ?? '' }} com {{ $req['minScore'] ?? 0 }} pontos para liberar</span>
        @endif
      </div>
    </article>
  @empty
    <div class="card empty"><x-icon name="clipboard" />Nenhuma atividade disponível.</div>
  @endforelse
</div>

<h2 style="margin-top:30px">Modos de jogo</h2>
<p class="muted">Jogue livremente por fase. No modo Treino as tentativas são ilimitadas e não há pontuação competitiva.</p>
<div class="grid g-3">
  @foreach ($modes as $mode)
    @continue($mode->code === 'CLASSROOM' && !($me?->isStaff()))
    <form class="card" method="GET" action="{{ $mode->code === 'EXPLORATION' ? route('explore') : route('play') }}">
      <div class="act"><div class="ico" style="background:var(--{{ $modeColors[$mode->code] }}-50);color:var(--{{ $modeColors[$mode->code] }})"><x-icon :name="$modeIcons[$mode->code]" /></div>
        <div class="grow"><h3 style="margin:0">{{ $mode->name }}</h3><p class="small muted" style="margin:4px 0 0">{{ $mode->description }}</p></div></div>
      <div class="meta" style="margin:10px 0">
        @if ($mode->is_timed)<span><x-icon name="clock" /> {{ $mode->time_limit_seconds }}s por pergunta</span>@endif
        <span><x-icon name="target" /> {{ $mode->max_attempts ? $mode->max_attempts.' tentativa(s)' : 'Tentativas livres' }}</span>
        @if ($mode->is_scored)<span><x-icon name="star" /> Vale pontos</span>@endif
      </div>
      @if ($mode->code !== 'EXPLORATION')
        <input type="hidden" name="modo" value="{{ $mode->id }}">
        <div class="row">
          <select class="input" name="fase" aria-label="Fase" required>
            @foreach ($phases as $p)
              <option value="{{ $p['phase']->id }}" @disabled(!$p['unlocked'])>Fase {{ $p['phase']->number }} — {{ $p['phase']->title }}{{ $p['unlocked'] ? '' : ' 🔒' }}</option>
            @endforeach
          </select>
          <button class="btn btn-primary" type="submit" aria-label="Jogar {{ $mode->name }}"><x-icon name="play" class="fill" /></button>
        </div>
      @else
        <button class="btn btn-green btn-block" type="submit"><x-icon name="compass" /> Explorar o mapa</button>
      @endif
    </form>
  @endforeach
</div>
@endsection

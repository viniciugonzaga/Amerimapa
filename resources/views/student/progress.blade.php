@extends('layouts.student')
@section('title', 'Meu progresso')

@section('content')
<div class="page-head">
  <div><h1><x-icon name="chart" /> Meu progresso</h1><p class="muted">Veja como você está indo e celebre suas conquistas!</p></div>
  <span class="handwrite">Cada passo importa!</span>
</div>

<div class="grid g-main">
  <section class="card">
    <div class="card-title"><h3><x-icon name="star" style="color:var(--yellow)" /> Progresso geral</h3></div>
    <div class="row wrap" style="gap:22px">
      <div class="center"><x-ring :percent="$s['overallPercent']" /><div class="small muted bold">das atividades concluídas</div></div>
      <div class="grid g-2 grow" style="gap:10px">
        <div class="stat yellow"><div class="ico"><x-icon name="star" /></div><div><div class="val">{{ number_format($s['points'], 0, ',', '.') }}</div><div class="lbl">pontos</div></div></div>
        <div class="stat blue"><div class="ico"><x-icon name="clipboard" /></div><div><div class="val">{{ $s['activitiesDone'] }} <small class="small">de {{ $s['activitiesTotal'] }}</small></div><div class="lbl">atividades</div></div></div>
        <div class="stat green"><div class="ico"><x-icon name="target" /></div><div><div class="val">{{ $s['accuracy'] }}%</div><div class="lbl">de acertos</div></div></div>
        <div class="stat purple"><div class="ico"><x-icon name="crown" /></div><div><div class="val">{{ $s['achievementsUnlocked'] }}</div><div class="lbl">conquistas</div></div></div>
      </div>
    </div>
  </section>

  <section class="card">
    <div class="card-title"><h3><x-icon name="check-circle" style="color:var(--green)" /> Atividades concluídas</h3><a href="{{ route('activities') }}">Ver todas <x-icon name="arrow-right" /></a></div>
    <div class="list">
      @forelse ($s['activities']->where('completed', true)->sortByDesc('best_percent')->take(5) as $a)
        <div class="list-item"><div class="ico"><x-icon :name="$a->icon" /></div><div class="grow"><b>{{ $a->title }}</b><x-bar :percent="$a->best_percent" /></div><b>{{ $a->best_percent }}%</b></div>
      @empty
        <div class="empty"><x-icon name="clipboard" />Você ainda não concluiu atividades. <a href="{{ route('activities') }}">Começar agora</a></div>
      @endforelse
    </div>
  </section>
</div>

<div class="grid g-3" style="margin-top:18px">
  <section class="card">
    <div class="card-title"><h3><x-icon name="chart" style="color:var(--blue)" /> Minha evolução</h3>@include('partials.period-select')</div>
    @php($chartData = ['title' => 'Acertos por dia (%)', 'labels' => $s['evolution']['labels'], 'series' => [['name' => 'Acertos', 'color' => 'blue', 'values' => $s['evolution']['values']]]])
    <div class="chart" data-chart='@json($chartData)'></div>
    <p class="tiny muted">Porcentagem de acertos por dia.</p>
  </section>

  <section class="card soft-yellow">
    <div class="card-title"><h3><x-icon name="bulb" style="color:var(--yellow)" /> Vamos revisar?</h3></div>
    @if ($r = $s['review'])
      <div class="act"><img src="{{ asset('images/ilustracao-revisar.png') }}" alt="" style="width:96px;border-radius:12px"><div><b>{{ $r->title }}</b><p class="small muted">Seu melhor resultado foi {{ $r->best_percent }}%. Pratique mais uma vez para avançar!</p></div></div>
      <a class="btn btn-primary btn-block" href="{{ route('play', ['atividade' => $r->id, 'modo' => \App\Models\GameMode::byCode('TRAINING')?->id]) }}"><x-icon name="play" class="fill" /> Revisar conteúdo</a>
    @else
      <p class="muted">Conclua uma atividade e eu sugiro o que revisar.</p>
    @endif
  </section>

  <section class="card">
    <div class="card-title"><h3><x-icon name="trophy" style="color:var(--purple)" /> Minhas conquistas</h3><a href="{{ route('achievements') }}">Ver todas</a></div>
    <div class="medals">@foreach (collect($s['achievements'])->where('status', 'UNLOCKED')->take(3) as $a)<x-medal :a="$a" />@endforeach</div>
    @if (collect($s['achievements'])->where('status', 'UNLOCKED')->isEmpty())<p class="muted small">Nenhuma ainda — a primeira está perto!</p>@endif
  </section>
</div>

<section class="card" style="margin-top:18px">
  <div class="card-title"><h3><x-icon name="flag" /> Fases</h3></div>
  <div class="grid g-4">
    @foreach ($phases as $p)
      <div class="stat {{ $p['unlocked'] ? ($p['completedMatches'] ? 'green' : 'blue') : '' }}">
        <div class="ico"><x-icon :name="$p['unlocked'] ? ($p['completedMatches'] ? 'check-circle' : 'flag') : 'lock'" /></div>
        <div><div class="bold">Fase {{ $p['phase']->number }} — {{ $p['phase']->title }}</div>
          <div class="lbl">@if ($p['unlocked']) Melhor: {{ $p['bestScore'] }} pts @else Precisa de {{ $p['requirement']['minScore'] }} pts na fase {{ $p['requirement']['previousPhaseNumber'] }} @endif</div></div>
      </div>
    @endforeach
  </div>
</section>
@endsection

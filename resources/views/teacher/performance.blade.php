@extends('layouts.teacher')
@section('title', 'Desempenho e ranking')

@section('content')
<div class="page-head">
  <div><h1><x-icon name="trophy" /> Desempenho e ranking</h1><p class="muted">Só partidas concluídas contam no ranking (RN21).</p></div>
  @include('teacher.partials.class-filter', ['route' => 'teacher.performance'])
</div>

<div class="grid g-main">
  <section class="card">
    <div class="card-title"><h3><x-icon name="crown" /> Ranking {{ $class ? '— '.$class->name : 'geral' }}</h3></div>
    <div class="stack" style="gap:6px">
      @forelse ($ranking as $r)
        <div class="rank-row"><span class="rank-pos">{{ $r->rank }}</span><span class="avatar sm av-{{ $r->avatar }}">{{ mb_substr($r->name, 0, 1) }}</span>
          <div class="grow"><b>{{ $r->name }}</b><div class="tiny muted">{{ $r->class_name }} · {{ $r->matches }} partidas · {{ $r->accuracy }}% de acertos</div></div>
          <b>⭐ {{ number_format($r->points, 0, ',', '.') }}</b></div>
      @empty
        <div class="empty"><x-icon name="trophy" />Sem partidas concluídas.</div>
      @endforelse
    </div>
  </section>
  <div class="stack">
    <section class="card">
      <div class="card-title"><h3><x-icon name="chart" /> Acertos por dia</h3></div>
      @php($chartData = ['labels' => $chart['labels'], 'series' => [['name' => 'Acertos', 'color' => 'green', 'values' => $chart['values']]]])
      <div class="chart" data-chart='@json($chartData)'></div>
    </section>
    <section class="card">
      <div class="card-title"><h3><x-icon name="bulb" /> Onde a turma mais erra</h3></div>
      <div class="list">
        @forelse ($hardest as $h)
          <div class="list-item"><div class="grow small"><b>{{ $h->prompt }}</b><x-bar :percent="$h->accuracy" color="yellow" /></div><span class="chip {{ $h->accuracy < 50 ? 'red' : 'yellow' }}">{{ $h->accuracy }}%</span></div>
        @empty
          <p class="small muted">Dados insuficientes ainda.</p>
        @endforelse
      </div>
    </section>
  </div>
</div>
@endsection

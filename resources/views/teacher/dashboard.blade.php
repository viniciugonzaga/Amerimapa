@extends('layouts.teacher')
@section('title', 'Visão geral')

@php($c = $o['counts'])
@section('content')
<div class="page-head">
  <div><h1>Olá, {{ $me->nickname ?: $me->displayName() }}!</h1><p class="muted">Aqui está o resumo das suas turmas.</p></div>
  <a class="btn btn-primary" href="{{ route('teacher.activities.create') }}"><x-icon name="plus" /> Criar atividade</a>
</div>

<div class="grid g-side">
  <div class="stack">
    <div class="grid g-4">
      <div class="kpi blue"><div class="ico"><x-icon name="users" /></div><div class="small bold muted">Turmas ativas</div><div class="val">{{ $c['classes'] }}</div><div class="delta">↑ +{{ $c['classesDelta'] }} nos últimos 7 dias</div></div>
      <div class="kpi green"><div class="ico"><x-icon name="user" /></div><div class="small bold muted">Alunos cadastrados</div><div class="val">{{ $c['students'] }}</div><div class="delta">↑ +{{ $c['studentsDelta'] }} nos últimos 7 dias</div></div>
      <div class="kpi yellow"><div class="ico"><x-icon name="clipboard" /></div><div class="small bold muted">Atividades criadas</div><div class="val">{{ $c['activities'] }}</div><div class="delta">↑ +{{ $c['activitiesDelta'] }} nos últimos 7 dias</div></div>
      <div class="kpi red"><div class="ico"><x-icon name="target" /></div><div class="small bold muted">Média de acertos da turma</div><div class="val">{{ $c['accuracy'] }}%</div><div class="delta {{ $c['accuracyDelta'] < 0 ? 'down' : '' }}">{{ $c['accuracyDelta'] < 0 ? '↓' : '↑' }} {{ $c['accuracyDelta'] >= 0 ? '+' : '' }}{{ $c['accuracyDelta'] }}% na semana</div></div>
    </div>

    <div class="grid g-main">
      <section class="card">
        <div class="card-title"><h3><x-icon name="chart" /> Desempenho da turma</h3>@include('partials.period-select')</div>
        <div class="row" style="margin-bottom:8px"><x-ring :percent="$c['accuracy']" size="sm" /><div><b>Média geral de acertos</b><div class="small muted">acertos (%) por dia em cada turma</div></div></div>
        <div class="chart" data-chart='@json($o['chart'])'></div>
      </section>
      <section class="card">
        <div class="card-title"><h3><x-icon name="sparkles" /> Atividades recentes</h3><a href="{{ route('teacher.activities.index') }}">Ver todas</a></div>
        <div class="list">
          @forelse ($o['recentActivities'] as $a)
            <a class="list-item" href="{{ route('teacher.activities.edit', $a) }}" style="color:inherit;text-decoration:none"><div class="ico"><x-icon :name="$a->icon" /></div><div class="grow"><b class="small">{{ $a->title }}</b><div class="tiny muted">{{ $a->schoolClass ? $a->schoolClass->name.' · '.$a->schoolClass->grade : 'Todas as turmas' }}</div></div><x-icon name="chevron-right" /></a>
          @empty
            <div class="empty"><x-icon name="clipboard" />Nenhuma atividade ainda.</div>
          @endforelse
        </div>
      </section>
    </div>

    <div class="grid g-main">
      <section class="card">
        <div class="card-title"><h3><x-icon name="users" /> Turmas</h3><a href="{{ route('teacher.classes.index') }}">Ver todas</a></div>
        <div class="grid g-3">
          @forelse ($o['classes'] as $class)
            <a class="class-card" href="{{ route('teacher.classes.show', $class) }}"><div class="ico c-{{ $class->color }}"><x-icon name="users" /></div><b>{{ $class->name }}</b><div class="small muted">{{ $class->grade }}</div><div class="tiny muted">{{ $class->students_count }} alunos</div></a>
          @empty
            <div class="empty"><x-icon name="users" />Crie sua primeira turma.</div>
          @endforelse
        </div>
      </section>
      <section class="card">
        <div class="card-title"><h3><x-icon name="sparkles" /> Acesso rápido</h3></div>
        <div class="grid g-2" style="gap:10px">
          <a class="btn btn-outline" href="{{ route('teacher.activities.create') }}"><x-icon name="plus-circle" /> Criar atividade</a>
          <a class="btn btn-outline" href="{{ route('teacher.classes.index') }}"><x-icon name="users" /> Gerenciar turmas</a>
          <a class="btn btn-outline" href="{{ route('teacher.reports') }}"><x-icon name="file" /> Relatórios</a>
          <a class="btn btn-outline" href="{{ route('teacher.activities.index') }}#sala-de-aula"><x-icon name="play" /> Modo Sala de Aula</a>
        </div>
      </section>
    </div>
  </div>

  <aside class="stack">
    @php($d = $o['device'])
    <section class="card">
      <div class="card-title"><h3><x-icon name="cpu" style="color:var(--green)" /> Dispositivo IoT</h3></div>
      @if ($d)
        <span class="chip {{ $d->isOnline() ? 'green' : 'red' }}">{{ $d->isOnline() ? 'Conectado' : 'Desconectado' }}</span>
        <p class="small muted" style="margin-top:10px"><b>{{ $d->name }}</b> ({{ $d->device_id }})<br>Última comunicação {{ $d->last_seen_at ? $d->last_seen_at->diffForHumans() : 'nunca' }}</p>
        @if ($d->battery_level !== null)<div class="row"><x-icon name="battery" style="color:var(--green)" /><div class="grow"><x-bar :percent="$d->battery_level" color="green" /></div><b>{{ $d->battery_level }}%</b></div>@endif
      @else
        <p class="small muted">Nenhum mapa se conectou ainda. Ligue o ESP32 ou rode <code>php artisan amerimapa:mapa</code>.</p>
      @endif
      <a class="btn btn-outline btn-block" style="margin-top:12px" href="{{ route('teacher.device') }}">Ver detalhes do dispositivo <x-icon name="arrow-right" /></a>
    </section>

    <section class="card">
      <div class="card-title"><h3><x-icon name="trophy" style="color:var(--yellow)" /> Ranking geral</h3></div>
      <p class="tiny muted">Os melhores alunos das suas turmas (partidas concluídas).</p>
      <div class="stack" style="gap:6px">
        @forelse ($o['ranking']->take(3) as $r)
          <div class="rank-row"><span class="rank-pos">{{ $r->rank }}</span><x-avatar :key="$r->avatar" :name="$r->name" size="sm" /><div class="grow"><b class="small">{{ $r->name }}</b><div class="tiny muted">⭐ {{ number_format($r->points, 0, ',', '.') }} pontos</div></div><span class="chip gray">{{ $r->rank }}º lugar</span></div>
        @empty
          <div class="empty small">Sem partidas concluídas ainda.</div>
        @endforelse
      </div>
      <a class="btn btn-primary btn-block" style="margin-top:12px" href="{{ route('teacher.performance') }}">Ver tudo <x-icon name="arrow-right" /></a>
    </section>
  </aside>
</div>
@endsection

@extends('layouts.student')
@section('title', 'Minhas conquistas')

@section('content')
<section class="card soft-blue" style="margin-bottom:18px">
  <h1><x-icon name="star" style="color:var(--yellow)" /> Minhas conquistas</h1>
  <p class="muted bold" style="margin:0">Veja os emblemas que você já ganhou e descubra os próximos desafios!</p>
</section>

<div class="grid g-3">
  <div class="stat green"><div class="ico"><x-icon name="trophy" /></div><div><div class="val">{{ $unlocked }}</div><div class="lbl">conquistas desbloqueadas</div></div></div>
  <div class="stat blue"><div class="ico"><x-icon name="star" /></div><div><div class="val">{{ number_format($points, 0, ',', '.') }}</div><div class="lbl">pontos</div></div></div>
  <div class="stat purple"><div class="ico"><x-icon name="target" /></div><div><div class="lbl">Próxima conquista:</div><div class="val" style="font-size:1.1rem">{{ $next ? $next['name'].' · '.$next['current'].'/'.$next['target'] : 'Todas conquistadas! 🎉' }}</div></div></div>
</div>

<div class="grid g-3" style="margin-top:18px">
  @foreach ($list as $a)
    <article class="card">
      <div class="ach {{ $a['status'] === 'LOCKED' ? 'locked' : '' }}">
        <div class="disc m-{{ $a['color'] }}"><x-icon :name="$a['icon']" /></div>
        <div class="grow">
          <h3 style="margin:0">{{ $a['name'] }}</h3>
          @if ($a['status'] === 'UNLOCKED')<span class="chip green"><x-icon name="check" /> Conquistado</span>
          @elseif ($a['status'] === 'IN_PROGRESS')<span class="chip"><x-icon name="clock" /> Em andamento</span>
          @else<span class="chip gray"><x-icon name="lock" /> Bloqueado</span>@endif
          <p class="small muted" style="margin:8px 0">{{ $a['description'] }}</p>
          <div class="row"><div class="grow"><x-bar :percent="$a['percent']" :color="$a['status'] === 'UNLOCKED' ? 'green' : ''" /></div><span class="tiny bold">{{ $a['current'] }}/{{ $a['target'] }}</span></div>
        </div>
      </div>
    </article>
  @endforeach
</div>

<section class="card" style="margin-top:18px">
  <div class="card-title"><h3><x-icon name="crown" style="color:var(--yellow)" /> Ranking da minha turma</h3><span class="chip gray">Partidas concluídas</span></div>
  @if ($ranking->isEmpty())
    <p class="muted small">{{ $me->school_class_id ? 'Ninguém da turma concluiu partidas ainda. Seja o primeiro!' : 'Você ainda não está em uma turma. Peça ao seu professor para te adicionar.' }}</p>
  @else
    <div class="stack" style="gap:6px">
      @foreach ($ranking as $r)
        <div class="rank-row" style="{{ $r->id === $me->id ? 'outline:2px solid var(--blue)' : '' }}"><span class="rank-pos">{{ $r->rank }}</span><x-avatar :key="$r->avatar" :name="$r->name" size="sm" />
          <div class="grow"><b class="small">{{ $r->name }}{{ $r->id === $me->id ? ' (você)' : '' }}</b><div class="tiny muted">{{ $r->accuracy }}% de acertos · {{ $r->matches }} partidas</div></div>
          <b>⭐ {{ number_format($r->points, 0, ',', '.') }}</b></div>
      @endforeach
    </div>
  @endif
</section>

<section class="card soft-blue row between wrap" style="margin-top:18px">
  <div><h3 style="margin:0"><x-icon name="sparkles" /> Continue explorando!</h3><p class="muted" style="margin:0">Cada desafio é uma nova descoberta.</p></div>
  <a class="btn btn-primary" href="{{ route('activities') }}">Ver atividades <x-icon name="arrow-right" /></a>
</section>
@endsection

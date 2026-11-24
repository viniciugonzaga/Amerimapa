@extends('layouts.student')
@section('title', 'Resultado')

@php
    $acc = $match->accuracy() ?? 0;
    $resultLabels = ['CORRECT' => ['Acertou', 'green', 'check'], 'WRONG' => ['Errou', 'red', 'x'], 'TIMEOUT' => ['Tempo esgotado', 'orange', 'clock'], 'SKIPPED' => ['Pulada', 'gray', 'skip']];
    $message = $acc >= 90 ? 'Incrível! Você é um(a) craque da geografia!' : ($acc >= 60 ? 'Muito bem! Continue praticando!' : 'Cada passo importa! Vamos treinar mais?');
@endphp

@section('content')
<div class="stack" data-speak-on-load="{{ $message }} Você fez {{ $match->total_score }} pontos e acertou {{ $match->correct_count }} perguntas.">
  <section class="card soft-blue hero-card">
    <div>
      <span class="chip {{ $match->status === 'COMPLETED' ? 'green' : 'gray' }}">{{ $match->status === 'COMPLETED' ? 'Partida concluída' : 'Partida encerrada' }}</span>
      <h1 style="margin-top:10px">{{ $message }}</h1>
      <p class="muted bold">{{ $match->activity?->title ?? 'Fase '.$match->phase->number.' — '.$match->phase->title }} · {{ $match->mode->name }}</p>
      <div class="grid g-3" style="max-width:520px">
        <div class="stat yellow"><div class="ico"><x-icon name="star" /></div><div><div class="val">{{ $match->total_score }}</div><div class="lbl">pontos</div></div></div>
        <div class="stat green"><div class="ico"><x-icon name="check-circle" /></div><div><div class="val">{{ $match->correct_count }}</div><div class="lbl">acertos</div></div></div>
        <div class="stat orange"><div class="ico"><x-icon name="fire" /></div><div><div class="val">{{ $match->best_streak }}</div><div class="lbl">melhor sequência</div></div></div>
      </div>
    </div>
    <x-ring :percent="$acc" />
  </section>

  @if ($newAchievements->isNotEmpty())
    <section class="card soft-yellow">
      <h3><x-icon name="trophy" /> Novas conquistas!</h3>
      <div class="medals">@foreach ($newAchievements as $a)<x-medal :a="['status' => 'UNLOCKED', 'name' => $a->name, 'icon' => $a->icon, 'color' => $a->color, 'description' => $a->description]" />@endforeach</div>
    </section>
  @endif
  @if (!$me)<div class="alert info"><x-icon name="help" /> Como visitante, este resultado não foi salvo. <a href="{{ route('register') }}">Crie uma conta</a> para guardar seus pontos!</div>@endif

  <section class="card">
    <div class="card-title"><h3><x-icon name="clipboard" /> Suas respostas</h3></div>
    @if ($answers->isEmpty())
      <div class="empty"><x-icon name="help" />Nenhuma pergunta respondida.</div>
    @else
    <div class="table-wrap"><table class="table">
      <thead><tr><th>Pergunta</th><th>Resposta certa</th><th>Você tocou</th><th>Resultado</th><th class="right">Tempo</th><th class="right">Pontos</th></tr></thead>
      <tbody>
        @foreach ($answers as $ans)
          @php([$label, $color, $icon] = $resultLabels[$ans->result])
          <tr>
            <td>{{ $ans->question->prompt }}</td>
            <td class="bold">{{ $ans->question->answerRegion->name }}</td>
            <td>{{ $ans->touchedRegion?->name ?? '—' }} @if($ans->source === 'MAP')<span class="chip gray tiny">mapa tátil</span>@endif</td>
            <td><span class="chip {{ $color }}"><x-icon :name="$icon" /> {{ $label }}</span></td>
            <td class="right nowrap">{{ $ans->response_time_ms ? number_format($ans->response_time_ms / 1000, 1, ',', '') . ' s' : '—' }}</td>
            <td class="right bold">{{ $ans->points_earned }}</td>
          </tr>
        @endforeach
      </tbody>
    </table></div>
    @endif
  </section>

  <div class="row wrap end">
    <a class="btn btn-outline" href="{{ route('activities') }}"><x-icon name="arrow-left" /> Atividades</a>
    @if ($match->activity_id)
      <a class="btn btn-outline" href="{{ route('play', ['atividade' => $match->activity_id, 'modo' => $training?->id]) }}"><x-icon name="refresh" /> Revisar no treino</a>
      <a class="btn btn-green" href="{{ route('play', ['atividade' => $match->activity_id]) }}"><x-icon name="play" class="fill" /> Jogar de novo</a>
    @else
      <a class="btn btn-green" href="{{ route('play', ['modo' => $match->mode_id, 'fase' => $match->phase_id]) }}"><x-icon name="play" class="fill" /> Jogar de novo</a>
    @endif
  </div>
</div>
@endsection

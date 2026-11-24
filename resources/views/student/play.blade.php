@extends('layouts.student')
@section('title', 'Atividade')

@php
    $config = [
        'matchId' => $match?->id,
        'start' => array_filter([
            'activityId' => $activity?->id,
            'modeId' => request()->integer('modo') ?: (!$activity ? $mode?->id : null),
            'phaseId' => !$activity ? $phase?->id : null,
        ]),
        'focusIsos' => $focusIsos,
        'defaultDevice' => $defaultDevice,
        'scored' => (bool) $mode?->is_scored,
        'playUrl' => route('play'),
        'backUrl' => route('activities'),
        'resultUrl' => route('match.result', ['match' => '__ID__']),
    ];
@endphp

@section('content')
<div data-game='@json($config)'>
  <div class="page-head">
    <div>
      <h1><x-icon name="target" /> {{ $activity?->title ?? 'Fase '.$phase?->number.' — '.$phase?->title }}</h1>
      <p class="muted"><span class="chip purple">{{ $mode?->name }}</span> <span data-q-counter class="bold">Carregando…</span></p>
    </div>
    <div class="row">
      <span class="timer hidden" data-q-timer aria-live="polite"></span>
      <span class="stat yellow" style="padding:8px 14px"><x-icon name="star" style="color:var(--yellow)" /><span><span class="val" style="font-size:1.2rem" data-score>0</span> <span class="lbl">pontos</span></span></span>
    </div>
  </div>
  <div class="steps" data-q-steps style="margin-bottom:18px"></div>

  <div class="grid g-main">
    <section class="card stack">
      <div class="question" aria-live="assertive"><x-icon name="pin" /><span data-q-prompt>Preparando a pergunta…</span></div>
      <div data-q-chips></div>
      <div class="device wait" data-device><span class="dot"></span><div class="grow"><b>Procurando o mapa…</b><small>Verificando a conexão</small></div></div>
      <button class="btn btn-outline btn-block" type="button" data-repeat><x-icon name="volume" /> Ouvir instruções</button>
      <div class="answer-box" data-answer aria-live="polite"><div class="muted bold">Sua resposta aparecerá aqui</div></div>
      <label class="field"><span class="sr-only">Escolher resposta da lista</span><select class="input" data-region-select></select></label>
      <div class="row">
        <button class="btn btn-outline grow" type="button" data-skip><x-icon name="skip" /> Pular</button>
        <button class="btn btn-green grow" type="button" data-confirm disabled>Responder <x-icon name="arrow-right" /></button>
        <button class="btn btn-primary grow hidden" type="button" data-next>Próxima pergunta <x-icon name="arrow-right" /></button>
      </div>
      <button class="btn btn-ghost btn-sm" type="button" data-finish><x-icon name="logout" /> {{ $mode?->is_scored ? 'Encerrar partida' : 'Concluir treino' }}</button>
    </section>

    <section class="card">
      <div class="card-title"><h3><x-icon name="map" /> Toque no mapa</h3><span class="handwrite">Toque no mapa!</span></div>
      <div class="map-box" data-map>
        @include('partials.americas-map')
        <div class="map-tools"><button class="icon-btn" type="button" data-map-all title="Ver o mapa inteiro / aproximar"><x-icon name="zoom-out" /></button></div>
        <div class="map-tip hidden"></div>
      </div>
      <p class="small muted" style="margin-top:10px"><x-icon name="hand" /> Ilhas pequenas do Caribe têm um círculo tracejado para facilitar o toque.</p>
    </section>
  </div>
</div>
@endsection

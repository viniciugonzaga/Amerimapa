@extends('layouts.student')
@section('title', 'Modo Exploração')

@section('content')
@php($exploreCfg = ['explored' => $explored, 'defaultDevice' => $defaultDevice])
<div data-explore='@json($exploreCfg)'>
  <div class="page-head">
    <div><h1><x-icon name="compass" /> Modo Exploração</h1><p class="muted">Conheça o mapa sem pressão: toque em qualquer país (no mapa tátil ou na tela) para descobrir sobre ele.</p></div>
    <span class="chip green"><x-icon name="globe" /> <span data-explored-count>{{ count($explored) }}</span> de {{ $countriesTotal }} países explorados</span>
  </div>
  <div class="grid g-main">
    <section class="card">
      <div class="map-box" data-map>@include('partials.americas-map')<div class="map-tip hidden"></div></div>
    </section>
    <section class="stack">
      <div class="device wait" data-device><span class="dot"></span><div class="grow"><b>Procurando o mapa…</b><small>Verificando a conexão</small></div></div>
      <div class="card" data-info aria-live="polite">
        <div class="empty"><x-icon name="hand" />Toque em um país para começar a explorar!</div>
      </div>
      <a class="btn btn-outline btn-block" href="{{ route('activities') }}"><x-icon name="arrow-left" /> Voltar às atividades</a>
    </section>
  </div>
</div>
@endsection

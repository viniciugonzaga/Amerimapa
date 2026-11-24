@extends('layouts.teacher')
@section('title', 'Dispositivo IoT')

@php
    $eventLabels = [
        'device.online' => ['Mapa conectado', 'green'], 'device.offline' => ['Mapa desconectado', 'red'],
        'device.sensor_error' => ['Falha de sensor', 'red'], 'device.bound' => ['Vinculado a partida', ''],
        'device.unbound' => ['Desvinculado', 'gray'], 'touch.unbound' => ['Toque livre', 'yellow'],
        'touch.rejected' => ['Toque rejeitado', 'orange'], 'region.explored' => ['Exploração', 'green'],
        'answer.evaluated' => ['Resposta avaliada', 'purple'], 'match.finished' => ['Partida concluída', 'green'],
    ];
@endphp

@section('content')
<div class="page-head">
  <div><h1><x-icon name="cpu" /> Dispositivo IoT</h1><p class="muted">Mapas físicos (ESP32 + MPR121 + DFPlayer) conectados ao AmeriMapa. RN20: sem sinal por {{ config('amerimapa.hardware_timeout_seconds') }} s o mapa é marcado como desconectado.</p></div>
</div>

<section class="card" data-device-panel>
  <div class="card-title"><h3>Mapas</h3><span class="small muted">Atualiza sozinho a cada 4 s</span></div>
  @if ($devices->isEmpty())
    <div class="empty"><x-icon name="wifi-off" />Nenhum mapa se conectou ainda.</div>
  @else
  <div class="table-wrap"><table class="table">
    <thead><tr><th>Mapa</th><th>Status</th><th>Último sinal</th><th>Firmware</th><th>Sensores</th><th>Bateria</th><th>Partida vinculada</th></tr></thead>
    <tbody>
      @foreach ($devices as $d)
        <tr data-device-row="{{ $d->device_id }}">
          <td><b>{{ $d->name }}</b><div class="tiny muted">{{ $d->device_id }}</div></td>
          <td data-online>@if($d->isOnline())<span class="chip green">Conectado</span>@else<span class="chip red">Desconectado</span>@endif</td>
          <td data-seen>{{ $d->last_seen_at?->diffForHumans() ?? '—' }}</td>
          <td>{{ $d->firmware_version ?? '—' }}</td>
          <td>@if($d->sensors_ok)<span class="chip green">OK</span>@else<span class="chip red">Falha</span>@endif</td>
          <td>{{ $d->battery_level !== null ? $d->battery_level.'%' : '—' }}</td>
          <td data-match>{{ $d->bound_match_id ? '#'.$d->bound_match_id : '—' }}</td>
        </tr>
      @endforeach
    </tbody>
  </table></div>
  @endif
</section>

<div class="grid g-2" style="margin-top:18px">
  <section class="card">
    <div class="card-title"><h3><x-icon name="bell" /> Últimos eventos</h3></div>
    <div class="list">
      @forelse ($events as $e)
        @php([$label, $color] = $eventLabels[$e->type] ?? [$e->type, 'gray'])
        <div class="list-item"><span class="chip {{ $color }}">{{ $label }}</span><div class="grow small">
          {{ $e->payload['region']['name'] ?? '' }}
          @isset($e->payload['outcome'])· {{ $e->payload['outcome']['feedback']['message'] ?? '' }}@endisset
          @isset($e->payload['pin'])· pino {{ $e->payload['pin'] }}@endisset
          @if ($e->match_id) <span class="tiny muted">partida #{{ $e->match_id }}</span>@endif
        </div><span class="tiny muted nowrap">{{ $e->created_at?->format('H:i:s') }} · {{ $e->device_id }}</span></div>
      @empty
        <div class="empty"><x-icon name="bell" />Nenhum evento ainda.</div>
      @endforelse
    </div>
  </section>

  <section class="card">
    <div class="card-title"><h3><x-icon name="book" /> Como conectar o ESP32</h3></div>
    <ol class="small" style="padding-left:18px">
      <li>No firmware, configure a URL do servidor: <code>{{ url('/api/v1') }}</code></li>
      <li>Envie os headers <code>X-Device-Key: {{ $deviceKeyHint }}</code> (valor de <code>DEVICE_API_KEY</code> no .env) e <code>X-Device-Id: map-01</code>.</li>
      <li>A cada 5 s: <code>POST /hardware/heartbeat</code> com <code>{"firmwareVersion":"1.0.0","sensorsOk":true,"batteryLevel":80}</code>.</li>
      <li>A cada toque: <code>POST /hardware/touches</code> com <code>{"pin":"5B:11"}</code>. A resposta traz <code>device.led</code> (GREEN/RED/YELLOW) e <code>device.audioTrack</code> (faixa do DFPlayer).</li>
      <li>Sem hardware? Simule no terminal: <code>php artisan amerimapa:mapa --auto</code></li>
    </ol>
    <a class="btn btn-outline btn-sm" href="{{ route('teacher.regions.index') }}"><x-icon name="pin" /> Ver pinos das regiões</a>
  </section>
</div>
@endsection

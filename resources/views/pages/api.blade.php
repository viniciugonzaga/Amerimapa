@extends('layouts.guest')
@section('title', 'API')

@php
$groups = [
  'Saúde e autenticação' => [
    ['GET', '/health', '🔓', 'Status da API e do banco'],
    ['POST', '/auth/register', '🔓', '{ name, email, password } → token + jogador'],
    ['POST', '/auth/login', '🔓', '{ email, password } → token + jogador'],
    ['POST', '/auth/guest', '🔓', 'Sessão de visitante (RN01: progresso não salvo)'],
    ['GET', '/auth/me', '🔑', 'Quem sou eu + preferências'],
    ['POST', '/auth/logout', '🔑', 'Revoga o token'],
  ],
  'Jogadores' => [
    ['GET/POST', '/players', '👩‍🏫', 'Listar (?role&search&schoolClassId&page&limit) / cadastrar'],
    ['GET/PATCH/DELETE', '/players/{id}', '🔑', 'O próprio jogador ou professor'],
    ['GET', '/players/{id}/progress', '🔑', 'Fases desbloqueadas e melhores pontuações (RN11)'],
    ['GET', '/players/{id}/matches', '🔑', 'Histórico de partidas'],
    ['GET', '/players/{id}/achievements', '🔑', 'Conquistas com progresso'],
    ['GET', '/players/{id}/summary', '🔑', 'Resumo das telas Início/Progresso'],
    ['GET/PUT', '/players/{id}/preferences', '🔑', 'Acessibilidade (RN18)'],
  ],
  'Conteúdo' => [
    ['GET', '/regions', '🔓', '?type&parentId&hasPin&search'],
    ['GET', '/regions/{id} · /regions/pin/{pin}', '🔓', 'Região por id ou pino do hardware (RN19)'],
    ['POST', '/regions/{id}/explore', '🔑', 'Modo Exploração pela tela'],
    ['POST/PATCH/DELETE', '/regions', '👩‍🏫', 'Gerenciar regiões'],
    ['GET', '/categories · /phases · /game-modes', '🔓', 'Catálogos (+ POST/PATCH/DELETE 👩‍🏫)'],
    ['GET', '/phases/available', '🔓/🔑', 'Fases com bloqueio para quem pergunta'],
    ['CRUD', '/questions', '👩‍🏫', 'Contém as respostas: só professores'],
    ['GET', '/activities[/{id}]', '🔓/🔑', 'Atividades visíveis (+ POST/PATCH/DELETE 👩‍🏫)'],
    ['CRUD', '/classes', '👩‍🏫', 'Turmas'],
    ['GET', '/achievements', '🔓', 'Catálogo de conquistas'],
    ['GET', '/leaderboard', '🔓', '?phaseId&modeId&schoolClassId&limit (RN21)'],
    ['GET', '/dashboard/teacher', '👩‍🏫', 'Números do painel do professor'],
  ],
  'Partidas (motor do jogo)' => [
    ['POST', '/matches', '🔑', '{ activityId } ou { modeId, phaseId } (RN02)'],
    ['GET', '/matches · /matches/{id}', '🔑', 'Listar / detalhar'],
    ['POST', '/matches/{id}/next-question', '🔑', 'Ativa a próxima pergunta (RN03, RN10, RN16)'],
    ['POST', '/matches/{id}/answers', '🔑', '{ regionId, source: MAP|SCREEN } → feedback (RN04–RN09, RN17)'],
    ['GET', '/matches/{id}/answers', '🔑', 'Telemetria de tentativas'],
    ['POST', '/matches/{id}/skip', '🔑', 'Pular (ou TIMEOUT se expirou — RN13)'],
    ['POST', '/matches/{id}/finish', '🔑', '{ status: COMPLETED|ABANDONED }'],
    ['DELETE', '/matches/{id}', '🛠️', 'Somente admin'],
  ],
  'Hardware (ESP32)' => [
    ['POST', '/hardware/heartbeat', '📟', '{ firmwareVersion, sensorsOk, batteryLevel }'],
    ['POST', '/hardware/touches', '📟', '{ pin: "5B:11" } → { action, region, outcome, device: { led, audioTrack, message } }'],
    ['POST', '/hardware/bind-match', '🔑', '{ deviceId, matchId }'],
    ['POST', '/hardware/unbind', '🔑', '{ deviceId }'],
    ['GET', '/hardware/status', '🔑', 'Mapas e se estão online (RN20)'],
    ['GET', '/hardware/events/poll', '🔑', '?deviceId&matchId&after — eventos em tempo real (polling)'],
    ['GET', '/hardware/events', '🔑', 'Server-Sent Events (?access_token=)'],
  ],
];
@endphp

@section('content')
<div class="container landing">
  <div class="card">
    <h1>API AmeriMapa <span class="chip">/api/v1</span></h1>
    <p>Mesmo contrato da API Node do Matheus, implementado em Laravel. Base: <code>{{ url('/api/v1') }}</code>. Sucesso: <code>{ "data": ..., "meta"?: {...} }</code> · Erro: <code>{ "error": { "code", "message", "details"? } }</code>.</p>
    <p class="small">🔓 público · 🔑 <code>Authorization: Bearer &lt;token&gt;</code> (jogador ou visitante) · 👩‍🏫 professor/admin · 🛠️ admin · 📟 ESP32 com <code>X-Device-Key</code> + <code>X-Device-Id</code></p>
    @foreach ($groups as $title => $rows)
      <h2 style="margin-top:24px">{{ $title }}</h2>
      <div class="table-wrap"><table class="table">
        @foreach ($rows as [$method, $path, $auth, $desc])
          <tr><td class="nowrap"><span class="chip purple">{{ $method }}</span></td><td><code>{{ $path }}</code></td><td>{{ $auth }}</td><td class="small">{{ $desc }}</td></tr>
        @endforeach
      </table></div>
    @endforeach
  </div>
</div>
@endsection

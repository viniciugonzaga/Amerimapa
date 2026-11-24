@extends('layouts.teacher')
@section('title', 'Atividades do mapa')

@section('content')
<div class="page-head">
  <div><h1><x-icon name="map" /> Atividades do mapa</h1><p class="muted">Missões que os alunos jogam respondendo no mapa tátil.</p></div>
  <a class="btn btn-primary" href="{{ route('teacher.activities.create') }}"><x-icon name="plus" /> Criar atividade</a>
</div>

<section class="card" id="sala-de-aula" style="margin-bottom:18px">
  <div class="row wrap between">
    <div><h3 style="margin:0"><x-icon name="users" /> Modo Sala de Aula</h3><p class="small muted" style="margin:0">Projete a tela, conecte o mapa e conduza a turma: {{ $classroom?->time_limit_seconds }} s por pergunta, uma tentativa.</p></div>
    <form method="GET" action="{{ route('play') }}" class="row">
      <input type="hidden" name="modo" value="{{ $classroom?->id }}">
      <select class="input" name="atividade" required aria-label="Atividade">@foreach ($activities->where('is_active', true) as $a)<option value="{{ $a->id }}">{{ $a->title }}</option>@endforeach</select>
      <button class="btn btn-green" type="submit"><x-icon name="play" class="fill" /> Iniciar aula</button>
    </form>
  </div>
</section>

<section class="card">
  <div class="table-wrap"><table class="table">
    <thead><tr><th>Atividade</th><th>Fase</th><th>Modo</th><th>Turma</th><th>Perguntas</th><th>Partidas</th><th>Média de acertos</th><th></th></tr></thead>
    <tbody>
      @forelse ($activities as $a)
        <tr style="{{ $a->is_active ? '' : 'opacity:.55' }}">
          <td><div class="row"><div class="act"><div class="ico" style="width:38px;height:38px"><x-icon :name="$a->icon" /></div></div><div><b>{{ $a->title }}</b>@unless($a->is_active) <span class="chip gray">inativa</span>@endunless<div class="tiny muted">{{ $a->description }}</div></div></div></td>
          <td>{{ $a->phase->number }} · {{ $a->phase->title }}</td>
          <td><span class="chip purple">{{ $a->mode->name }}</span></td>
          <td>{{ $a->schoolClass?->name ?? 'Todas' }}</td>
          <td>{{ $a->questions_count }}</td>
          <td>{{ $a->matches_count }}</td>
          <td>@if(isset($accuracy[$a->id]))<x-bar :percent="$accuracy[$a->id]" /><span class="tiny bold">{{ round($accuracy[$a->id]) }}%</span>@else — @endif</td>
          <td class="nowrap">
            <a class="icon-btn" href="{{ route('teacher.activities.edit', $a) }}" aria-label="Editar"><x-icon name="edit" /></a>
            <form method="POST" action="{{ route('teacher.activities.destroy', $a) }}" style="display:inline" data-confirm="Remover/desativar esta atividade?">@csrf @method('DELETE')<button class="icon-btn" aria-label="Remover"><x-icon name="trash" /></button></form>
          </td>
        </tr>
      @empty
        <tr><td colspan="8"><div class="empty"><x-icon name="map" />Nenhuma atividade.</div></td></tr>
      @endforelse
    </tbody>
  </table></div>
</section>
@endsection

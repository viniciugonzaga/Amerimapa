@extends('layouts.teacher')
@section('title', 'Perguntas')

@section('content')
<div class="page-head">
  <div><h1><x-icon name="help" /> Banco de perguntas</h1><p class="muted">Cada pergunta tem enunciado, resposta (região do mapa), categoria e dificuldade (RN15). Fácil 10 · Média 20 · Difícil 30 pontos.</p></div>
  <a class="btn btn-primary" href="{{ route('teacher.questions.create') }}"><x-icon name="plus" /> Nova pergunta</a>
</div>

<form class="card row wrap" method="GET" style="margin-bottom:18px">
  <input class="input grow" type="search" name="q" value="{{ $f['q'] ?? '' }}" placeholder="Buscar enunciado…">
  <select class="input" name="phase_id" style="max-width:180px"><option value="">Todas as fases</option>@foreach ($phases as $p)<option value="{{ $p->id }}" @selected(($f['phase_id'] ?? '') == $p->id)>Fase {{ $p->number }} — {{ $p->title }}</option>@endforeach</select>
  <select class="input" name="category_id" style="max-width:180px"><option value="">Todas as categorias</option>@foreach ($categories as $c)<option value="{{ $c->id }}" @selected(($f['category_id'] ?? '') == $c->id)>{{ $c->name }}</option>@endforeach</select>
  <select class="input" name="difficulty" style="max-width:150px"><option value="">Dificuldade</option>@foreach (\App\Models\Question::DIFFICULTIES as $d)<option value="{{ $d }}" @selected(($f['difficulty'] ?? '') === $d)>{{ \App\Models\Question::difficultyLabel($d) }}</option>@endforeach</select>
  <button class="btn btn-outline" type="submit"><x-icon name="search" /> Filtrar</button>
</form>

<section class="card">
  <div class="table-wrap"><table class="table">
    <thead><tr><th>Enunciado</th><th>Resposta</th><th>Fase</th><th>Categoria</th><th>Dificuldade</th><th>Respostas</th><th></th></tr></thead>
    <tbody>
      @forelse ($questions as $q)
        <tr style="{{ $q->is_active ? '' : 'opacity:.5' }}">
          <td>{{ $q->prompt }} @unless($q->is_active)<span class="chip gray">inativa</span>@endunless</td>
          <td class="bold">{{ $q->answerRegion->name }}</td>
          <td>{{ $q->phase->number }}</td>
          <td>{{ $q->category->name }}</td>
          <td><span class="chip {{ ['EASY' => 'green', 'MEDIUM' => 'yellow', 'HARD' => 'red'][$q->difficulty] }}">{{ \App\Models\Question::difficultyLabel($q->difficulty) }}</span></td>
          <td>{{ $q->answers_count }}</td>
          <td class="nowrap">
            <a class="icon-btn" href="{{ route('teacher.questions.edit', $q) }}" aria-label="Editar"><x-icon name="edit" /></a>
            <form method="POST" action="{{ route('teacher.questions.destroy', $q) }}" style="display:inline" data-confirm="Remover/desativar esta pergunta?">@csrf @method('DELETE')<button class="icon-btn" aria-label="Remover"><x-icon name="trash" /></button></form>
          </td>
        </tr>
      @empty
        <tr><td colspan="7"><div class="empty"><x-icon name="help" />Nenhuma pergunta encontrada.</div></td></tr>
      @endforelse
    </tbody>
  </table></div>
  {{ $questions->links() }}
</section>
@endsection

@extends('layouts.teacher')
@section('title', $question->exists ? 'Editar pergunta' : 'Nova pergunta')

@section('content')
<div class="page-head"><div><a href="{{ route('teacher.questions.index') }}" class="small bold"><x-icon name="arrow-left" /> Perguntas</a><h1>{{ $question->exists ? 'Editar pergunta' : 'Nova pergunta' }}</h1></div></div>
<section class="card" style="max-width:820px">
  <form method="POST" action="{{ $question->exists ? route('teacher.questions.update', $question) : route('teacher.questions.store') }}" class="form-grid">
    @csrf
    @if ($question->exists) @method('PUT') @endif
    <label class="field full"><span>Enunciado</span><input class="input" name="prompt" value="{{ old('prompt', $question->prompt) }}" required maxlength="255" placeholder="Ex.: Encontre o Brasil"></label>
    <label class="field"><span>Resposta correta (região do mapa)</span>
      <select class="input" name="answer_region_id" required>
        @foreach ($regions->groupBy('type') as $type => $group)
          <optgroup label="{{ ['REGION' => 'Regiões', 'COUNTRY' => 'Países', 'STATE' => 'Estados'][$type] ?? $type }}">
            @foreach ($group as $r)<option value="{{ $r->id }}" @selected(old('answer_region_id', $question->answer_region_id) == $r->id)>{{ $r->name }}{{ $r->parent ? ' ('.$r->parent->name.')' : '' }}</option>@endforeach
          </optgroup>
        @endforeach
      </select>
    </label>
    <label class="field"><span>Dificuldade</span><select class="input" name="difficulty">@foreach (\App\Models\Question::DIFFICULTIES as $d)<option value="{{ $d }}" @selected(old('difficulty', $question->difficulty) === $d)>{{ \App\Models\Question::difficultyLabel($d) }} ({{ \App\Domain\ScoringPolicy::BASE_POINTS[$d] }} pts)</option>@endforeach</select></label>
    <label class="field"><span>Fase</span><select class="input" name="phase_id" required>@foreach ($phases as $p)<option value="{{ $p->id }}" @selected(old('phase_id', $question->phase_id) == $p->id)>Fase {{ $p->number }} — {{ $p->title }}</option>@endforeach</select></label>
    <label class="field"><span>Categoria</span><select class="input" name="category_id" required>@foreach ($categories as $c)<option value="{{ $c->id }}" @selected(old('category_id', $question->category_id) == $c->id)>{{ $c->name }}</option>@endforeach</select></label>
    <label class="field"><span>Áudio no cartão SD (DFPlayer)</span><input class="input" name="audio_path" value="{{ old('audio_path', $question->audio_path) }}" placeholder="mp3/0150.mp3"></label>
    <label class="check" style="align-self:end"><input type="checkbox" name="is_active" value="1" @checked(old('is_active', $question->is_active ?? true))> Pergunta ativa</label>
    <div class="row end full"><a class="btn btn-ghost" href="{{ route('teacher.questions.index') }}">Cancelar</a><button class="btn btn-green" type="submit"><x-icon name="save" /> Salvar</button></div>
  </form>
</section>
@endsection

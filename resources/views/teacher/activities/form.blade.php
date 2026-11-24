@extends('layouts.teacher')
@section('title', $activity->exists ? 'Editar atividade' : 'Nova atividade')

@section('content')
<div class="page-head">
  <div><a href="{{ route('teacher.activities.index') }}" class="small bold"><x-icon name="arrow-left" /> Atividades</a><h1>{{ $activity->exists ? 'Editar atividade' : 'Nova atividade' }}</h1></div>
</div>

<form method="POST" action="{{ $activity->exists ? route('teacher.activities.update', $activity) : route('teacher.activities.store') }}">
  @csrf
  @if ($activity->exists) @method('PUT') @endif
  <div class="grid g-2">
    <section class="card stack">
      <label class="field"><span>Título</span><input class="input" name="title" value="{{ old('title', $activity->title) }}" required maxlength="120" placeholder="Ex.: América do Sul: países e capitais"></label>
      <label class="field"><span>Descrição</span><input class="input" name="description" value="{{ old('description', $activity->description) }}" maxlength="255" placeholder="Ex.: Encontre os países no mapa tátil."></label>
      <div class="form-grid">
        <label class="field"><span>Fase (nível) — RN11</span><select class="input" name="phase_id" required>@foreach ($phases as $p)<option value="{{ $p->id }}" @selected(old('phase_id', $activity->phase_id) == $p->id)>Fase {{ $p->number }} — {{ $p->title }}</option>@endforeach</select></label>
        <label class="field"><span>Modo de jogo</span><select class="input" name="mode_id" required>@foreach ($modes as $m)<option value="{{ $m->id }}" @selected(old('mode_id', $activity->mode_id) == $m->id)>{{ $m->name }}</option>@endforeach</select></label>
        <label class="field"><span>Turma</span><select class="input" name="school_class_id"><option value="">Todas as turmas</option>@foreach ($classes as $c)<option value="{{ $c->id }}" @selected(old('school_class_id', $activity->school_class_id) == $c->id)>{{ $c->name }} · {{ $c->grade }}</option>@endforeach</select></label>
        <label class="field"><span>Aproximar o mapa em</span><select class="input" name="focus_region_id"><option value="">Mapa inteiro</option>@foreach ($regions as $r)<option value="{{ $r->id }}" @selected(old('focus_region_id', $activity->focus_region_id) == $r->id)>{{ $r->name }}</option>@endforeach</select></label>
        <label class="field"><span>Duração estimada (min)</span><input class="input" type="number" name="estimated_minutes" min="1" max="120" value="{{ old('estimated_minutes', $activity->estimated_minutes) }}" required></label>
        <label class="field"><span>Pontos de recompensa (exibição)</span><input class="input" type="number" name="reward_points" min="0" value="{{ old('reward_points', $activity->reward_points) }}" required></label>
        <label class="field"><span>Ícone</span><select class="input" name="icon">@foreach (\App\Models\Activity::ICONS as $icon)<option value="{{ $icon }}" @selected(old('icon', $activity->icon) === $icon)>{{ $icon }}</option>@endforeach</select></label>
        <label class="check" style="align-self:end"><input type="checkbox" name="is_active" value="1" @checked(old('is_active', $activity->is_active ?? true))> Atividade ativa</label>
      </div>
    </section>

    <section class="card" data-question-picker>
      <div class="card-title"><h3><x-icon name="help" /> Perguntas (<span data-q-count>0</span> selecionadas)</h3><a href="{{ route('teacher.questions.create') }}">Nova pergunta</a></div>
      <div class="row" style="margin-bottom:10px">
        <input class="input" type="search" placeholder="Filtrar por texto ou país…" data-q-search>
        <select class="input" data-q-phase style="max-width:160px"><option value="">Todas as fases</option>@foreach ($phases as $p)<option value="{{ $p->id }}">Fase {{ $p->number }}</option>@endforeach</select>
      </div>
      <div class="row" style="margin-bottom:8px"><button class="btn btn-sm btn-outline" type="button" data-q-all>Marcar visíveis</button><button class="btn btn-sm btn-ghost" type="button" data-q-none>Desmarcar visíveis</button></div>
      <div class="q-picker">
        @php($chosen = old('question_ids', $selected))
        @foreach ($questions as $q)
          <label data-phase="{{ $q->phase_id }}" data-text="{{ mb_strtolower($q->prompt.' '.$q->answerRegion->name.' '.$q->category->name) }}">
            <input type="checkbox" name="question_ids[]" value="{{ $q->id }}" @checked(in_array($q->id, $chosen))>
            <span class="grow">{{ $q->prompt }} <span class="tiny muted">→ {{ $q->answerRegion->name }}</span></span>
            <span class="chip {{ ['EASY' => 'green', 'MEDIUM' => 'yellow', 'HARD' => 'red'][$q->difficulty] }}">{{ \App\Models\Question::difficultyLabel($q->difficulty) }}</span>
          </label>
        @endforeach
      </div>
    </section>
  </div>
  <div class="row end" style="margin-top:18px"><a class="btn btn-ghost" href="{{ route('teacher.activities.index') }}">Cancelar</a><button class="btn btn-green btn-lg" type="submit"><x-icon name="save" /> Salvar atividade</button></div>
</form>
@endsection

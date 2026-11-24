@extends('layouts.teacher')
@section('title', 'Turmas e alunos')

@section('content')
<div class="page-head">
  <div><h1><x-icon name="users" /> Turmas e alunos</h1><p class="muted">Crie turmas, cadastre alunos e acompanhe cada grupo.</p></div>
  <button class="btn btn-primary" type="button" data-show="#nova-turma"><x-icon name="plus" /> Nova turma</button>
</div>

<section class="card hidden" id="nova-turma" style="margin-bottom:18px">
  <h3>Nova turma</h3>
  <form method="POST" action="{{ route('teacher.classes.store') }}" class="form-grid">
    @csrf
    <label class="field"><span>Nome</span><input class="input" name="name" placeholder="Ex.: Turma 6" required maxlength="80"></label>
    <label class="field"><span>Ano/série</span><input class="input" name="grade" placeholder="Ex.: 5º ano" maxlength="40"></label>
    <label class="field"><span>Cor</span><select class="input" name="color">@foreach (\App\Models\SchoolClass::COLORS as $color)<option value="{{ $color }}">{{ ucfirst($color) }}</option>@endforeach</select></label>
    <div class="field" style="align-self:end"><button class="btn btn-green btn-block" type="submit"><x-icon name="check" /> Criar turma</button></div>
  </form>
</section>

<div class="grid g-4">
  @forelse ($classes as $class)
    <a class="class-card" href="{{ route('teacher.classes.show', $class) }}">
      <div class="ico c-{{ $class->color }}"><x-icon name="users" /></div>
      <h3 style="margin:0">{{ $class->name }}</h3>
      <div class="muted small">{{ $class->grade ?: 'Sem série' }}</div>
      <div class="row between" style="margin-top:10px"><span class="chip">{{ $class->students_count }} alunos</span><x-icon name="arrow-right" /></div>
    </a>
  @empty
    <div class="card empty" style="grid-column:1/-1"><x-icon name="users" />Você ainda não tem turmas. Clique em <b>Nova turma</b>.</div>
  @endforelse
</div>
@endsection

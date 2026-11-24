@extends('layouts.teacher')
@section('title', 'Busca')

@section('content')
<div class="page-head"><h1><x-icon name="search" /> Resultados para “{{ $q }}”</h1></div>
<div class="grid g-3">
  <section class="card"><h3>Turmas</h3><div class="list">
    @forelse ($classes as $c)<a class="list-item" href="{{ route('teacher.classes.show', $c) }}"><div class="ico"><x-icon name="users" /></div>{{ $c->name }} · {{ $c->grade }}</a>@empty<p class="muted small">Nada encontrado.</p>@endforelse
  </div></section>
  <section class="card"><h3>Alunos</h3><div class="list">
    @forelse ($students as $s)<a class="list-item" href="{{ $s->schoolClass ? route('teacher.classes.show', $s->schoolClass) : '#' }}"><x-avatar :user="$s" size="sm" /><div><b>{{ $s->name }}</b><div class="tiny muted">{{ $s->email }} · {{ $s->schoolClass?->name ?? 'sem turma' }}</div></div></a>@empty<p class="muted small">Nada encontrado.</p>@endforelse
  </div></section>
  <section class="card"><h3>Atividades</h3><div class="list">
    @forelse ($activities as $a)<a class="list-item" href="{{ route('teacher.activities.edit', $a) }}"><div class="ico"><x-icon :name="$a->icon" /></div>{{ $a->title }}</a>@empty<p class="muted small">Nada encontrado.</p>@endforelse
  </div></section>
</div>
@endsection

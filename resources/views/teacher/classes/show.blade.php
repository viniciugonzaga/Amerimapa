@extends('layouts.teacher')
@section('title', $class->name)

@section('content')
<div class="page-head">
  <div><a href="{{ route('teacher.classes.index') }}" class="small bold"><x-icon name="arrow-left" /> Turmas</a>
    <h1><span class="avatar c-{{ $class->color }}" style="border-radius:12px">{{ mb_substr($class->name, -1) }}</span> {{ $class->name }} <span class="chip">{{ $class->grade }}</span></h1></div>
  <div class="row wrap">
    <a class="btn btn-outline" href="{{ route('teacher.reports', ['turma' => $class->id]) }}"><x-icon name="file" /> Relatório</a>
    <button class="btn btn-outline" type="button" data-show="#editar-turma"><x-icon name="edit" /> Editar</button>
    <button class="btn btn-primary" type="button" data-show="#novo-aluno"><x-icon name="plus" /> Novo aluno</button>
  </div>
</div>

<section class="card hidden" id="editar-turma" style="margin-bottom:18px">
  <form method="POST" action="{{ route('teacher.classes.update', $class) }}" class="form-grid">
    @csrf @method('PUT')
    <label class="field"><span>Nome</span><input class="input" name="name" value="{{ $class->name }}" required></label>
    <label class="field"><span>Ano/série</span><input class="input" name="grade" value="{{ $class->grade }}"></label>
    <label class="field"><span>Cor</span><select class="input" name="color">@foreach (\App\Models\SchoolClass::COLORS as $color)<option value="{{ $color }}" @selected($class->color === $color)>{{ ucfirst($color) }}</option>@endforeach</select></label>
    <div class="row" style="align-self:end"><button class="btn btn-green grow" type="submit">Salvar</button></div>
  </form>
  <form method="POST" action="{{ route('teacher.classes.destroy', $class) }}" data-confirm="Excluir a turma {{ $class->name }}? Os alunos continuam cadastrados." style="margin-top:10px">
    @csrf @method('DELETE')<button class="btn btn-danger btn-sm" type="submit"><x-icon name="trash" /> Excluir turma</button>
  </form>
</section>

<section class="card hidden" id="novo-aluno" style="margin-bottom:18px">
  <h3>Cadastrar aluno nesta turma</h3>
  <form method="POST" action="{{ route('teacher.classes.students.store', $class) }}" class="form-grid">
    @csrf
    <label class="field"><span>Nome completo</span><input class="input" name="name" required></label>
    <label class="field"><span>E-mail (login)</span><input class="input" type="email" name="email" required></label>
    <label class="field"><span>Senha inicial</span><input class="input" name="password" required minlength="8" value="{{ \Illuminate\Support\Str::lower(\Illuminate\Support\Str::random(8)) }}"></label>
    <div style="align-self:end"><button class="btn btn-green btn-block" type="submit"><x-icon name="plus" /> Cadastrar</button></div>
  </form>
  <p class="small muted">Anote a senha inicial e entregue ao aluno. Ele pode trocá-la em Meu perfil.</p>
</section>

<div class="grid g-side">
  <section class="card">
    <div class="card-title"><h3><x-icon name="users" /> Alunos ({{ $report->count() }})</h3></div>
    <div class="table-wrap"><table class="table">
      <thead><tr><th>Aluno</th><th>Partidas</th><th>Pontos</th><th>Acertos</th><th>Última vez</th><th></th></tr></thead>
      <tbody>
        @forelse ($report as $r)
          @php($s = $r['student'])
          <tr>
            <td><div class="row"><x-avatar :user="$s" size="sm" /><div><b>{{ $s->name }}</b><div class="tiny muted">{{ $s->email }}</div></div></div></td>
            <td>{{ $r['completed'] }}/{{ $r['matches'] }}</td>
            <td class="bold">{{ number_format($r['points'], 0, ',', '.') }}</td>
            <td>@if($r['accuracy'] !== null)<span class="chip {{ $r['accuracy'] >= 70 ? 'green' : ($r['accuracy'] >= 50 ? 'yellow' : 'red') }}">{{ $r['accuracy'] }}%</span>@else — @endif</td>
            <td class="small muted">{{ $r['lastPlayed']?->diffForHumans() ?? 'nunca' }}</td>
            <td class="nowrap">
              <button class="icon-btn" type="button" data-show="#edit-{{ $s->id }}" aria-label="Editar {{ $s->name }}"><x-icon name="edit" /></button>
              <form method="POST" action="{{ route('teacher.students.destroy', $s) }}" style="display:inline" data-confirm="Excluir {{ $s->name }} e todo o histórico?">@csrf @method('DELETE')<button class="icon-btn" type="submit" aria-label="Excluir"><x-icon name="trash" /></button></form>
            </td>
          </tr>
          <tr id="edit-{{ $s->id }}" class="hidden"><td colspan="6">
            <form method="POST" action="{{ route('teacher.students.update', $s) }}" class="form-grid">
              @csrf @method('PUT')
              <label class="field"><span>Nome</span><input class="input" name="name" value="{{ $s->name }}" required></label>
              <label class="field"><span>E-mail</span><input class="input" type="email" name="email" value="{{ $s->email }}" required></label>
              <label class="field"><span>Turma</span><select class="input" name="school_class_id">@foreach (app(\App\Services\StatsService::class)->classesOf($me) as $opt)<option value="{{ $opt->id }}" @selected($opt->id === $s->school_class_id)>{{ $opt->name }}</option>@endforeach</select></label>
              <label class="field"><span>Nova senha (opcional)</span><input class="input" name="password" minlength="8" placeholder="deixe em branco para manter"></label>
              <button class="btn btn-green full" type="submit">Salvar aluno</button>
            </form>
          </td></tr>
        @empty
          <tr><td colspan="6"><div class="empty"><x-icon name="user" />Nenhum aluno nesta turma.</div></td></tr>
        @endforelse
      </tbody>
    </table></div>
  </section>

  <aside class="stack">
    <section class="card">
      <div class="card-title"><h3><x-icon name="chart" /> Acertos (7 dias)</h3></div>
      @php($chartData = ['labels' => $chart['labels'], 'series' => [['name' => $class->name, 'color' => $class->color, 'values' => $chart['values']]]])
      <div class="chart" data-chart='@json($chartData)'></div>
    </section>
    <section class="card">
      <div class="card-title"><h3><x-icon name="map" /> Atividades da turma</h3><a href="{{ route('teacher.activities.create') }}">Nova</a></div>
      <div class="list">
        @forelse ($activities as $a)
          <a class="list-item" href="{{ route('teacher.activities.edit', $a) }}"><div class="ico"><x-icon :name="$a->icon" /></div><div class="grow small"><b>{{ $a->title }}</b><div class="tiny muted">{{ $a->questions_count }} perguntas</div></div></a>
        @empty
          <p class="small muted">Só as atividades gerais. Crie uma específica para esta turma.</p>
        @endforelse
      </div>
    </section>
  </aside>
</div>
@endsection

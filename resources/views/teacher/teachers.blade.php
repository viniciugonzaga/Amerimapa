@extends('layouts.teacher')
@section('title', 'Professores')

@section('content')
<div class="page-head">
  <div><h1><x-icon name="book" /> Professores</h1><p class="muted">Somente administradores veem esta tela. Professores não podem se cadastrar sozinhos.</p></div>
  <button class="btn btn-primary" type="button" data-show="#novo-prof"><x-icon name="plus" /> Novo professor</button>
</div>

<section class="card hidden" id="novo-prof" style="margin-bottom:18px">
  <form method="POST" action="{{ route('teacher.teachers.store') }}" class="form-grid">
    @csrf
    <label class="field"><span>Nome</span><input class="input" name="name" required></label>
    <label class="field"><span>E-mail</span><input class="input" type="email" name="email" required></label>
    <label class="field"><span>Senha inicial</span><input class="input" name="password" required minlength="8" value="{{ \Illuminate\Support\Str::lower(\Illuminate\Support\Str::random(10)) }}"></label>
    <label class="field"><span>Perfil</span><select class="input" name="role"><option value="TEACHER">Professor(a)</option><option value="ADMIN">Administrador(a)</option></select></label>
    <button class="btn btn-green full" type="submit"><x-icon name="check" /> Cadastrar</button>
  </form>
</section>

<section class="card">
  <div class="table-wrap"><table class="table">
    <thead><tr><th>Nome</th><th>E-mail</th><th>Perfil</th><th>Turmas</th><th>Último acesso</th><th></th></tr></thead>
    <tbody>
      @foreach ($teachers as $t)
        <tr>
          <td><div class="row"><x-avatar :user="$t" size="sm" /><b>{{ $t->name }}</b></div></td>
          <td>{{ $t->email }}</td>
          <td><span class="chip {{ $t->role === 'ADMIN' ? 'purple' : '' }}">{{ $t->roleLabel() }}</span></td>
          <td>{{ $t->teaching_classes_count }}</td>
          <td class="small muted">{{ $t->last_login_at?->diffForHumans() ?? 'nunca' }}</td>
          <td class="nowrap">
            <button class="icon-btn" type="button" data-show="#t-{{ $t->id }}" aria-label="Editar"><x-icon name="edit" /></button>
            @if ($t->id !== $me->id)
              <form method="POST" action="{{ route('teacher.teachers.destroy', $t) }}" style="display:inline" data-confirm="Excluir {{ $t->name }}?">@csrf @method('DELETE')<button class="icon-btn" aria-label="Excluir"><x-icon name="trash" /></button></form>
            @endif
          </td>
        </tr>
        <tr id="t-{{ $t->id }}" class="hidden"><td colspan="6">
          <form method="POST" action="{{ route('teacher.teachers.update', $t) }}" class="form-grid">
            @csrf @method('PUT')
            <label class="field"><span>Nome</span><input class="input" name="name" value="{{ $t->name }}" required></label>
            <label class="field"><span>E-mail</span><input class="input" type="email" name="email" value="{{ $t->email }}" required></label>
            <label class="field"><span>Perfil</span><select class="input" name="role"><option value="TEACHER" @selected($t->role === 'TEACHER')>Professor(a)</option><option value="ADMIN" @selected($t->role === 'ADMIN')>Administrador(a)</option></select></label>
            <label class="field"><span>Nova senha (opcional)</span><input class="input" name="password" minlength="8" placeholder="deixe em branco para manter"></label>
            <button class="btn btn-green full" type="submit">Salvar</button>
          </form>
        </td></tr>
      @endforeach
    </tbody>
  </table></div>
</section>
@endsection

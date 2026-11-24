@extends('layouts.guest')
@section('title', 'Criar conta')

@section('content')
<div class="container landing" style="max-width:520px">
  <div class="card" style="padding:28px">
    <h1>Criar conta de aluno</h1>
    <p class="muted">Com uma conta, seu progresso, pontos e conquistas ficam salvos. Professores recebem acesso pela escola.</p>
    @if ($errors->any())<div class="alert error"><x-icon name="x" /> {{ $errors->first() }}</div>@endif
    <form method="POST" action="{{ route('register') }}" class="stack">
      @csrf
      <label class="field"><span>Nome completo</span><input class="input" name="name" value="{{ old('name') }}" required maxlength="120" autocomplete="name"></label>
      <label class="field"><span>E-mail</span><input class="input" type="email" name="email" value="{{ old('email') }}" required autocomplete="email"></label>
      <label class="field"><span>Senha (mínimo 8 caracteres)</span><input class="input" type="password" name="password" required minlength="8" autocomplete="new-password"></label>
      <label class="field"><span>Confirme a senha</span><input class="input" type="password" name="password_confirmation" required minlength="8" autocomplete="new-password"></label>
      <button class="btn btn-green btn-block btn-lg" type="submit">Criar conta <x-icon name="arrow-right" /></button>
    </form>
    <p class="center small" style="margin-top:14px">Já tem conta? <a href="{{ route('login') }}" class="bold">Entrar</a></p>
  </div>
</div>
@endsection

@extends('layouts.guest')
@section('title', 'Esqueci minha senha')

@section('content')
<div class="container landing" style="max-width:520px">
  <div class="card" style="padding:28px">
    <div class="center"><x-icon name="lock" style="width:48px;height:48px;color:var(--blue)" /></div>
    <h1 class="center">Esqueceu a senha?</h1>
    <p class="muted center">Digite seu e-mail. Seu professor ou professora recebe o pedido no painel e cria uma nova senha para você.</p>
    @if (session('status'))<div class="alert success"><x-icon name="check-circle" /> {{ session('status') }}</div>@endif
    @if ($errors->any())<div class="alert error"><x-icon name="x" /> {{ $errors->first() }}</div>@endif
    <form method="POST" action="{{ route('password.email') }}" class="stack">
      @csrf
      <div class="input-icon"><x-icon name="send" /><input class="input" type="email" name="email" value="{{ old('email') }}" placeholder="Seu e-mail" required aria-label="E-mail"></div>
      <button class="btn btn-primary btn-block" type="submit">Pedir nova senha <x-icon name="arrow-right" /></button>
    </form>
    <p class="center small" style="margin-top:14px"><a class="bold" href="{{ route('login') }}"><x-icon name="arrow-left" /> Voltar para o login</a></p>
  </div>
</div>
@endsection

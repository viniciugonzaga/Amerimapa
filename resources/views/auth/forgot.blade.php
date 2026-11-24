@extends('layouts.guest')
@section('title', 'Esqueci minha senha')

@section('content')
<div class="container landing" style="max-width:520px">
  <div class="card center" style="padding:28px">
    <x-icon name="lock" class="" style="width:48px;height:48px;color:var(--blue)" />
    <h1>Esqueceu a senha?</h1>
    <p class="muted">Sem problemas! Peça ao seu professor ou professora para criar uma nova senha para você em <b>Turmas e alunos</b>. Professores podem pedir à administração da escola.</p>
    <a class="btn btn-primary" href="{{ route('login') }}"><x-icon name="arrow-left" /> Voltar para o login</a>
  </div>
</div>
@endsection

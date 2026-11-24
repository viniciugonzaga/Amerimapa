@extends('layouts.base')

@section('body')
<header class="container" style="display:flex;justify-content:space-between;align-items:center;padding-top:18px">
  <x-logo />
  <span class="handwrite">Aprender geografia também é uma grande aventura!</span>
</header>
<main id="conteudo">@yield('content')</main>
<footer class="footer">
  <div class="container row between wrap">
    <div class="row"><x-logo /><span>Geografia que conecta pessoas.</span></div>
    <div class="row wrap"><a href="{{ route('page', 'termos') }}">Termos de uso</a> · <a href="{{ route('page', 'privacidade') }}">Política de privacidade</a> · <a href="{{ route('page', 'suporte') }}">Suporte</a></div>
  </div>
</footer>
@endsection

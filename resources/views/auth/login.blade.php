@extends('layouts.guest')
@section('title', 'Bem-vindo')

@section('content')
<div class="container landing">
  <div class="landing-hero">
    <section>
      <span class="chip yellow"><x-icon name="sparkles" /> Explore · Descubra · Aprenda</span>
      <h1 class="welcome-title">Bem-vindo ao<br><span class="rainbow">AmeriMapa!</span></h1>
      <p class="muted" style="font-size:1.1rem;max-width:560px">Uma plataforma interativa que une tecnologia, educação e inclusão para tornar o aprendizado de geografia mais acessível, divertido e significativo para todos.</p>

      <div style="position:relative;max-width:560px;margin:18px 0">
        <img src="{{ asset('images/ilustracao-boas-vindas.png') }}" alt="Duas crianças explorando o mapa tátil das Américas" style="width:100%;max-width:420px;border-radius:22px;display:block">
        <span class="handwrite" style="position:absolute;right:10px;top:8px">Geografia tátil e interativa!</span>
      </div>

      <div class="grid g-4" style="max-width:560px">
        <div class="feature"><div class="ico" style="background:var(--green-50);color:var(--green)"><x-icon name="globe" /></div>Explore a América</div>
        <div class="feature"><div class="ico" style="background:var(--purple-50);color:var(--purple)"><x-icon name="target" /></div>Responda às atividades</div>
        <div class="feature"><div class="ico" style="background:var(--yellow-50);color:#c58b00"><x-icon name="trophy" /></div>Conquiste pontos e evolua</div>
        <div class="feature"><div class="ico" style="background:var(--red-50);color:var(--red)"><x-icon name="accessibility" /></div>Acessível a todos</div>
      </div>
    </section>

    <section class="card" style="padding:26px">
      <div class="row"><div class="setting" style="padding:0;border:0;background:none"><span class="ico"><x-icon name="user" /></span></div><div><h2 style="margin:0">Acesse sua conta</h2><p class="muted small" style="margin:0">Escolha como deseja entrar no AmeriMapa.</p></div></div>

      <form method="POST" action="{{ route('login') }}" class="stack" style="margin-top:6px">
        @csrf
        <div class="role-tabs">
          <label><input type="radio" name="role" value="student" {{ old('role', 'student') === 'student' ? 'checked' : '' }}><div><b><x-icon name="user" /> Aluno</b><small>Acesse suas atividades, progresso e conquistas.</small></div></label>
          <label><input type="radio" name="role" value="teacher" {{ old('role') === 'teacher' ? 'checked' : '' }}><div><b><x-icon name="book" /> Professor</b><small>Gerencie turmas, crie atividades e acompanhe os resultados.</small></div></label>
        </div>
        @if ($errors->any())<div class="alert error"><x-icon name="x" /> {{ $errors->first() }}</div>@endif
        <div class="input-icon"><x-icon name="send" /><input class="input" type="email" name="email" value="{{ old('email') }}" placeholder="E-mail" required autocomplete="email" aria-label="E-mail"></div>
        <div class="input-icon"><x-icon name="lock" /><input class="input" type="password" name="password" placeholder="Senha" required autocomplete="current-password" aria-label="Senha"></div>
        <div class="row between">
          <label class="check"><input type="checkbox" name="remember" value="1"> Lembrar de mim</label>
          <a class="small bold" href="{{ route('password.request') }}">Esqueceu sua senha?</a>
        </div>
        <button class="btn btn-primary btn-block btn-lg" type="submit">Entrar <x-icon name="arrow-right" /></button>
      </form>

      <div class="divider">ou</div>
      <a class="btn btn-outline btn-block" href="{{ route('register') }}"><x-icon name="plus-circle" /> Criar uma conta</a>
      <form method="POST" action="{{ route('guest.start') }}" style="margin-top:10px">
        @csrf
        <button class="btn btn-ghost btn-block" type="submit"><x-icon name="play" /> Jogar como visitante (sem salvar progresso)</button>
      </form>
      <p class="small muted" style="margin-top:14px"><x-icon name="accessibility" /> Acessibilidade: aumentar fonte, alto contraste, instruções por áudio e mais — em <b>Meu perfil</b>.</p>
    </section>
  </div>

  <div class="grid g-4" style="margin-top:28px">
    <div class="card soft-yellow"><h3><x-icon name="compass" /> Conheça a América de um jeito diferente!</h3><p class="small">Com o mapa tátil interativo, você é capaz de explorar os países, culturas e curiosidades de forma prática, intuitiva e inclusiva.</p></div>
    <div class="card soft-green"><h3><x-icon name="book" /> Para uma educação mais acessível!</h3><p class="small">O AmeriMapa foi desenvolvido pensando em todos os estudantes, incluindo aqueles com deficiência visual, garantindo que ninguém fique de fora.</p></div>
    <div class="card soft-pink"><h3><x-icon name="trophy" /> Desafios e conquistas</h3><p class="small">Participe de atividades, acumule pontos, suba no ranking e desbloqueie conquistas enquanto aprende sobre os países da América.</p></div>
    <div class="card soft-blue"><h3><x-icon name="star" /> Cada país é uma nova descoberta!</h3><p class="small"><span class="handwrite">Vamos explorar?</span></p></div>
  </div>
</div>
@endsection

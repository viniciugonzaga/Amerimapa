@extends($layout)
@section('title', 'Meu perfil e acessibilidade')

@section('content')
<div class="page-head">
  <div><h1><x-icon name="user" /> Meu perfil e acessibilidade</h1><p class="muted">Personalize sua experiência no AmeriMapa e deixe o jogo do seu jeito!</p></div>
  <span class="handwrite">Do seu jeito!</span>
</div>
@if ($errors->any() && $layout === 'layouts.student')<div class="alert error"><x-icon name="x" /> {{ $errors->first() }}</div>@endif

<div class="grid g-2">
  <div class="stack">
    {{-- Cartão do perfil --}}
    <section class="card soft-blue">
      <div class="row wrap">
        <x-avatar :user="$user" size="lg" />
        <div class="grow">
          <h2 style="margin:0">{{ $user->name }}</h2>
          <div class="row wrap" style="gap:6px;margin-top:4px">
            <span class="chip"><x-icon name="user" /> {{ $user->isStaff() ? $user->roleLabel() : 'Aluno(a)' }}</span>
            @if ($user->schoolClass)<span class="chip purple">{{ $user->schoolClass->name }} · {{ $user->schoolClass->grade }}</span>@endif
          </div>
          <p class="small muted" style="margin:6px 0 0">Apelido: <b>{{ $user->nickname ?: '—' }}</b> · {{ $user->email }}</p>
        </div>
        <button class="btn btn-outline btn-sm" type="button" data-show="#editar-perfil"><x-icon name="edit" /> Editar perfil</button>
      </div>
    </section>

    {{-- Edição (abre pelo botão "Editar perfil" ou quando há erro) --}}
    <section class="card {{ $errors->any() ? '' : 'hidden' }}" id="editar-perfil">
      <div class="card-title"><h3><x-icon name="edit" /> Editar perfil</h3></div>
      <form method="POST" action="{{ route('profile.update') }}" class="stack">
        @csrf @method('PUT')
        <fieldset style="border:0;padding:0;margin:0"><legend class="field bold small" style="margin-bottom:8px">Escolha seu avatar</legend>
          <div class="row wrap avatar-pick">
            @foreach (\App\Models\User::AVATARS as $av)
              <label title="{{ ucfirst($av) }}"><input class="sr-only" type="radio" name="avatar" value="{{ $av }}" @checked(old('avatar', $user->avatar) === $av)><x-avatar :key="$av" :name="$user->name" /></label>
            @endforeach
          </div>
        </fieldset>
        <label class="field"><span>Nome</span><input class="input" name="name" value="{{ old('name', $user->name) }}" required maxlength="120"></label>
        <label class="field"><span>Apelido <small class="muted">(como você quer ser chamado)</small></span><input class="input" name="nickname" value="{{ old('nickname', $user->nickname) }}" maxlength="60"></label>
        <label class="field"><span>E-mail</span><input class="input" type="email" name="email" value="{{ old('email', $user->email) }}" required></label>
        <button class="btn btn-green btn-block" type="submit"><x-icon name="save" /> Salvar alterações</button>
      </form>
      <hr style="border:0;border-top:1px solid var(--line);margin:18px 0">
      <h3><x-icon name="lock" /> Trocar senha</h3>
      <form method="POST" action="{{ route('profile.password') }}" class="form-grid">
        @csrf @method('PUT')
        <label class="field full"><span>Senha atual</span><input class="input" type="password" name="current_password" required autocomplete="current-password"></label>
        <label class="field"><span>Nova senha</span><input class="input" type="password" name="password" required minlength="8" autocomplete="new-password"></label>
        <label class="field"><span>Confirmar</span><input class="input" type="password" name="password_confirmation" required minlength="8" autocomplete="new-password"></label>
        <button class="btn btn-outline full" type="submit">Alterar senha</button>
      </form>
    </section>

    {{-- Como usar o mapa tátil --}}
    <section class="card">
      <div class="card-title"><h3><x-icon name="hand" style="color:var(--yellow)" /> Como usar o mapa tátil</h3></div>
      <div class="row wrap" style="align-items:center">
        <img src="{{ asset('images/ilustracao-mapa-tatil.png') }}" alt="Criança tocando no mapa tátil" style="width:170px;border-radius:14px">
        <div class="grow">
          <p>Toque na região indicada para responder às perguntas. O mapa acende <b style="color:var(--green)">verde</b> no acerto, <b style="color:#c58b00">amarelo</b> para tentar de novo e <b style="color:var(--red)">vermelho</b> quando a pergunta termina.</p>
          <button class="btn btn-outline" type="button" data-speak="Bem-vindo ao AmeriMapa! Para responder, ouça a pergunta e toque no país certo no mapa tátil. Se o mapa acender verde, você acertou! Amarelo quer dizer: tente de novo. Você também pode tocar no mapa da tela."><x-icon name="volume" /> Ouvir tutorial</button>
        </div>
      </div>
    </section>
  </div>

  <div class="stack">
    <section class="card" id="acessibilidade">
      <div class="card-title"><h3><x-icon name="accessibility" style="color:var(--green)" /> Acessibilidade</h3></div>
      <p class="small muted">Deixe o AmeriMapa do seu jeito! As mudanças valem na hora e ficam salvas na sua conta.</p>
      @php($p = $user->prefs())
      <form method="POST" action="{{ route('profile.preferences') }}">
        @csrf @method('PUT')
        @foreach ([
          ['large_font', 'type', 'Aumentar tamanho da fonte', 'Deixa o texto maior e mais fácil de ler.', 'a11y-large-font'],
          ['high_contrast', 'contrast', 'Alto contraste', 'Melhora a visibilidade das cores e do texto.', 'a11y-high-contrast'],
          ['audio_instructions', 'volume', 'Instruções por áudio', 'Ouça as instruções do jogo e das atividades.', ''],
          ['reduced_motion', 'sparkles', 'Animações reduzidas', 'Diminui os efeitos de movimento e transição.', 'a11y-reduced-motion'],
        ] as [$field, $icon, $title, $desc, $class])
          <div class="setting">
            <span class="ico"><x-icon :name="$icon" /></span>
            <div class="grow"><b>{{ $title }}</b><div class="small muted">{{ $desc }}</div></div>
            <label class="switch"><input type="checkbox" name="{{ $field }}" value="1" data-pref="{{ $class }}" @checked($p->{$field}) aria-label="{{ $title }}"><span></span></label>
          </div>
        @endforeach
        <noscript><button class="btn btn-primary" type="submit" style="margin-top:12px">Salvar preferências</button></noscript>
      </form>
    </section>

    @unless ($user->isStaff())
    <section class="card">
      <div class="card-title"><h3><x-icon name="star" style="color:var(--yellow)" /> Meu resumo</h3></div>
      <div class="grid g-3" style="gap:8px">
        <a class="stat yellow" href="{{ route('progress') }}"><div><div class="val">{{ number_format((int) $user->matches()->sum('total_score'), 0, ',', '.') }}</div><div class="lbl">pontos</div></div></a>
        <a class="stat blue" href="{{ route('progress') }}"><div><div class="val">{{ $user->matches()->where('status', 'COMPLETED')->count() }}</div><div class="lbl">partidas</div></div></a>
        <a class="stat green" href="{{ route('achievements') }}"><div><div class="val">{{ $user->achievements()->count() }}</div><div class="lbl">conquistas</div></div></a>
      </div>
    </section>
    @endunless

    <form method="POST" action="{{ route('logout') }}">
      @csrf
      <button class="card row between" type="submit" style="width:100%;cursor:pointer;font:inherit;text-align:left">
        <span class="row"><span class="setting" style="padding:8px;border:0;background:var(--red-50);color:var(--red)"><x-icon name="logout" /></span><b>Sair da conta</b></span>
        <x-icon name="chevron-right" />
      </button>
    </form>
  </div>
</div>
@endsection

@extends($layout)
@section('title', 'Meu perfil e acessibilidade')

@section('content')
<div class="page-head">
  <div><h1><x-icon name="user" /> Meu perfil e acessibilidade</h1><p class="muted">Personalize sua experiência no AmeriMapa e deixe o jogo do seu jeito!</p></div>
</div>
@if ($errors->any() && $layout === 'layouts.student')<div class="alert error"><x-icon name="x" /> {{ $errors->first() }}</div>@endif

<div class="grid g-2">
  <div class="stack">
    <section class="card">
      <div class="card-title"><h3><x-icon name="user" /> Meu perfil</h3></div>
      <form method="POST" action="{{ route('profile.update') }}" class="stack">
        @csrf @method('PUT')
        <div class="row"><x-avatar :user="$user" size="lg" /><div><h2 style="margin:0">{{ $user->name }}</h2><span class="chip">{{ $user->roleLabel() }}</span>@if($user->schoolClass) <span class="chip purple">{{ $user->schoolClass->name }} · {{ $user->schoolClass->grade }}</span>@endif</div></div>
        <fieldset style="border:0;padding:0;margin:0"><legend class="field bold small" style="margin-bottom:6px">Escolha seu avatar</legend>
          <div class="row wrap">
            @foreach (\App\Models\User::AVATARS as $av)
              <label style="cursor:pointer"><input class="sr-only" type="radio" name="avatar" value="{{ $av }}" @checked(old('avatar', $user->avatar) === $av)><span class="avatar av-{{ $av }}" style="{{ $user->avatar === $av ? 'outline:3px solid var(--blue)' : '' }}" title="{{ ucfirst($av) }}">{{ $user->initials() }}</span></label>
            @endforeach
          </div>
        </fieldset>
        <label class="field"><span>Nome</span><input class="input" name="name" value="{{ old('name', $user->name) }}" required maxlength="120"></label>
        <label class="field"><span>Apelido <small class="muted">(como você quer ser chamado)</small></span><input class="input" name="nickname" value="{{ old('nickname', $user->nickname) }}" maxlength="60"></label>
        <label class="field"><span>E-mail</span><input class="input" type="email" name="email" value="{{ old('email', $user->email) }}" required></label>
        <button class="btn btn-outline btn-block" type="submit"><x-icon name="save" /> Salvar alterações</button>
      </form>
    </section>

    <section class="card">
      <div class="card-title"><h3><x-icon name="lock" /> Trocar senha</h3></div>
      <form method="POST" action="{{ route('profile.password') }}" class="form-grid">
        @csrf @method('PUT')
        <label class="field full"><span>Senha atual</span><input class="input" type="password" name="current_password" required autocomplete="current-password"></label>
        <label class="field"><span>Nova senha</span><input class="input" type="password" name="password" required minlength="8" autocomplete="new-password"></label>
        <label class="field"><span>Confirmar</span><input class="input" type="password" name="password_confirmation" required minlength="8" autocomplete="new-password"></label>
        <button class="btn btn-outline full" type="submit">Alterar senha</button>
      </form>
    </section>
  </div>

  <div class="stack">
    <section class="card" id="acessibilidade">
      <div class="card-title"><h3><x-icon name="accessibility" style="color:var(--green)" /> Acessibilidade</h3></div>
      <p class="small muted">As mudanças valem na hora e ficam salvas na sua conta.</p>
      @php($p = $user->prefs())
      <form method="POST" action="{{ route('profile.preferences') }}">
        @csrf @method('PUT')
        @foreach ([
          ['large_font', 'type', 'Aumentar tamanho da fonte', 'Deixa o texto maior e mais fácil de ler.', 'a11y-large-font'],
          ['high_contrast', 'contrast', 'Alto contraste', 'Melhora a visibilidade das cores e do texto.', 'a11y-high-contrast'],
          ['audio_instructions', 'volume', 'Instruções por áudio', 'Ouça as instruções do jogo e das atividades.', ''],
          ['reduced_motion', 'sparkles', 'Reduzir animações', 'Deixa a navegação mais simples e leve.', 'a11y-reduced-motion'],
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

    <section class="card soft-blue">
      <div class="card-title"><h3><x-icon name="bulb" style="color:var(--purple)" /> Ajuda para usar o mapa tátil</h3></div>
      <p>Toque na região indicada para responder às perguntas. O mapa acende <b style="color:var(--green)">verde</b> quando você acerta, <b style="color:#c58b00">amarelo</b> para tentar de novo e <b style="color:var(--red)">vermelho</b> quando a pergunta termina sem acerto.</p>
      <button class="btn btn-outline" type="button" data-speak="Bem-vindo ao AmeriMapa! Para responder, ouça a pergunta e toque no país certo no mapa tátil. Se o mapa piscar verde, você acertou! Amarelo quer dizer: tente de novo. Você também pode tocar no mapa da tela."><x-icon name="volume" /> Ouvir tutorial</button>
    </section>

    @unless ($user->isStaff())
    <section class="card">
      <div class="card-title"><h3><x-icon name="logout" style="color:var(--red)" /> Conta</h3></div>
      <form method="POST" action="{{ route('logout') }}">@csrf<button class="btn btn-danger btn-block" type="submit"><x-icon name="logout" /> Sair da conta</button></form>
    </section>
    @endunless
  </div>
</div>
@endsection

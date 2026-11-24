@extends('layouts.guest')
@section('title', 'Política de privacidade')
@section('content')
<div class="container landing" style="max-width:780px"><div class="card stack">
  <h1>Política de privacidade</h1>
  <p>Guardamos apenas o necessário para o jogo funcionar: nome, apelido, e-mail, turma, preferências de acessibilidade e o histórico de partidas (respostas, tempo de resposta e pontos).</p>
  <p>Partidas de visitantes não ficam ligadas a nenhuma pessoa e não entram no ranking. As senhas são armazenadas com hash (bcrypt) e os tokens de acesso apenas como hash SHA-256.</p>
  <p>Os dados são usados somente para acompanhamento pedagógico e não são compartilhados com terceiros.</p>
</div></div>
@endsection

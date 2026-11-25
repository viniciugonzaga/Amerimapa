<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="csrf-token" content="{{ csrf_token() }}">
  @if (!empty($apiToken))<meta name="api-token" content="{{ $apiToken }}">@endif
  <meta name="api-base" content="{{ url('/api/v1') }}">
  <meta name="a11y-audio" content="{{ $prefs->audio_instructions ? '1' : '0' }}">
  @if ($me)<meta name="player-id" content="{{ $me->id }}">@endif
  <title>@yield('title', 'AmeriMapa') · AmeriMapa</title>
  <link rel="icon" href="{{ asset('images/Amerimapa-icone.png') }}" type="image/png">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Baloo+2:wght@600;700;800&family=Caveat:wght@600&family=Nunito:wght@500;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="{{ asset('css/amerimapa.css') }}?v=1">
  @stack('head')
</head>
<body class="{{ $prefs->bodyClasses() }} @yield('body-class')">
  @include('partials.icons')
  <a href="#conteudo" class="sr-only">Pular para o conteúdo</a>
  @yield('body')
  <div class="toasts" id="toasts" aria-live="polite"></div>
  <script src="{{ asset('js/amerimapa.js') }}?v=1"></script>
  @stack('scripts')
</body>
</html>

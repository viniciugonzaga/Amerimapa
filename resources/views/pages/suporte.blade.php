@extends('layouts.guest')
@section('title', 'Suporte')
@section('content')
<div class="container landing" style="max-width:780px"><div class="card stack">
  <h1>Suporte</h1>
  <h3>O mapa aparece como desconectado</h3>
  <p>Verifique se o ESP32 está ligado, conectado ao Wi-Fi e se a chave <code>DEVICE_API_KEY</code> do firmware é igual à do servidor. O mapa envia um sinal a cada 5 segundos; sem sinal por 15 segundos ele é marcado como desconectado e as respostas pelo mapa ficam pausadas (RN20). Enquanto isso, é possível responder pelo mapa da tela.</p>
  <h3>Um país não responde ao toque</h3>
  <p>No painel do professor, em <b>Regiões e pinos</b>, confira se o país tem o pino correto (ex.: <code>5B:11</code>). Em <b>Dispositivo IoT</b> aparecem os últimos toques recebidos.</p>
  <h3>Esqueci minha senha</h3>
  <p>O professor pode definir uma nova senha em <b>Turmas e alunos</b>.</p>
</div></div>
@endsection

@extends('layouts.teacher')
@section('title', 'Relatórios')

@section('content')
<div class="page-head">
  <div><h1><x-icon name="file" /> Relatórios</h1><p class="muted">Desempenho por aluno — pronto para levar ao conselho de classe.</p></div>
  <div class="row wrap">
    @include('teacher.partials.class-filter', ['route' => 'teacher.reports'])
    <a class="btn btn-primary" href="{{ route('teacher.reports.export', ['turma' => $class?->id]) }}"><x-icon name="download" /> Exportar CSV</a>
  </div>
</div>
@php
    $withData = $report->filter(fn ($r) => $r['accuracy'] !== null);
@endphp
<div class="grid g-4" style="margin-bottom:18px">
  <div class="kpi blue"><div class="small bold muted">Alunos</div><div class="val">{{ $report->count() }}</div></div>
  <div class="kpi green"><div class="small bold muted">Partidas concluídas</div><div class="val">{{ $report->sum('completed') }}</div></div>
  <div class="kpi yellow"><div class="small bold muted">Pontos somados</div><div class="val">{{ number_format($report->sum('points'), 0, ',', '.') }}</div></div>
  <div class="kpi red"><div class="small bold muted">Média de acertos</div><div class="val">{{ $withData->isEmpty() ? '—' : round($withData->avg('accuracy')).'%' }}</div></div>
</div>
<section class="card">
  <div class="table-wrap"><table class="table">
    <thead><tr><th>Aluno</th><th>Turma</th><th>Partidas</th><th>Concluídas</th><th>Pontos</th><th>Acertos</th><th>Melhor sequência</th><th>Última partida</th></tr></thead>
    <tbody>
      @forelse ($report as $r)
        <tr>
          <td><b>{{ $r['student']->name }}</b></td>
          <td>{{ $r['student']->schoolClass?->name }}</td>
          <td>{{ $r['matches'] }}</td><td>{{ $r['completed'] }}</td>
          <td class="bold">{{ number_format($r['points'], 0, ',', '.') }}</td>
          <td>@if($r['accuracy'] !== null)<span class="chip {{ $r['accuracy'] >= 70 ? 'green' : ($r['accuracy'] >= 50 ? 'yellow' : 'red') }}">{{ $r['accuracy'] }}%</span>@else<span class="chip gray">sem dados</span>@endif</td>
          <td>{{ $r['bestStreak'] }}</td>
          <td class="small muted">{{ $r['lastPlayed']?->format('d/m/Y H:i') ?? '—' }}</td>
        </tr>
      @empty
        <tr><td colspan="8"><div class="empty"><x-icon name="file" />Nenhum aluno.</div></td></tr>
      @endforelse
    </tbody>
  </table></div>
</section>
@endsection

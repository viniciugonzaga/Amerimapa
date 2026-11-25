<form method="GET" class="row" style="gap:6px">
  @foreach (request()->except('periodo') as $k => $v)<input type="hidden" name="{{ $k }}" value="{{ $v }}">@endforeach
  <select class="input" name="periodo" onchange="this.form.submit()" aria-label="Período" style="padding:6px 10px;font-size:.85rem;width:auto">
    @foreach (\App\Services\StatsService::PERIODS as $d)<option value="{{ $d }}" @selected(($days ?? 7) === $d)>Últimos {{ $d }} dias</option>@endforeach
  </select>
  <noscript><button class="btn btn-sm btn-outline">OK</button></noscript>
</form>

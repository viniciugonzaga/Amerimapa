<form method="GET" action="{{ route($route) }}" class="row">
  <select class="input" name="turma" onchange="this.form.submit()" aria-label="Turma">
    <option value="">Todas as turmas</option>
    @foreach ($classes as $c)<option value="{{ $c->id }}" @selected($class?->id === $c->id)>{{ $c->name }} · {{ $c->grade }}</option>@endforeach
  </select>
  <noscript><button class="btn btn-outline" type="submit">Filtrar</button></noscript>
</form>

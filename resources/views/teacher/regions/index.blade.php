@extends('layouts.teacher')
@section('title', 'Regiões e pinos')

@section('content')
<div class="page-head">
  <div><h1><x-icon name="pin" /> Regiões e pinos do mapa</h1><p class="muted">Cada área física tem um identificador único (RN19): <code>&lt;endereço do MPR121&gt;:&lt;eletrodo&gt;</code>, ex.: <code>5B:11</code>. Para religar um ponto de cobre, basta trocar o pino aqui — sem mudar o firmware.</p></div>
  <button class="btn btn-primary" type="button" data-show="#nova-regiao"><x-icon name="plus" /> Nova região</button>
</div>

<section class="card hidden" id="nova-regiao" style="margin-bottom:18px">
  <h3>Nova região (ex.: um estado do Brasil — RN22)</h3>
  <form method="POST" action="{{ route('teacher.regions.store') }}" class="form-grid">
    @csrf
    <label class="field"><span>Nome</span><input class="input" name="name" required></label>
    <label class="field"><span>Tipo</span><select class="input" name="type"><option value="STATE">Estado</option><option value="COUNTRY">País</option><option value="REGION">Região</option></select></label>
    <label class="field"><span>Pertence a</span><select class="input" name="parent_id"><option value="">—</option>@foreach ($parents as $p)<option value="{{ $p->id }}">{{ $p->name }}</option>@endforeach</select></label>
    <label class="field"><span>Pino</span><input class="input" name="hardware_pin" placeholder="5C:11"></label>
    <label class="field"><span>Capital</span><input class="input" name="capital"></label>
    <label class="field"><span>Idioma</span><input class="input" name="language"></label>
    <label class="field full"><span>Curiosidade</span><input class="input" name="info"></label>
    <button class="btn btn-green full" type="submit">Cadastrar</button>
  </form>
</section>

@foreach ($groups as $g)
  <section class="card" style="margin-bottom:18px">
    <div class="card-title"><h3><x-icon name="globe" /> {{ $g->name }}</h3><span class="chip">{{ $g->children->count() }} áreas</span></div>
    <div class="table-wrap"><table class="table">
      <thead><tr><th>Nome</th><th>Pino</th><th>ISO</th><th>Capital</th><th>Idioma</th><th></th></tr></thead>
      <tbody>
        @foreach ($g->children as $r)
          <tr>
            @php($fid = 'r-'.$r->id)
            <td><input form="{{ $fid }}" class="input" name="name" value="{{ $r->name }}" required style="min-width:160px"></td>
            <td><input form="{{ $fid }}" class="input" name="hardware_pin" value="{{ $r->hardware_pin }}" style="width:100px"></td>
            <td><input form="{{ $fid }}" class="input" name="iso_code" value="{{ $r->iso_code }}" style="width:80px" maxlength="3"></td>
            <td><input form="{{ $fid }}" class="input" name="capital" value="{{ $r->capital }}"></td>
            <td><input form="{{ $fid }}" class="input" name="language" value="{{ $r->language }}"></td>
            <td>
              <form id="{{ $fid }}" method="POST" action="{{ route('teacher.regions.update', $r) }}">
                @csrf @method('PUT')
                <input type="hidden" name="type" value="{{ $r->type }}"><input type="hidden" name="parent_id" value="{{ $r->parent_id }}"><input type="hidden" name="info" value="{{ $r->info }}">
                <button class="icon-btn" type="submit" aria-label="Salvar {{ $r->name }}"><x-icon name="save" /></button>
              </form>
            </td>
          </tr>
        @endforeach
      </tbody>
    </table></div>
  </section>
@endforeach
@endsection

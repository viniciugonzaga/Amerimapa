@props(['percent' => 0, 'size' => ''])
@php
    $r = 52;
    $c = 2 * M_PI * $r;
    $p = max(0, min(100, (int) $percent));
@endphp
<div {{ $attributes->merge(['class' => "ring {$size}"]) }} role="img" aria-label="{{ $p }}%">
  <svg viewBox="0 0 120 120"><circle class="bg" cx="60" cy="60" r="{{ $r }}"/><circle class="fg" cx="60" cy="60" r="{{ $r }}" stroke-dasharray="{{ $c }}" stroke-dashoffset="{{ $c * (1 - $p / 100) }}"/></svg>
  <div class="lbl">{{ $p }}%</div>
</div>

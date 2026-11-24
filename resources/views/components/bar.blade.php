@props(['percent' => 0, 'color' => ''])
<div class="bar {{ $color }}" role="progressbar" aria-valuenow="{{ (int) $percent }}" aria-valuemin="0" aria-valuemax="100"><i style="width: {{ max(0, min(100, (int) $percent)) }}%"></i></div>

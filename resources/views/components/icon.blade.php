@props(['name', 'class' => ''])
<svg {{ $attributes->merge(['class' => 'icon '.$class]) }} aria-hidden="true"><use href="#i-{{ $name }}"/></svg>

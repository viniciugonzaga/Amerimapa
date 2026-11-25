@props(['size' => ''])
<a href="{{ route('home') }}" {{ $attributes->merge(['class' => "logo {$size}"]) }} aria-label="AmeriMapa — início">
  <img src="{{ asset('images/Amerimapa.png') }}" alt="AmeriMapa">
</a>

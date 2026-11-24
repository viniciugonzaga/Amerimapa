@props(['size' => ''])
<a href="{{ route('home') }}" class="logo {{ $size }}" aria-label="AmeriMapa — início">
  <svg viewBox="0 0 64 64" aria-hidden="true">
    <circle cx="30" cy="34" r="24" fill="#4aa8f0"/>
    <path d="M17 18c5-2 9 1 10 4 1 4-3 5-2 9 1 3 5 3 6 7 1 5-3 9-5 12-5-2-9-6-11-11-2-4 3-6 2-10-1-3-4-5 0-11z" fill="#5bc06b"/>
    <path d="M34 39c4-1 8 2 9 6 1 3-2 6-5 8-3-2-6-5-6-8 0-3 0-5 2-6z" fill="#5bc06b"/>
    <path d="M44 2a10 10 0 0 0-10 10c0 7 10 17 10 17s10-10 10-17A10 10 0 0 0 44 2z" fill="#ff8a1f"/>
    <circle cx="44" cy="12" r="4" fill="#fff"/>
  </svg>
  <span>Ameri<span class="m1">M</span><span class="m2">a</span><span class="m3">p</span><span class="m4">a</span></span>
</a>

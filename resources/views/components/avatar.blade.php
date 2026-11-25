@props(['user' => null, 'size' => '', 'key' => null, 'name' => null])
@php
    $key = $key ?? $user?->avatar ?? 'nuvem';
    $label = $name ?? $user?->name;
    $parts = preg_split('/\s+/', trim((string) $label)) ?: [''];
    $initials = $label ? mb_strtoupper(mb_substr($parts[0], 0, 1).(count($parts) > 1 ? mb_substr(end($parts), 0, 1) : '')) : 'V';
@endphp
@if (in_array($key, \App\Models\User::IMAGE_AVATARS, true))
  <img {{ $attributes->merge(['class' => "avatar img {$size}"]) }} src="{{ asset('images/avatars/'.$key.'.png') }}" alt="">
@else
  <span {{ $attributes->merge(['class' => "avatar av-{$key} {$size}"]) }} aria-hidden="true">{{ $initials }}</span>
@endif

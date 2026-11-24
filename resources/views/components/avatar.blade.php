@props(['user' => null, 'size' => ''])
@php($key = $user?->avatar ?? 'nuvem')
<span {{ $attributes->merge(['class' => "avatar av-{$key} {$size}"]) }} aria-hidden="true">{{ $user ? $user->initials() : 'V' }}</span>

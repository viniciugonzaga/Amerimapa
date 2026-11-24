@props(['a'])
<div class="medal {{ $a['status'] === 'UNLOCKED' ? '' : 'locked' }}" title="{{ $a['description'] }}">
  <div class="disc m-{{ $a['color'] }}"><x-icon :name="$a['icon']" /></div>
  {{ $a['name'] }}
</div>

@props(['name' => '', 'src' => null, 'size' => 40, 'open' => false, 'square' => false])
@php
    // Initials on a colour picked from the name: stable for the same business or person.
    $palette = ['#1877f2', '#42b72a', '#f5533d', '#a033ff', '#f7b928', '#0a7c86', '#e42645', '#4b4c4f'];
    $clean = trim((string) $name);
    $initial = $clean === '' ? '?' : mb_strtoupper(mb_substr($clean, 0, 1));
    $color = $palette[abs(crc32($clean)) % count($palette)];
    $radius = $square ? 'rounded-lg' : 'rounded-full';
@endphp
<span {{ $attributes->merge(['class' => 'relative inline-flex shrink-0']) }} style="width: {{ (int) $size }}px; height: {{ (int) $size }}px">
    @if ($src)
        <img src="{{ $src }}" alt="" width="{{ (int) $size }}" height="{{ (int) $size }}" loading="lazy" decoding="async"
             class="size-full {{ $radius }} object-cover">
    @else
        <span class="flex size-full items-center justify-center {{ $radius }} font-bold text-white" aria-hidden="true"
              style="background: {{ $color }}; font-size: {{ max(12, (int) round($size * 0.42)) }}px">{{ $initial }}</span>
    @endif
    @if ($open)
        <span class="fb-presence" title="{{ __('Ouvert') }}"></span>
    @endif
</span>

@props(['icon' => null, 'size' => 24])
@php
    // Read once per request (App\Support\Icons\Lucide); unknown names fall back to "store".
    $inner = \App\Support\Icons\Lucide::inner($icon);
@endphp
@if ($inner !== null)
    <svg xmlns="http://www.w3.org/2000/svg" width="{{ $size }}" height="{{ $size }}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" {{ $attributes }}>{!! $inner !!}</svg>
@else
    <x-ui.icon name="store" :size="$size" {{ $attributes }} />
@endif

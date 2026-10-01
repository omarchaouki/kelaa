@props(['rating' => 0, 'size' => 16])
@php
    $rating = max(0, min(5, (float) $rating));
    $full = (int) floor($rating);
    $half = ($rating - $full) >= 0.5;
@endphp
<span {{ $attributes->merge(['class' => 'inline-flex items-center gap-px text-accent-400']) }} role="img"
      aria-label="{{ __(':rating sur 5', ['rating' => number_format($rating, 1, ',', ' ')]) }}">
    @for ($i = 1; $i <= 5; $i++)
        <svg width="{{ $size }}" height="{{ $size }}" viewBox="0 0 24 24" aria-hidden="true"
             class="{{ $i <= $full || ($i === $full + 1 && $half) ? 'fill-current' : 'fill-stone-300' }}">
            <path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/>
        </svg>
    @endfor
</span>

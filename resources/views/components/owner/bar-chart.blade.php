@props(['series', 'label'])
{{-- Server-rendered SVG chart: no chart library, no inline script, CSP-friendly. --}}
@php
    $values = array_values($series);
    $dates = array_keys($series);
    $max = max(1, ...$values);
    $count = max(1, count($values));
    $width = 600;
    $height = 140;
    $gap = 2;
    $bar = max(1, ($width - $gap * ($count - 1)) / $count);
@endphp
<figure {{ $attributes->merge(['class' => 'w-full']) }}>
    <svg viewBox="0 0 {{ $width }} {{ $height + 4 }}" class="h-36 w-full" role="img" aria-label="{{ $label }}" preserveAspectRatio="none">
        @foreach ($values as $i => $value)
            @php $h = $value / $max * $height; @endphp
            <rect x="{{ round($i * ($bar + $gap), 2) }}" y="{{ round($height - $h, 2) }}" width="{{ round($bar, 2) }}" height="{{ round(max($h, 0.5), 2) }}"
                  rx="2" class="{{ $value > 0 ? 'fill-brand-500' : 'fill-stone-200' }}">
                <title>{{ \Illuminate\Support\Carbon::parse($dates[$i])->locale(app()->getLocale())->isoFormat('D MMM') }} : {{ $value }}</title>
            </rect>
        @endforeach
    </svg>
    <figcaption class="mt-1 flex justify-between text-xs text-stone-500" dir="ltr">
        <span>{{ \Illuminate\Support\Carbon::parse($dates[0] ?? now())->locale(app()->getLocale())->isoFormat('D MMM') }}</span>
        <span>{{ \Illuminate\Support\Carbon::parse(end($dates) ?: now())->locale(app()->getLocale())->isoFormat('D MMM') }}</span>
    </figcaption>
</figure>

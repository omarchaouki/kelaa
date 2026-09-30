@props(['business', 'city', 'status' => null, 'placement' => null, 'source' => 'search'])
@php
    $primary = $business->categories->firstWhere('pivot.is_primary', true) ?? $business->categories->first();
    // Paid "highlighted card" changes the look only, never the position.
    $highlighted = (bool) $business->plan?->feature('highlighted_card') && $business->plan_expires_at?->isFuture();
    $url = route('city.business', array_filter([
        'city' => $city,
        'business' => $business,
        'src' => $placement ? 'sponsored' : $source,
        'pl' => $placement?->id,
    ]));
    $thumb = $business->getFirstMediaUrl('gallery', 'thumb') ?: null;
@endphp
<article {{ $attributes->merge(['class' => 'relative flex gap-3 rounded-xl border bg-white p-3 '.($placement ? 'border-accent-400/60' : ($highlighted ? 'border-brand-300 shadow-sm' : 'border-stone-200'))]) }}>
    @if ($thumb)
        <img src="{{ $thumb }}" alt="" width="96" height="72" loading="lazy" decoding="async"
             class="h-18 w-24 shrink-0 rounded-lg object-cover">
    @endif

    <div class="min-w-0 flex-1">
        @if ($placement)
            <x-directory.sponsored-label class="mb-1" />
        @endif

        <h3 class="truncate font-semibold">
            <a href="{{ $url }}" wire:navigate class="after:absolute after:inset-0 hover:text-brand-700">{{ $business->name }}</a>
            @if ($business->is_verified)
                <span class="ms-1 align-middle text-brand-600" title="{{ __('Vérifié') }}">
                    <x-ui.icon name="badge-check" size="16" class="inline" />
                    <span class="sr-only">{{ __('Vérifié') }}</span>
                </span>
            @endif
        </h3>

        <p class="truncate text-sm text-stone-600">
            {{ $primary?->name }}@if ($business->neighborhood) · {{ $business->neighborhood->name }}@endif
            @isset($business->distance_m)
                · <span dir="ltr">{{ \Illuminate\Support\Number::format($business->distance_m / 1000, precision: 1, locale: app()->getLocale()) }} km</span>
            @endisset
        </p>

        <div class="mt-1 flex flex-wrap items-center gap-x-3 gap-y-1 text-sm">
            @if ($business->rating_count > 0)
                <span class="inline-flex items-center gap-1">
                    <x-directory.stars :rating="$business->rating_avg" size="14" />
                    <span class="text-stone-600">{{ number_format($business->rating_avg, 1, ',', ' ') }} ({{ $business->rating_count }})</span>
                </span>
            @else
                <span class="text-stone-500">{{ __('Pas encore d’avis') }}</span>
            @endif
            <x-directory.open-badge :status="$status" />
        </div>
    </div>
</article>

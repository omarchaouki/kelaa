@props(['business', 'city', 'status' => null, 'placement' => null, 'source' => 'search', 'framed' => true])
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
    $picture = $business->getFirstMediaUrl('logo', 'thumb') ?: ($business->getFirstMediaUrl('gallery', 'thumb') ?: null);
@endphp
<article {{ $attributes->class([
    'relative flex items-center gap-3',
    'fb-card p-3' => $framed,
    'rounded-lg p-2 hover:bg-stone-100' => ! $framed,
    'ring-2 ring-brand-200' => $highlighted && $framed,
]) }}>
    <x-ui.avatar :name="$business->name" :src="$picture" size="56" :open="(bool) $status?->isOpen" />

    <div class="min-w-0 flex-1">
        <h3 class="flex items-center gap-1 text-[15px] font-semibold leading-5">
            <a href="{{ $url }}" wire:navigate class="truncate after:absolute after:inset-0 hover:underline">{{ $business->name }}</a>
            @if ($business->is_verified)
                <span class="shrink-0 text-brand-600" title="{{ __('Vérifié') }}">
                    <x-ui.icon name="badge-check" size="16" class="fill-brand-600 stroke-white" />
                    <span class="sr-only">{{ __('Vérifié') }}</span>
                </span>
            @endif
        </h3>

        @if ($placement)
            <x-directory.sponsored-label />
        @endif

        <p class="truncate text-[13px] text-stone-600">
            {{ $primary?->name }}@if ($business->neighborhood) · {{ $business->neighborhood->name }}@endif
            @isset($business->distance_m)
                · <span dir="ltr">{{ \Illuminate\Support\Number::format($business->distance_m / 1000, precision: 1, locale: app()->getLocale()) }} km</span>
            @endisset
        </p>

        <div class="mt-0.5 flex flex-wrap items-center gap-x-2 gap-y-0.5 text-[13px]">
            @if ($business->rating_count > 0)
                <span class="inline-flex items-center gap-1">
                    <x-directory.stars :rating="$business->rating_avg" size="13" />
                    <span class="text-stone-600">{{ number_format($business->rating_avg, 1, ',', ' ') }} ({{ $business->rating_count }})</span>
                </span>
            @else
                <span class="text-stone-500">{{ __('Pas encore d’avis') }}</span>
            @endif
            <x-directory.open-badge :status="$status" />
        </div>
    </div>

    <x-ui.icon name="chevron-right" size="20" class="shrink-0 text-stone-400 rtl:rotate-180" />
</article>

@php
    $priceLevels = $filters->priceRanges;
@endphp
<div class="space-y-4">
    <nav aria-label="{{ __('Fil d’Ariane') }}" class="text-sm text-stone-500">
        <ol class="flex flex-wrap items-center gap-1">
            <li><a href="{{ route('city.home', ['city' => $city]) }}" wire:navigate class="hover:underline">{{ $city->name }}</a></li>
            @if ($category?->parent)
                <li aria-hidden="true">›</li>
                <li><a href="{{ route('city.category', ['city' => $city, 'category' => $category->parent]) }}" wire:navigate class="hover:underline">{{ $category->parent->name }}</a></li>
            @endif
            @if ($category)
                <li aria-hidden="true">›</li>
                <li class="text-stone-700">{{ $category->name }}</li>
            @endif
        </ol>
    </nav>

    <header class="space-y-3">
        <h1 class="text-2xl font-bold">
            @if ($category)
                {{ $neighborhood ? __(':category à :neighborhood', ['category' => $category->name, 'neighborhood' => $neighborhood->name]) : $category->name }}
            @else
                {{ __('Rechercher à :city', ['city' => $city->name]) }}
            @endif
        </h1>

        <div role="search" class="relative">
            <label for="q" class="sr-only">{{ __('Rechercher un service') }}</label>
            <span class="pointer-events-none absolute inset-y-0 start-3 flex items-center text-stone-400"><x-ui.icon name="search" /></span>
            <input id="q" type="search" wire:model.live.debounce.400ms="q" maxlength="{{ \App\Services\Search\SearchFilters::MAX_QUERY_LENGTH }}"
                   placeholder="{{ __('Plombier, pharmacie, mécanicien…') }}"
                   class="block w-full rounded-xl border border-stone-300 bg-white py-2.5 pe-3 ps-10 focus:border-brand-600 focus:outline-none focus:ring-2 focus:ring-brand-600/30">
        </div>
    </header>

    {{-- Filters --}}
    <section aria-label="{{ __('Filtres') }}" class="-mx-4 flex items-center gap-2 overflow-x-auto whitespace-nowrap px-4 pb-1 text-sm sm:mx-0 sm:flex-wrap sm:px-0" x-data="geolocate">
        <button type="button" wire:click="toggle('open')" aria-pressed="{{ $filters->openNow ? 'true' : 'false' }}"
                @class(['rounded-full border px-3 py-1.5', 'border-brand-600 bg-brand-50 text-brand-800' => $filters->openNow, 'border-stone-300 bg-white' => ! $filters->openNow])>
            {{ __('Ouvert maintenant') }}
        </button>
        <button type="button" wire:click="toggle('verified')" aria-pressed="{{ $filters->verifiedOnly ? 'true' : 'false' }}"
                @class(['rounded-full border px-3 py-1.5', 'border-brand-600 bg-brand-50 text-brand-800' => $filters->verifiedOnly, 'border-stone-300 bg-white' => ! $filters->verifiedOnly])>
            {{ __('Vérifiés') }}
        </button>

        <label class="sr-only" for="rating">{{ __('Note minimum') }}</label>
        <select id="rating" wire:model.live="rating" class="rounded-full border border-stone-300 bg-white px-3 py-1.5">
            <option value="">{{ __('Toutes les notes') }}</option>
            @foreach (\App\Services\Search\SearchFilters::MIN_RATINGS as $value)
                <option value="{{ $value }}">{{ __(':rating+ étoiles', ['rating' => number_format($value, 1, ',', ' ')]) }}</option>
            @endforeach
        </select>

        @if ($neighborhoods->isNotEmpty() && $pathNeighborhoodId === null)
            <label class="sr-only" for="quartier">{{ __('Quartier') }}</label>
            <select id="quartier" wire:model.live="neighborhood" class="rounded-full border border-stone-300 bg-white px-3 py-1.5">
                <option value="">{{ __('Tous les quartiers') }}</option>
                @foreach ($neighborhoods as $option)
                    <option value="{{ $option->slug }}">{{ $option->name }}</option>
                @endforeach
            </select>
        @endif

        <div class="flex items-center gap-1" role="group" aria-label="{{ __('Prix') }}">
            @foreach ([1, 2, 3, 4] as $level)
                <button type="button" wire:click="togglePrice({{ $level }})" aria-pressed="{{ in_array($level, $priceLevels, true) ? 'true' : 'false' }}"
                        @class(['rounded-full border px-2.5 py-1.5', 'border-brand-600 bg-brand-50 text-brand-800' => in_array($level, $priceLevels, true), 'border-stone-300 bg-white' => ! in_array($level, $priceLevels, true)])>
                    {{ \App\Support\PriceRange::label($level) }}
                </button>
            @endforeach
        </div>

        @if ($hasLocation)
            <label class="sr-only" for="radius">{{ __('Distance') }}</label>
            <select id="radius" wire:model.live="radius" class="rounded-full border border-stone-300 bg-white px-3 py-1.5">
                <option value="">{{ __('Toute la ville') }}</option>
                @foreach (\App\Services\Search\SearchFilters::RADII_KM as $km)
                    <option value="{{ $km }}">{{ __('< :km km', ['km' => $km]) }}</option>
                @endforeach
            </select>
        @else
            <button type="button" x-on:click="locate" x-bind:disabled="locating" class="inline-flex items-center gap-1 rounded-full border border-stone-300 bg-white px-3 py-1.5">
                <x-ui.icon name="locate" size="14" /> {{ __('Près de moi') }}
            </button>
        @endif

        <label class="sr-only" for="sort">{{ __('Trier par') }}</label>
        <select id="sort" wire:model.live="sort" class="ms-auto rounded-full border border-stone-300 bg-white px-3 py-1.5">
            @foreach ($sorts as $option)
                @continue($option === \App\Enums\SearchSort::Distance && ! $hasLocation)
                <option value="{{ $option->value }}">{{ $option->getLabel() }}</option>
            @endforeach
        </select>
    </section>

    <div class="flex items-center justify-between">
        <p class="text-sm text-stone-600" role="status">
            {{ trans_choice(':count résultat|:count résultats', $results->total(), ['count' => $results->total()]) }}
            @if ($filters->openNow || $filters->verifiedOnly || $filters->minRating || $priceLevels || $filters->radiusKm || $filters->hasText())
                · <button type="button" wire:click="resetFilters" class="underline">{{ __('Effacer les filtres') }}</button>
            @endif
        </p>
        <div class="flex rounded-lg border border-stone-300 bg-white p-0.5 text-sm" role="group" aria-label="{{ __('Affichage') }}">
            <button type="button" wire:click="$set('view', 'list')" aria-pressed="{{ $view === 'list' ? 'true' : 'false' }}"
                    @class(['flex items-center gap-1 rounded-md px-2.5 py-1', 'bg-brand-600 text-white' => $view === 'list'])>
                <x-ui.icon name="list" size="16" /> {{ __('Liste') }}
            </button>
            <button type="button" wire:click="$set('view', 'map')" aria-pressed="{{ $view === 'map' ? 'true' : 'false' }}"
                    @class(['flex items-center gap-1 rounded-md px-2.5 py-1', 'bg-brand-600 text-white' => $view === 'map'])>
                <x-ui.icon name="map" size="16" /> {{ __('Carte') }}
            </button>
        </div>
    </div>

    @if ($view === 'map')
        <div
             x-data="directoryMap(@js([
                 'center' => ['lat' => $city->center_latitude, 'lng' => $city->center_longitude],
                 'zoom' => $city->default_zoom,
                 'tiles' => ['url' => config('directory.maps.tile_url'), 'attribution' => config('directory.maps.attribution'), 'maxZoom' => config('directory.maps.max_zoom')],
                 'labels' => ['sponsored' => __('Sponsorisé')],
             ]))"
             data-markers="{{ json_encode($markers, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE) }}"
             class="overflow-hidden rounded-xl border border-stone-200">
            <div x-ref="canvas" wire:ignore class="h-[60vh] min-h-80 w-full"></div>
        </div>
    @endif

    @if ($sponsored->isNotEmpty() && $view === 'list')
        <section aria-label="{{ __('Sponsorisé') }}" class="space-y-2">
            @foreach ($sponsored as $placement)
                <x-directory.business-card :business="$placement->business" :city="$city" :placement="$placement"
                                           :status="$statuses[$placement->business_id] ?? null" wire:key="sponsored-{{ $placement->id }}" />
            @endforeach
        </section>
    @endif

    @if ($view === 'list')
        <section aria-label="{{ __('Résultats') }}" class="space-y-2">
            @forelse ($results as $business)
                <x-directory.business-card :business="$business" :city="$city" :status="$statuses[$business->id] ?? null"
                                           :source="$filters->hasText() ? 'search' : 'organic'" wire:key="result-{{ $business->id }}" />
            @empty
                <p class="rounded-xl border border-dashed border-stone-300 p-6 text-center text-stone-600">
                    {{ __('Aucun résultat. Essayez un autre mot ou retirez des filtres.') }}
                </p>
            @endforelse
        </section>
    @endif

    <div>
        {{ $results->onEachSide(1)->links(data: ['scrollTo' => '#main']) }}
    </div>
</div>

@if ($results->hasPages())
    @push('head')
        @if ($results->previousPageUrl())
            <link rel="prev" href="{{ $results->previousPageUrl() }}">
        @endif
        @if ($results->nextPageUrl())
            <link rel="next" href="{{ $results->nextPageUrl() }}">
        @endif
    @endpush
@endif

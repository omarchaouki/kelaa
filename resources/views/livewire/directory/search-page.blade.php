@php
    $priceLevels = $filters->priceRanges;
    $select = 'rounded-full border-0 bg-stone-200 py-1.5 ps-3 pe-8 text-[14px] font-semibold text-stone-900 hover:bg-stone-300 focus:outline-2 focus:outline-brand-600';
@endphp
<div class="grid gap-4 lg:grid-cols-[340px_minmax(0,680px)] lg:justify-center">
    {{-- Left rail: what you searched and how to narrow it. --}}
    <aside class="fb-card space-y-4 self-start p-4 lg:sticky lg:top-[72px]" x-data="geolocate">
        <nav aria-label="{{ __('Fil d’Ariane') }}" class="text-[13px] text-stone-600">
            <ol class="flex flex-wrap items-center gap-1">
                <li><a href="{{ route('city.home', ['city' => $city]) }}" wire:navigate class="hover:underline">{{ $city->name }}</a></li>
                @if ($category?->parent)
                    <li aria-hidden="true">›</li>
                    <li><a href="{{ route('city.category', ['city' => $city, 'category' => $category->parent]) }}" wire:navigate class="hover:underline">{{ $category->parent->name }}</a></li>
                @endif
                @if ($category)
                    <li aria-hidden="true">›</li>
                    <li class="text-stone-900">{{ $category->name }}</li>
                @endif
            </ol>
        </nav>

        <h1 class="text-[24px] font-bold leading-7">
            @if ($category)
                {{ $neighborhood ? __(':category à :neighborhood', ['category' => $category->name, 'neighborhood' => $neighborhood->name]) : $category->name }}
            @else
                {{ __('Rechercher à :city', ['city' => $city->name]) }}
            @endif
        </h1>

        <div role="search" class="relative">
            <label for="q" class="sr-only">{{ __('Rechercher un service') }}</label>
            <span class="pointer-events-none absolute inset-y-0 start-3 flex items-center text-stone-600"><x-ui.icon name="search" size="16" /></span>
            <input id="q" type="search" wire:model.live.debounce.400ms="q" maxlength="{{ \App\Services\Search\SearchFilters::MAX_QUERY_LENGTH }}"
                   placeholder="{{ __('Plombier, pharmacie, mécanicien…') }}" class="fb-input h-10 ps-9">
        </div>

        <div class="fb-divider"></div>

        <section aria-label="{{ __('Filtres') }}" class="space-y-3">
            <h2 class="text-[17px] font-semibold">{{ __('Filtres') }}</h2>

            <div class="-mx-4 flex gap-2 overflow-x-auto px-4 pb-1 lg:mx-0 lg:flex-wrap lg:px-0">
                <button type="button" wire:click="toggle('open')" aria-pressed="{{ $filters->openNow ? 'true' : 'false' }}" class="fb-chip">
                    <span class="size-2 rounded-full bg-online" aria-hidden="true"></span> {{ __('Ouvert maintenant') }}
                </button>
                <button type="button" wire:click="toggle('verified')" aria-pressed="{{ $filters->verifiedOnly ? 'true' : 'false' }}" class="fb-chip">
                    <x-ui.icon name="badge-check" size="14" /> {{ __('Vérifiés') }}
                </button>
                @foreach ([1, 2, 3, 4] as $level)
                    <button type="button" wire:click="togglePrice({{ $level }})" aria-pressed="{{ in_array($level, $priceLevels, true) ? 'true' : 'false' }}"
                            class="fb-chip" aria-label="{{ __('Prix') }} {{ \App\Support\PriceRange::label($level) }}">
                        {{ \App\Support\PriceRange::label($level) }}
                    </button>
                @endforeach
                @unless ($hasLocation)
                    <button type="button" x-on:click="locate" x-bind:disabled="locating" class="fb-chip">
                        <x-ui.icon name="locate" size="14" /> {{ __('Près de moi') }}
                    </button>
                @endunless
            </div>

            <div class="grid gap-2 sm:grid-cols-2 lg:grid-cols-1">
                <label class="sr-only" for="rating">{{ __('Note minimum') }}</label>
                <select id="rating" wire:model.live="rating" class="{{ $select }}">
                    <option value="">{{ __('Toutes les notes') }}</option>
                    @foreach (\App\Services\Search\SearchFilters::MIN_RATINGS as $value)
                        <option value="{{ $value }}">{{ __(':rating+ étoiles', ['rating' => number_format($value, 1, ',', ' ')]) }}</option>
                    @endforeach
                </select>

                @if ($neighborhoods->isNotEmpty() && $pathNeighborhoodId === null)
                    <label class="sr-only" for="quartier">{{ __('Quartier') }}</label>
                    <select id="quartier" wire:model.live="neighborhood" class="{{ $select }}">
                        <option value="">{{ __('Tous les quartiers') }}</option>
                        @foreach ($neighborhoods as $option)
                            <option value="{{ $option->slug }}">{{ $option->name }}</option>
                        @endforeach
                    </select>
                @endif

                @if ($hasLocation)
                    <label class="sr-only" for="radius">{{ __('Distance') }}</label>
                    <select id="radius" wire:model.live="radius" class="{{ $select }}">
                        <option value="">{{ __('Toute la ville') }}</option>
                        @foreach (\App\Services\Search\SearchFilters::RADII_KM as $km)
                            <option value="{{ $km }}">{{ __('< :km km', ['km' => $km]) }}</option>
                        @endforeach
                    </select>
                @endif

                <label class="sr-only" for="sort">{{ __('Trier par') }}</label>
                <select id="sort" wire:model.live="sort" class="{{ $select }}">
                    @foreach ($sorts as $option)
                        @continue($option === \App\Enums\SearchSort::Distance && ! $hasLocation)
                        <option value="{{ $option->value }}">{{ $option->getLabel() }}</option>
                    @endforeach
                </select>
            </div>
        </section>
    </aside>

    {{-- Results --}}
    <div class="min-w-0 space-y-3">
        <div class="flex items-center justify-between gap-2 px-3 sm:px-0">
            <p class="text-[15px] font-semibold text-stone-600" role="status">
                {{ trans_choice(':count résultat|:count résultats', $results->total(), ['count' => $results->total()]) }}
                @if ($filters->openNow || $filters->verifiedOnly || $filters->minRating || $priceLevels || $filters->radiusKm || $filters->hasText())
                    · <button type="button" wire:click="resetFilters" class="text-brand-600 hover:underline">{{ __('Effacer les filtres') }}</button>
                @endif
            </p>
            <div class="flex rounded-full bg-stone-200 p-0.5 text-[14px] font-semibold" role="group" aria-label="{{ __('Affichage') }}">
                <button type="button" wire:click="$set('view', 'list')" aria-pressed="{{ $view === 'list' ? 'true' : 'false' }}"
                        @class(['flex items-center gap-1 rounded-full px-3 py-1', 'bg-white text-brand-600 shadow-sm' => $view === 'list'])>
                    <x-ui.icon name="list" size="16" /> {{ __('Liste') }}
                </button>
                <button type="button" wire:click="$set('view', 'map')" aria-pressed="{{ $view === 'map' ? 'true' : 'false' }}"
                        @class(['flex items-center gap-1 rounded-full px-3 py-1', 'bg-white text-brand-600 shadow-sm' => $view === 'map'])>
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
                 class="fb-card overflow-hidden">
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
                    <div class="fb-card p-8 text-center">
                        <span class="mx-auto flex size-12 items-center justify-center rounded-full bg-stone-200 text-stone-600"><x-ui.icon name="search" size="22" /></span>
                        <p class="mt-3 text-[17px] font-semibold">{{ __('Aucun résultat') }}</p>
                        <p class="mt-1 text-stone-600">{{ __('Aucun résultat. Essayez un autre mot ou retirez des filtres.') }}</p>
                    </div>
                @endforelse
            </section>
        @endif

        <div>
            {{ $results->onEachSide(1)->links(data: ['scrollTo' => '#main']) }}
        </div>
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

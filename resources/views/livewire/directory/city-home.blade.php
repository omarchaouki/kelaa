<div class="space-y-8">
    {{-- Search --}}
    <section class="pt-2">
        <h1 class="text-2xl font-bold sm:text-3xl">{{ __('De quoi avez-vous besoin à :city ?', ['city' => $city->name]) }}</h1>

        <form wire:submit="search" role="search" class="relative mt-4" x-data="{ open: false }" x-on:click.outside="open = false">
            <label for="home-search" class="sr-only">{{ __('Rechercher un service') }}</label>
            <div class="flex gap-2">
                <div class="relative flex-1">
                    <span class="pointer-events-none absolute inset-y-0 start-3 flex items-center text-stone-400">
                        <x-ui.icon name="search" />
                    </span>
                    <input id="home-search" type="search" wire:model.live.debounce.300ms="q" x-on:focus="open = true" x-on:input="open = true"
                           autocomplete="off" maxlength="{{ \App\Services\Search\SearchFilters::MAX_QUERY_LENGTH }}"
                           placeholder="{{ __('Plombier, pharmacie, mécanicien…') }}"
                           class="block w-full rounded-xl border border-stone-300 bg-white py-3 pe-3 ps-10 text-base shadow-sm focus:border-brand-600 focus:outline-none focus:ring-2 focus:ring-brand-600/30">
                </div>
                <button type="submit" class="rounded-xl bg-brand-600 px-4 font-semibold text-white hover:bg-brand-700">
                    {{ __('Chercher') }}
                </button>
            </div>

            @if ($suggestions['categories']->isNotEmpty() || $suggestions['businesses']->isNotEmpty())
                <div x-show="open" x-cloak class="absolute inset-x-0 z-30 mt-1 overflow-hidden rounded-xl border border-stone-200 bg-white shadow-lg">
                    <ul>
                        @foreach ($suggestions['categories'] as $suggestion)
                            <li>
                                <a href="{{ route('city.category', ['city' => $city, 'category' => $suggestion]) }}" wire:navigate
                                   class="flex items-center gap-2 px-4 py-2.5 hover:bg-stone-50">
                                    <x-ui.category-icon :icon="$suggestion->icon" size="18" class="text-brand-600" />
                                    <span>{{ $suggestion->name }}</span>
                                </a>
                            </li>
                        @endforeach
                        @foreach ($suggestions['businesses'] as $suggestion)
                            <li>
                                <a href="{{ route('city.business', ['city' => $city, 'business' => $suggestion, 'src' => 'search']) }}" wire:navigate
                                   class="block px-4 py-2.5 hover:bg-stone-50">
                                    <span class="font-medium">{{ $suggestion->name }}</span>
                                    <span class="text-sm text-stone-500">· {{ $suggestion->categories->first()?->name }}</span>
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif
        </form>

        <div class="mt-3" x-data="geolocate">
            @if ($hasLocation)
                <p class="flex items-center gap-2 text-sm text-stone-600">
                    <x-ui.icon name="locate" size="16" class="text-brand-600" />
                    {{ __('Position utilisée pour « près de vous ».') }}
                    <button type="button" wire:click="forgetLocation" class="underline">{{ __('Oublier') }}</button>
                </p>
            @else
                <button type="button" x-on:click="locate" x-bind:disabled="locating"
                        class="inline-flex items-center gap-2 rounded-lg border border-stone-300 bg-white px-3 py-2 text-sm font-medium hover:bg-stone-50 disabled:opacity-60">
                    <x-ui.icon name="locate" size="16" />
                    <span x-show="! locating">{{ __('Utiliser ma position') }}</span>
                    <span x-show="locating" x-cloak>{{ __('Localisation…') }}</span>
                </button>
                <p x-show="error" x-cloak class="mt-1 text-sm text-stone-500" role="status">
                    {{ __('Position indisponible. Vous pouvez chercher par quartier.') }}
                </p>
            @endif
            @error('location') <p class="mt-1 text-sm text-red-700">{{ $message }}</p> @enderror
        </div>
    </section>

    {{-- Pharmacies de garde --}}
    @if ($pharmacies->isNotEmpty())
        <section aria-labelledby="pharmacies-title" class="rounded-xl border border-emerald-200 bg-emerald-50 p-4">
            <div class="flex items-center justify-between gap-2">
                <h2 id="pharmacies-title" class="flex items-center gap-2 font-semibold text-emerald-900">
                    <x-ui.icon name="pill" size="18" />
                    {{ __('Pharmacies de garde ce soir') }}
                </h2>
                <a href="{{ route('city.pharmacies', ['city' => $city]) }}" wire:navigate class="text-sm text-emerald-800 underline">{{ __('Tout voir') }}</a>
            </div>
            <ul class="mt-3 space-y-2">
                @foreach ($pharmacies->take(4) as $duty)
                    <li class="flex items-center justify-between gap-2 text-sm">
                        <a href="{{ route('city.business', ['city' => $city, 'business' => $duty->business]) }}" wire:navigate class="font-medium hover:underline">
                            {{ $duty->business->name }}
                        </a>
                        <span class="shrink-0 text-emerald-800" dir="ltr">{{ $duty->starts_at->format('H:i') }}–{{ $duty->ends_at->format('H:i') }}</span>
                    </li>
                @endforeach
            </ul>
        </section>
    @endif

    {{-- Open now near you --}}
    @if ($openNearby !== null)
        <section aria-labelledby="nearby-title">
            <h2 id="nearby-title" class="text-lg font-semibold">{{ __('Ouvert maintenant près de vous') }}</h2>
            @if ($openNearby->isEmpty())
                <p class="mt-2 text-sm text-stone-600">{{ __('Aucun établissement ouvert à moins de 3 km pour le moment.') }}</p>
            @else
                <div class="mt-3 grid gap-3 sm:grid-cols-2">
                    @foreach ($openNearby as $business)
                        <x-directory.business-card :business="$business" :city="$city" :status="\App\Services\Hours\OpeningHours::for($business)->statusAt()" source="map" wire:key="nearby-{{ $business->id }}" />
                    @endforeach
                </div>
            @endif
        </section>
    @endif

    {{-- Categories --}}
    <section aria-labelledby="categories-title">
        <h2 id="categories-title" class="text-lg font-semibold">{{ __('Catégories') }}</h2>
        <div class="mt-3 space-y-5">
            @foreach ($groups as $group)
                <div wire:key="group-{{ $group->id }}">
                    <h3 @class(['mb-2 flex items-center gap-2 text-sm font-semibold uppercase tracking-wide', 'text-rose-700' => $group->is_emergency, 'text-stone-500' => ! $group->is_emergency])>
                        <x-ui.category-icon :icon="$group->icon" size="16" />
                        {{ $group->name }}
                    </h3>
                    <ul class="grid grid-cols-2 gap-2 sm:grid-cols-3 lg:grid-cols-4">
                        @foreach ($group->children as $category)
                            <li>
                                <a href="{{ route('city.category', ['city' => $city, 'category' => $category]) }}" wire:navigate
                                   @class([
                                       'flex h-full items-center gap-2 rounded-xl border bg-white p-3 text-sm font-medium hover:border-brand-400 hover:bg-brand-50',
                                       'border-rose-200' => $category->is_emergency,
                                       'border-stone-200' => ! $category->is_emergency,
                                   ])>
                                    <x-ui.category-icon :icon="$category->icon" size="20" :class="$category->is_emergency ? 'shrink-0 text-rose-600' : 'shrink-0 text-brand-600'" />
                                    <span class="min-w-0 flex-1">{{ $category->name }}</span>
                                    @if (($counts[$category->id] ?? 0) > 0)
                                        <span class="text-xs text-stone-500">{{ $counts[$category->id] }}</span>
                                    @endif
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endforeach
        </div>
    </section>

    {{-- Recently reviewed --}}
    @if ($recentlyReviewed->isNotEmpty())
        <section aria-labelledby="recent-title">
            <h2 id="recent-title" class="text-lg font-semibold">{{ __('Récemment évalués') }}</h2>
            <div class="mt-3 grid gap-3 sm:grid-cols-2">
                @foreach ($recentlyReviewed as $business)
                    <x-directory.business-card :business="$business" :city="$city" :status="\App\Services\Hours\OpeningHours::for($business)->statusAt()" wire:key="recent-{{ $business->id }}" />
                @endforeach
            </div>
        </section>
    @endif
</div>

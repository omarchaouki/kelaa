@php
    use App\Services\Hours\OpeningHours;

    // One colour per category group, used for its icon bubbles (emergency is always red).
    $groupColors = ['#1877f2', '#42b72a', '#f5533d', '#a033ff', '#0a7c86', '#e8a213', '#e42645', '#4b4c4f'];
    $colorFor = fn ($group, int $index) => $group->is_emergency ? '#e41e3f' : $groupColors[$index % count($groupColors)];
    $user = auth()->user();
@endphp
<div class="grid gap-4 lg:grid-cols-[280px_minmax(0,1fr)] xl:grid-cols-[300px_minmax(0,600px)_300px] xl:justify-between">

    {{-- Left rail: shortcuts to every category, like a social network's side menu. --}}
    <aside class="hidden lg:block" aria-label="{{ __('Catégories') }}">
        <div class="sticky top-[72px] max-h-[calc(100dvh-88px)] space-y-1 overflow-y-auto pe-2 [scrollbar-width:thin]">
            @auth
                <a href="{{ route('dashboard') }}" wire:navigate class="fb-row">
                    <x-ui.avatar :name="$user->displayName()" size="36" />
                    <span class="font-semibold">{{ $user->displayName() }}</span>
                </a>
            @endauth
            <a href="{{ route('city.pharmacies', ['city' => $city]) }}" wire:navigate class="fb-row">
                <span class="flex size-9 items-center justify-center rounded-full bg-[#e41e3f] text-white"><x-ui.icon name="pill" size="18" /></span>
                <span class="font-semibold">{{ __('Pharmacies de garde') }}</span>
            </a>
            <a href="{{ route('city.search', ['city' => $city, 'open' => 1]) }}" wire:navigate class="fb-row">
                <span class="flex size-9 items-center justify-center rounded-full bg-online text-white"><x-ui.icon name="clock" size="18" /></span>
                <span class="font-semibold">{{ __('Ouvert maintenant') }}</span>
            </a>

            @foreach ($groups as $group)
                @php $color = $colorFor($group, $loop->index); @endphp
                <div class="pt-3" wire:key="rail-group-{{ $group->id }}">
                    <div class="fb-divider mx-2 mb-3"></div>
                    <h2 class="px-2 pb-1 text-[17px] font-semibold text-stone-600">{{ $group->name }}</h2>
                    @foreach ($group->children as $category)
                        <a href="{{ route('city.category', ['city' => $city, 'category' => $category]) }}" wire:navigate class="fb-row" wire:key="rail-{{ $category->id }}">
                            <span class="flex size-9 shrink-0 items-center justify-center rounded-full" style="background: {{ $color }}1f; color: {{ $color }}">
                                <x-ui.category-icon :icon="$category->icon" size="18" />
                            </span>
                            <span class="min-w-0 flex-1 truncate">{{ $category->name }}</span>
                            @if (($counts[$category->id] ?? 0) > 0)
                                <span class="text-[13px] text-stone-500">{{ $counts[$category->id] }}</span>
                            @endif
                        </a>
                    @endforeach
                </div>
            @endforeach
        </div>
    </aside>

    {{-- Feed --}}
    <div class="min-w-0 space-y-4">
        {{-- Composer: the search box is the "what's on your mind" of a city directory. --}}
        <section class="fb-card px-4 pb-2 pt-3">
            <form wire:submit="search" role="search" class="relative" x-data="{ open: false }" x-on:click.outside="open = false">
                <h1 class="mb-3 text-[20px] font-bold leading-6">
                    <label for="home-search">{{ __('De quoi avez-vous besoin à :city ?', ['city' => $city->name]) }}</label>
                </h1>
                <div class="flex items-center gap-2">
                    @auth
                        <x-ui.avatar :name="$user->displayName()" size="40" />
                    @else
                        <span class="flex size-10 shrink-0 items-center justify-center rounded-full bg-stone-200 text-stone-600"><x-ui.icon name="user" size="20" /></span>
                    @endauth
                    <input id="home-search" type="search" wire:model.live.debounce.300ms="q" x-on:focus="open = true" x-on:input="open = true"
                           autocomplete="off" maxlength="{{ \App\Services\Search\SearchFilters::MAX_QUERY_LENGTH }}"
                           placeholder="{{ __('Plombier, pharmacie, mécanicien…') }}"
                           class="fb-input h-10 flex-1 text-[17px]">
                    <button type="submit" class="fb-btn fb-btn-primary h-10">{{ __('Chercher') }}</button>
                </div>

                @if ($suggestions['categories']->isNotEmpty() || $suggestions['businesses']->isNotEmpty())
                    <div x-show="open" x-cloak class="absolute inset-x-0 z-30 mt-2 rounded-lg bg-white p-2 shadow-[var(--shadow-pop)]">
                        <ul>
                            @foreach ($suggestions['categories'] as $suggestion)
                                <li>
                                    <a href="{{ route('city.category', ['city' => $city, 'category' => $suggestion]) }}" wire:navigate class="fb-row">
                                        <span class="flex size-9 items-center justify-center rounded-full bg-brand-50 text-brand-600">
                                            <x-ui.category-icon :icon="$suggestion->icon" size="18" />
                                        </span>
                                        <span>{{ $suggestion->name }}</span>
                                    </a>
                                </li>
                            @endforeach
                            @foreach ($suggestions['businesses'] as $suggestion)
                                <li>
                                    <a href="{{ route('city.business', ['city' => $city, 'business' => $suggestion, 'src' => 'search']) }}" wire:navigate class="fb-row">
                                        <x-ui.avatar :name="$suggestion->name" size="36" />
                                        <span class="min-w-0">
                                            <span class="block truncate font-semibold">{{ $suggestion->name }}</span>
                                            <span class="block truncate text-[13px] text-stone-600">{{ $suggestion->categories->first()?->name }}</span>
                                        </span>
                                    </a>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endif
            </form>

            <div class="fb-divider mb-1 mt-3"></div>

            <div class="flex flex-wrap" x-data="geolocate">
                @if ($hasLocation)
                    <button type="button" wire:click="forgetLocation" class="fb-action">
                        <x-ui.icon name="locate" size="20" class="text-online" />
                        <span>{{ __('Oublier ma position') }}</span>
                    </button>
                @else
                    <button type="button" x-on:click="locate" x-bind:disabled="locating" class="fb-action">
                        <x-ui.icon name="locate" size="20" class="text-online" />
                        <span x-show="! locating">{{ __('Près de moi') }}</span>
                        <span x-show="locating" x-cloak>{{ __('Localisation…') }}</span>
                    </button>
                @endif
                <a href="{{ route('city.search', ['city' => $city, 'open' => 1]) }}" wire:navigate class="fb-action">
                    <x-ui.icon name="clock" size="20" class="text-brand-600" />
                    <span>{{ __('Ouvert maintenant') }}</span>
                </a>
                <a href="{{ route('city.pharmacies', ['city' => $city]) }}" wire:navigate class="fb-action">
                    <x-ui.icon name="pill" size="20" class="text-[#e41e3f]" />
                    <span>{{ __('De garde') }}</span>
                </a>

                <p x-show="error" x-cloak class="w-full px-2 pb-1 text-[13px] text-stone-600" role="status">
                    {{ __('Position indisponible. Vous pouvez chercher par quartier.') }}
                </p>
                @if ($hasLocation)
                    <p class="w-full px-2 pb-1 text-[13px] text-stone-600">{{ __('Position utilisée pour « près de vous ».') }}</p>
                @endif
                @error('location') <p class="w-full px-2 pb-1 text-[13px] text-red-700">{{ $message }}</p> @enderror
            </div>
        </section>

        {{-- On small screens the right rail is not visible: pharmacies come first. --}}
        @if ($pharmacies->isNotEmpty())
            <section class="fb-card p-4 xl:hidden" aria-labelledby="pharmacies-title-feed">
                <div class="flex items-center justify-between">
                    <h2 id="pharmacies-title-feed" class="text-[17px] font-semibold">{{ __('Pharmacies de garde ce soir') }}</h2>
                    <a href="{{ route('city.pharmacies', ['city' => $city]) }}" wire:navigate class="rounded-md px-2 py-1 text-[15px] text-brand-600 hover:bg-stone-100">{{ __('Tout voir') }}</a>
                </div>
                <ul class="-mx-2 mt-1">
                    @foreach ($pharmacies->take(4) as $duty)
                        <li>
                            <a href="{{ route('city.business', ['city' => $city, 'business' => $duty->business]) }}" wire:navigate class="fb-row">
                                <x-ui.avatar :name="$duty->business->name" size="36" :open="true" />
                                <span class="min-w-0 flex-1 truncate font-semibold">{{ $duty->business->name }}</span>
                                <span class="shrink-0 text-[13px] text-stone-600" dir="ltr">{{ $duty->starts_at->format('H:i') }}–{{ $duty->ends_at->format('H:i') }}</span>
                            </a>
                        </li>
                    @endforeach
                </ul>
            </section>
        @endif

        {{-- Categories as a card on screens without the left rail. --}}
        <section class="fb-card p-4 lg:hidden" aria-labelledby="categories-title">
            <h2 id="categories-title" class="text-[17px] font-semibold">{{ __('Catégories') }}</h2>
            @foreach ($groups as $group)
                @php $color = $colorFor($group, $loop->index); @endphp
                <div class="mt-3" wire:key="group-{{ $group->id }}">
                    <h3 class="text-[13px] font-semibold uppercase tracking-wide" style="color: {{ $color }}">{{ $group->name }}</h3>
                    <ul class="-mx-2 mt-1 grid grid-cols-2 sm:grid-cols-3">
                        @foreach ($group->children as $category)
                            <li>
                                <a href="{{ route('city.category', ['city' => $city, 'category' => $category]) }}" wire:navigate class="fb-row text-[14px]">
                                    <span class="flex size-8 shrink-0 items-center justify-center rounded-full" style="background: {{ $color }}1f; color: {{ $color }}">
                                        <x-ui.category-icon :icon="$category->icon" size="16" />
                                    </span>
                                    <span class="min-w-0 flex-1 truncate">{{ $category->name }}</span>
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endforeach
        </section>

        {{-- Open now near you --}}
        @if ($openNearby !== null)
            <section class="fb-card p-4" aria-labelledby="nearby-title">
                <h2 id="nearby-title" class="text-[17px] font-semibold">{{ __('Ouvert maintenant près de vous') }}</h2>
                @if ($openNearby->isEmpty())
                    <p class="mt-2 text-stone-600">{{ __('Aucun établissement ouvert à moins de 3 km pour le moment.') }}</p>
                @else
                    <div class="-mx-2 mt-2">
                        @foreach ($openNearby as $business)
                            <x-directory.business-card :business="$business" :city="$city" :status="OpeningHours::for($business)->statusAt()" source="map" :framed="false" wire:key="nearby-{{ $business->id }}" />
                        @endforeach
                    </div>
                @endif
            </section>
        @endif

        {{-- Recently reviewed --}}
        @if ($recentlyReviewed->isNotEmpty())
            <section class="fb-card p-4" aria-labelledby="recent-title">
                <h2 id="recent-title" class="text-[17px] font-semibold">{{ __('Récemment évalués') }}</h2>
                <div class="-mx-2 mt-2">
                    @foreach ($recentlyReviewed as $business)
                        <x-directory.business-card :business="$business" :city="$city" :status="OpeningHours::for($business)->statusAt()" :framed="false" wire:key="recent-{{ $business->id }}" />
                    @endforeach
                </div>
            </section>
        @endif
    </div>

    {{-- Right rail: tonight's pharmacies, shown like an online contacts list. --}}
    <aside class="hidden xl:block" aria-labelledby="pharmacies-title">
        <div class="sticky top-[72px] space-y-4">
            @if ($pharmacies->isNotEmpty())
                <section>
                    <div class="flex items-center justify-between px-2">
                        <h2 id="pharmacies-title" class="text-[17px] font-semibold text-stone-600">{{ __('Pharmacies de garde ce soir') }}</h2>
                        <a href="{{ route('city.pharmacies', ['city' => $city]) }}" wire:navigate class="rounded-full p-1.5 text-stone-600 hover:bg-stone-200" aria-label="{{ __('Tout voir') }}">
                            <x-ui.icon name="chevron-right" size="18" class="rtl:rotate-180" />
                        </a>
                    </div>
                    <ul class="mt-1">
                        @foreach ($pharmacies->take(8) as $duty)
                            <li>
                                <a href="{{ route('city.business', ['city' => $city, 'business' => $duty->business]) }}" wire:navigate class="fb-row">
                                    <x-ui.avatar :name="$duty->business->name" size="36" :open="true" />
                                    <span class="min-w-0 flex-1">
                                        <span class="block truncate font-semibold">{{ $duty->business->name }}</span>
                                        <span class="block text-[13px] text-stone-600" dir="ltr">{{ $duty->starts_at->format('H:i') }}–{{ $duty->ends_at->format('H:i') }}</span>
                                    </span>
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </section>
                <div class="fb-divider mx-2"></div>
            @endif

            <section class="px-2">
                <h2 class="text-[17px] font-semibold text-stone-600">{{ __('Vous êtes un professionnel ?') }}</h2>
                <p class="mt-1 text-[13px] text-stone-600">{{ __('Ajoutez votre établissement gratuitement et répondez aux avis de vos clients.') }}</p>
                <a href="{{ route('city.add', ['city' => $city]) }}" wire:navigate class="fb-btn fb-btn-soft mt-3 w-full">
                    <x-ui.icon name="plus" size="18" /> {{ __('Ajouter un établissement') }}
                </a>
            </section>
        </div>
    </aside>
</div>

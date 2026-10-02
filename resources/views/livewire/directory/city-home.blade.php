@php
    use App\Services\Hours\OpeningHours;
    use App\Support\Icons\Lucide;
    use Illuminate\Support\Number;

    // One colour per category group, used for its icon bubbles (emergency is always red).
    $groupColors = ['#1877f2', '#42b72a', '#f5533d', '#a033ff', '#0a7c86', '#e8a213', '#e42645', '#4b4c4f'];
    $colorFor = fn ($group, int $index) => $group->is_emergency ? '#e41e3f' : $groupColors[$index % count($groupColors)];
    $groupColor = $groups->mapWithKeys(fn ($group, $index) => [$group->id => $colorFor($group, $index)]);
    $number = fn (int $value) => Number::format($value, locale: app()->getLocale());
@endphp
<div class="mx-auto max-w-[1180px] space-y-4" x-data="homeCategories(@js($index))" x-on:home-filter.window="term = $event.detail">

    {{-- Hero: what is this, search, figures, shortcuts. --}}
    <section class="fb-card overflow-hidden rounded-none sm:rounded-lg">
        <div class="bg-gradient-to-br from-brand-500 via-brand-600 to-brand-900 px-4 pb-14 pt-6 text-white sm:px-8 sm:pt-8">
            <p class="text-[13px] font-semibold uppercase tracking-wide text-white/80">{{ config('app.name') }} · {{ $city->name }}</p>
            <h1 class="mt-1 max-w-2xl text-[26px] font-extrabold leading-8 sm:text-[34px] sm:leading-10">
                {{ __('Trouvez le bon contact à :city', ['city' => $city->name]) }}
            </h1>
            <p class="mt-2 max-w-2xl text-[15px] text-white/85 sm:text-[17px]">
                {{ __('Artisans, santé, taxis, avocats, écoles, commerces… avec le téléphone, le WhatsApp, les horaires et les avis des habitants.') }}
            </p>
        </div>

        <div class="-mt-8 px-3 sm:px-8">
            <livewire:directory.quick-search :city="$city" />
        </div>

        <dl class="grid grid-cols-2 gap-px px-3 pt-4 sm:grid-cols-4 sm:px-8">
            <div class="rounded-lg p-2">
                <dt class="text-[13px] text-stone-600">{{ __('Établissements') }}</dt>
                <dd class="text-[24px] font-bold leading-7 text-stone-900">{{ $number($stats['businesses']) }}</dd>
            </div>
            <div class="rounded-lg p-2">
                <dt class="text-[13px] text-stone-600">{{ __('Catégories') }}</dt>
                <dd class="text-[24px] font-bold leading-7 text-stone-900">{{ $number($stats['categories']) }}</dd>
            </div>
            <div class="rounded-lg p-2">
                <dt class="text-[13px] text-stone-600">{{ __('Avis des habitants') }}</dt>
                <dd class="text-[24px] font-bold leading-7 text-stone-900">{{ $number($stats['reviews']) }}</dd>
            </div>
            <div class="rounded-lg p-2">
                <dt class="text-[13px] text-stone-600">{{ __('Nouveaux ce mois-ci') }}</dt>
                <dd class="text-[24px] font-bold leading-7 text-online-dark">+{{ $number($stats['new_this_month']) }}</dd>
            </div>
        </dl>

        <div class="mx-3 mt-2 border-t border-stone-300 py-1 sm:mx-8" x-data="geolocate">
            <div class="flex flex-wrap">
                @if ($hasLocation)
                    <button type="button" wire:click="forgetLocation" class="fb-action">
                        <x-ui.icon name="locate" size="20" class="text-online" /> <span>{{ __('Oublier ma position') }}</span>
                    </button>
                @else
                    <button type="button" x-on:click="locate" x-bind:disabled="locating" class="fb-action">
                        <x-ui.icon name="locate" size="20" class="text-online" />
                        <span x-show="! locating">{{ __('Près de moi') }}</span>
                        <span x-show="locating" x-cloak>{{ __('Localisation…') }}</span>
                    </button>
                @endif
                <a href="{{ route('city.search', ['city' => $city, 'open' => 1]) }}" wire:navigate class="fb-action">
                    <x-ui.icon name="clock" size="20" class="text-brand-600" /> <span>{{ __('Ouvert maintenant') }}</span>
                </a>
                <a href="{{ route('city.pharmacies', ['city' => $city]) }}" wire:navigate class="fb-action">
                    <x-ui.icon name="pill" size="20" class="text-[#e41e3f]" /> <span>{{ __('De garde') }}</span>
                </a>
                <a href="{{ route('city.add', ['city' => $city]) }}" wire:navigate class="fb-action">
                    <x-ui.icon name="plus" size="20" class="text-[#a033ff]" /> <span>{{ __('Ajouter') }}</span>
                </a>
            </div>
            <p x-show="error" x-cloak class="px-2 pb-1 text-[13px] text-stone-600" role="status">{{ __('Position indisponible. Vous pouvez chercher par quartier.') }}</p>
            @error('location') <p class="px-2 pb-1 text-[13px] text-red-700">{{ $message }}</p> @enderror
        </div>
    </section>

    <div class="grid gap-4 lg:grid-cols-[minmax(0,1fr)_320px]">
        <div class="min-w-0 space-y-4">
            {{-- Most listed categories, hidden while filtering. --}}
            @if ($popular->isNotEmpty())
                <section x-show="! filtering" aria-labelledby="popular-title" class="px-3 sm:px-0">
                    <h2 id="popular-title" class="mb-2 text-[17px] font-semibold">{{ __('Les plus demandés') }}</h2>
                    <div class="-mx-3 flex snap-x gap-2 overflow-x-auto px-3 pb-1 sm:mx-0 sm:grid sm:grid-cols-4 sm:overflow-visible sm:px-0">
                        @foreach ($popular as $category)
                            @php $color = $groupColor[$category->parent_id] ?? '#1877f2'; @endphp
                            <a href="{{ route('city.category', ['city' => $city, 'category' => $category]) }}" wire:navigate wire:key="pop-{{ $category->id }}"
                               class="fb-card group relative flex w-36 shrink-0 snap-start flex-col justify-between overflow-hidden p-3 transition-transform hover:-translate-y-0.5 sm:w-auto">
                                <span class="flex size-11 items-center justify-center rounded-full text-white" style="background: {{ $color }}">
                                    {{ Lucide::svg($category->icon, 22) }}
                                </span>
                                <span class="mt-6 block text-[15px] font-semibold leading-5">{{ $category->name }}</span>
                                <span class="text-[13px] text-stone-600">{{ trans_choice(':count adresse|:count adresses', $counts[$category->id], ['count' => $counts[$category->id]]) }}</span>
                            </a>
                        @endforeach
                    </div>
                </section>
            @endif

            {{-- Group tabs: instant, no reload. --}}
            <nav x-ref="grid" class="sticky top-14 z-20 -mx-0 scroll-mt-16 bg-stone-100/95 py-2 backdrop-blur" aria-label="{{ __('Catégories') }}">
                <div class="flex gap-2 overflow-x-auto px-3 sm:px-0">
                    <button type="button" class="fb-chip" x-on:click="choose('all')" x-bind:aria-pressed="group === 'all'">{{ __('Tout') }}</button>
                    @foreach ($groups as $group)
                        <button type="button" class="fb-chip" x-on:click="choose({{ $group->id }})" x-bind:aria-pressed="group === {{ $group->id }}" wire:key="tab-{{ $group->id }}">
                            <span style="color: {{ $groupColor[$group->id] }}">{{ Lucide::svg($group->icon, 15) }}</span>
                            {{ $group->name }}
                        </button>
                    @endforeach
                </div>
            </nav>

            {{-- Every category with its number of listings. --}}
            <div class="space-y-5 px-3 sm:px-0">
                @foreach ($groups as $group)
                    @php
                        $color = $groupColor[$group->id];
                        $groupTotal = $group->children->sum(fn ($child) => $counts[$child->id] ?? 0);
                    @endphp
                    <section x-show="groupVisible({{ $group->id }})" aria-labelledby="group-{{ $group->id }}" wire:key="group-{{ $group->id }}">
                        <div class="mb-2 flex items-center gap-2">
                            <span class="flex size-8 items-center justify-center rounded-full text-white" style="background: {{ $color }}">{{ Lucide::svg($group->icon, 16) }}</span>
                            <h2 id="group-{{ $group->id }}" class="text-[17px] font-semibold">{{ $group->name }}</h2>
                            <span class="text-[13px] text-stone-600">· {{ trans_choice(':count adresse|:count adresses', $groupTotal, ['count' => $groupTotal]) }}</span>
                        </div>
                        <ul class="grid grid-cols-2 gap-2 md:grid-cols-3 xl:grid-cols-4">
                            @foreach ($group->children as $category)
                                @php $count = $counts[$category->id] ?? 0; @endphp
                                <li x-show="visible({{ $category->id }})" wire:key="cat-{{ $category->id }}">
                                    <a href="{{ route('city.category', ['city' => $city, 'category' => $category]) }}" wire:navigate
                                       class="fb-card flex h-full items-center gap-3 p-3 transition-[transform,box-shadow] hover:-translate-y-0.5 hover:shadow-[0_4px_12px_rgb(0_0_0/0.12)]">
                                        <span class="flex size-10 shrink-0 items-center justify-center rounded-full" style="background: {{ $color }}1f; color: {{ $color }}">
                                            {{ Lucide::svg($category->icon, 20) }}
                                        </span>
                                        <span class="min-w-0 flex-1">
                                            <span class="block text-[14px] font-semibold leading-5 sm:text-[15px]">{{ $category->name }}</span>
                                            <span @class(['block text-[12px] sm:text-[13px]', 'text-stone-600' => $count > 0, 'text-stone-400' => $count === 0])>
                                                {{ $count > 0 ? trans_choice(':count adresse|:count adresses', $count, ['count' => $count]) : __('Bientôt') }}
                                            </span>
                                        </span>
                                    </a>
                                </li>
                            @endforeach
                        </ul>
                    </section>
                @endforeach

                <div x-show="visibleCount === 0" x-cloak class="fb-card p-8 text-center">
                    <span class="mx-auto flex size-12 items-center justify-center rounded-full bg-stone-200 text-stone-600"><x-ui.icon name="search" size="22" /></span>
                    <p class="mt-3 text-[17px] font-semibold">{{ __('Aucune catégorie ne correspond') }}</p>
                    <p class="mt-1 text-stone-600">{{ __('Appuyez sur « Chercher » pour trouver un établissement par son nom.') }}</p>
                    <button type="button" class="fb-btn fb-btn-gray mt-3" x-on:click="term = ''; group = 'all'; $dispatch('home-filter-reset')">{{ __('Voir toutes les catégories') }}</button>
                </div>
            </div>
        </div>

        {{-- Side column: tonight's pharmacies, add a business, activity. --}}
        <aside class="space-y-4 px-3 sm:px-0 lg:sticky lg:top-[72px] lg:self-start">
            @if ($pharmacies->isNotEmpty())
                <section class="fb-card p-4" aria-labelledby="pharmacies-title">
                    <div class="flex items-center justify-between">
                        <h2 id="pharmacies-title" class="text-[17px] font-semibold">{{ __('Pharmacies de garde ce soir') }}</h2>
                        <a href="{{ route('city.pharmacies', ['city' => $city]) }}" wire:navigate class="rounded-md px-2 py-1 text-[14px] text-brand-600 hover:bg-stone-100">{{ __('Tout voir') }}</a>
                    </div>
                    <ul class="-mx-2 mt-1">
                        @foreach ($pharmacies->take(5) as $duty)
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
            @endif

            <section class="fb-card overflow-hidden">
                <div class="bg-[#a033ff]/10 p-4">
                    <span class="flex size-11 items-center justify-center rounded-full bg-[#a033ff] text-white"><x-ui.icon name="store" size="22" /></span>
                    <h2 class="mt-3 text-[17px] font-semibold">{{ __('Vous avez une activité à :city ?', ['city' => $city->name]) }}</h2>
                    <p class="mt-1 text-[14px] text-stone-600">{{ __('Ajoutez votre établissement gratuitement en quelques étapes. Notre équipe le vérifie puis le publie.') }}</p>
                    <a href="{{ route('city.add', ['city' => $city]) }}" wire:navigate class="fb-btn fb-btn-primary mt-3 w-full">
                        <x-ui.icon name="plus" size="18" /> {{ __('Ajouter mon établissement') }}
                    </a>
                </div>
            </section>

            @if ($openNearby !== null && $openNearby->isNotEmpty())
                <section class="fb-card p-4" aria-labelledby="nearby-title">
                    <h2 id="nearby-title" class="text-[17px] font-semibold">{{ __('Ouvert maintenant près de vous') }}</h2>
                    <div class="-mx-2 mt-2">
                        @foreach ($openNearby->take(5) as $business)
                            <x-directory.business-card :business="$business" :city="$city" :status="OpeningHours::for($business)->statusAt()" source="map" :framed="false" wire:key="nearby-{{ $business->id }}" />
                        @endforeach
                    </div>
                </section>
            @endif

            @if ($recentlyReviewed->isNotEmpty())
                <section class="fb-card p-4" aria-labelledby="recent-title">
                    <h2 id="recent-title" class="text-[17px] font-semibold">{{ __('Récemment évalués') }}</h2>
                    <div class="-mx-2 mt-2">
                        @foreach ($recentlyReviewed->take(5) as $business)
                            <x-directory.business-card :business="$business" :city="$city" :status="OpeningHours::for($business)->statusAt()" :framed="false" wire:key="recent-{{ $business->id }}" />
                        @endforeach
                    </div>
                </section>
            @endif
        </aside>
    </div>
</div>

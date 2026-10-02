<form wire:submit="search" role="search" class="relative" x-data="{ open: false }" x-on:click.outside="open = false">
    <label for="home-search" class="sr-only">{{ __('Que cherchez-vous ?') }}</label>
    <div class="relative">
        <span class="pointer-events-none absolute inset-y-0 start-4 flex items-center text-stone-500"><x-ui.icon name="search" size="20" /></span>
        {{-- Each keystroke filters the categories at once (browser event); business matches follow from the server. --}}
        <input id="home-search" type="search" wire:model.live.debounce.300ms="q" autocomplete="off"
               maxlength="{{ \App\Services\Search\SearchFilters::MAX_QUERY_LENGTH }}"
               x-on:input="open = true; $dispatch('home-filter', $event.target.value)"
               x-on:focus="open = true" x-on:keydown.escape="open = false"
               x-on:home-filter-reset.window="$el.value = ''; $wire.set('q', '')"
               placeholder="{{ __('Plombier, pharmacie, taxi, avocat…') }}"
               class="fb-input h-14 ps-12 pe-28 text-[17px] shadow-[inset_0_0_0_1px_var(--color-stone-300)]">
        <button type="submit" class="fb-btn fb-btn-primary absolute end-2 top-2 h-10">{{ __('Chercher') }}</button>
    </div>

    @if ($businesses->isNotEmpty())
        <div x-show="open" x-cloak x-transition.opacity class="absolute inset-x-0 z-30 mt-2 rounded-lg bg-white p-2 shadow-[var(--shadow-pop)]">
            <p class="px-2 pb-1 text-[13px] font-semibold text-stone-600">{{ __('Établissements') }}</p>
            <ul>
                @foreach ($businesses as $business)
                    <li wire:key="qs-{{ $business->id }}">
                        <a href="{{ route('city.business', ['city' => $city, 'business' => $business, 'src' => 'search']) }}" wire:navigate class="fb-row">
                            <x-ui.avatar :name="$business->name" :src="$business->getFirstMediaUrl('logo', 'thumb') ?: null" size="36" />
                            <span class="min-w-0">
                                <span class="block truncate font-semibold">{{ $business->name }}</span>
                                <span class="block truncate text-[13px] text-stone-600">
                                    {{ $business->categories->first()?->name }}@if ($business->neighborhood) · {{ $business->neighborhood->name }}@endif
                                </span>
                            </span>
                        </a>
                    </li>
                @endforeach
            </ul>
            <button type="submit" class="fb-row w-full justify-center font-semibold text-brand-600">
                {{ __('Voir tous les résultats pour « :q »', ['q' => $q]) }}
            </button>
        </div>
    @endif
</form>

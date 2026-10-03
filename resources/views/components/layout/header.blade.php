@php
    use App\Models\City;
    use App\Support\Locales;

    $routeCity = request()->route('city');
    $citySlug = $routeCity instanceof City ? $routeCity->slug : (is_string($routeCity) ? $routeCity : config('directory.default_city'));
    $user = auth()->user();

    $tabs = [
        ['route' => 'city.home', 'params' => ['city' => $citySlug], 'icon' => 'house', 'label' => __('Accueil'), 'active' => request()->routeIs('city.home', 'locale.home')],
        ['route' => 'city.search', 'params' => ['city' => $citySlug], 'icon' => 'search', 'label' => __('Rechercher'), 'active' => request()->routeIs('city.search', 'city.category')],
        ['route' => 'city.pharmacies', 'params' => ['city' => $citySlug], 'icon' => 'pill', 'label' => __('Pharmacies de garde'), 'active' => request()->routeIs('city.pharmacies')],
        ['route' => 'dashboard', 'params' => [], 'icon' => 'store', 'label' => __('Espace pro'), 'active' => request()->routeIs('dashboard*')],
    ];
@endphp
<header class="sticky top-0 z-40 bg-white shadow-[0_1px_2px_rgb(0_0_0/0.1),0_2px_6px_rgb(0_0_0/0.04)]">
    <div class="mx-auto grid h-14 max-w-[1440px] grid-cols-[1fr_auto] items-center gap-2 px-3 md:grid-cols-[1fr_auto_1fr]">
        {{-- Brand + search --}}
        <div class="flex min-w-0 items-center gap-2">
            <a href="{{ route('city.home', ['city' => $citySlug]) }}" wire:navigate
               class="flex size-10 shrink-0 items-center justify-center rounded-full bg-brand-600 text-white"
               aria-label="{{ config('app.name') }} · {{ __('Accueil') }}">
                <x-ui.icon name="map-pin" size="22" />
            </a>
            <form action="{{ route('city.search', ['city' => $citySlug]) }}" method="GET" role="search" class="relative hidden w-60 lg:block">
                <label for="header-search" class="sr-only">{{ __('Rechercher un service') }}</label>
                <span class="pointer-events-none absolute inset-y-0 start-3 flex items-center text-stone-600"><x-ui.icon name="search" size="16" /></span>
                <input id="header-search" name="q" type="search" maxlength="{{ \App\Services\Search\SearchFilters::MAX_QUERY_LENGTH }}"
                       placeholder="{{ $routeCity instanceof City ? __('Rechercher à :city', ['city' => $routeCity->name]) : __('Rechercher') }}"
                       class="fb-input h-10 ps-9">
            </form>
            <a href="{{ route('city.search', ['city' => $citySlug]) }}" wire:navigate
               class="flex size-10 items-center justify-center rounded-full bg-stone-200 text-stone-900 hover:bg-stone-300 lg:hidden"
               aria-label="{{ __('Rechercher') }}">
                <x-ui.icon name="search" size="18" />
            </a>
        </div>

        {{-- Main sections: icon tabs with the active underline. --}}
        <nav class="hidden h-full md:flex" aria-label="{{ __('Navigation principale') }}">
            @foreach ($tabs as $tab)
                <a href="{{ route($tab['route'], $tab['params']) }}" wire:navigate
                   @if ($tab['active']) aria-current="page" @endif
                   title="{{ $tab['label'] }}"
                   class="fb-tab h-full w-24 justify-center rounded-none lg:w-28">
                    <x-ui.icon :name="$tab['icon']" size="24" />
                    <span class="sr-only">{{ $tab['label'] }}</span>
                </a>
            @endforeach
        </nav>

        {{-- Language + account --}}
        <div class="flex items-center justify-end gap-2">
            <div x-data="{ open: false }" class="relative">
                <button type="button" x-on:click="open = ! open" x-bind:aria-expanded="open"
                        class="flex h-10 items-center gap-1 rounded-full bg-stone-200 px-3 text-sm font-semibold hover:bg-stone-300"
                        aria-label="{{ __('Langue') }}">
                    <x-ui.icon name="globe" size="18" />
                    <span class="uppercase">{{ app()->getLocale() }}</span>
                </button>
                <ul x-show="open" x-cloak x-on:click.outside="open = false" x-transition.origin.top
                    class="absolute end-0 mt-2 w-48 rounded-lg bg-white p-2 shadow-[var(--shadow-pop)]">
                    @foreach (Locales::supported() as $code)
                        <li>
                            <a href="{{ Locales::switchUrl(request(), $code) }}" hreflang="{{ $code }}" lang="{{ $code }}"
                               @class(['fb-row', 'text-brand-600' => $code === app()->getLocale()])>
                                {{ Locales::nativeName($code) }}
                            </a>
                        </li>
                    @endforeach
                </ul>
            </div>

            @auth
                <div x-data="{ open: false }" class="relative">
                    <button type="button" x-on:click="open = ! open" x-bind:aria-expanded="open" class="flex rounded-full"
                            aria-label="{{ __('Mon compte') }}">
                        <x-ui.avatar :name="$user->displayName()" size="40" />
                    </button>
                    <div x-show="open" x-cloak x-on:click.outside="open = false" x-transition.origin.top
                         class="absolute end-0 mt-2 w-80 rounded-lg bg-white p-2 shadow-[var(--shadow-pop)]">
                        <div class="fb-card mb-2 p-1 shadow-[0_2px_12px_rgb(0_0_0/0.12)]">
                            <a href="{{ route('account.privacy') }}" wire:navigate class="fb-row">
                                <x-ui.avatar :name="$user->displayName()" size="36" />
                                <span class="text-[17px] font-semibold">{{ $user->displayName() }}</span>
                            </a>
                        </div>
                        <a href="{{ route('dashboard') }}" wire:navigate class="fb-row">
                            <span class="flex size-9 items-center justify-center rounded-full bg-stone-200"><x-ui.icon name="store" size="20" /></span>
                            {{ __('Espace pro') }}
                        </a>
                        <a href="{{ route('account.phone') }}" wire:navigate class="fb-row">
                            <span class="flex size-9 items-center justify-center rounded-full bg-stone-200"><x-ui.icon name="phone" size="20" /></span>
                            {{ __('Vérifier mon numéro') }}
                        </a>
                        <a href="{{ route('account.privacy') }}" wire:navigate class="fb-row">
                            <span class="flex size-9 items-center justify-center rounded-full bg-stone-200"><x-ui.icon name="shield" size="20" /></span>
                            {{ __('Mes données') }}
                        </a>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="fb-row w-full">
                                <span class="flex size-9 items-center justify-center rounded-full bg-stone-200"><x-ui.icon name="log-out" size="20" /></span>
                                {{ __('Déconnexion') }}
                            </button>
                        </form>
                    </div>
                </div>
            @else
                @unless (config('auth_options.sms_verification'))
                    <a href="{{ route('login', ['mode' => 'register']) }}" wire:navigate class="fb-btn fb-btn-gray hidden h-10 sm:inline-flex">
                        {{ __('Créer un compte') }}
                    </a>
                @endunless
                <a href="{{ route('login') }}" wire:navigate class="fb-btn fb-btn-primary h-10">
                    {{ __('Connexion') }}
                </a>
            @endauth
        </div>
    </div>
</header>

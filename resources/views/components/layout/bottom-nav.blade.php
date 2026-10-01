@php
    use App\Models\City;

    $routeCity = request()->route('city');
    $citySlug = $routeCity instanceof City ? $routeCity->slug : (is_string($routeCity) ? $routeCity : config('directory.default_city'));

    $items = [
        ['href' => route('city.home', ['city' => $citySlug]), 'icon' => 'house', 'label' => __('Accueil'), 'active' => request()->routeIs('city.home', 'locale.home')],
        ['href' => route('city.search', ['city' => $citySlug]), 'icon' => 'search', 'label' => __('Rechercher'), 'active' => request()->routeIs('city.search', 'city.category')],
        ['href' => route('city.pharmacies', ['city' => $citySlug]), 'icon' => 'pill', 'label' => __('Pharmacies'), 'active' => request()->routeIs('city.pharmacies')],
        auth()->check()
            ? ['href' => route('dashboard'), 'icon' => 'store', 'label' => __('Espace pro'), 'active' => request()->routeIs('dashboard*', 'account.*')]
            : ['href' => route('login'), 'icon' => 'user', 'label' => __('Connexion'), 'active' => request()->routeIs('login*')],
    ];
@endphp
{{-- Mobile navigation, thumb-reachable. Hidden from md and up (the header has the tabs). --}}
<nav class="fixed inset-x-0 bottom-0 z-40 border-t border-stone-300 bg-white pb-[env(safe-area-inset-bottom)] md:hidden"
     aria-label="{{ __('Navigation principale') }}">
    <ul class="grid grid-cols-4 text-[11px] font-semibold">
        @foreach ($items as $item)
            <li>
                <a href="{{ $item['href'] }}" wire:navigate @if ($item['active']) aria-current="page" @endif
                   @class(['relative flex flex-col items-center gap-0.5 pb-1.5 pt-2', 'text-brand-600' => $item['active'], 'text-stone-600' => ! $item['active']])>
                    @if ($item['active'])
                        <span class="absolute inset-x-4 top-0 h-[3px] rounded-b bg-brand-600" aria-hidden="true"></span>
                    @endif
                    <x-ui.icon :name="$item['icon']" size="22" />
                    <span>{{ $item['label'] }}</span>
                </a>
            </li>
        @endforeach
    </ul>
</nav>

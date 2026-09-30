{{-- Mobile navigation, thumb-reachable. Hidden from sm and up. --}}
<nav class="fixed inset-x-0 bottom-0 z-40 border-t border-stone-200 bg-white pb-[env(safe-area-inset-bottom)] sm:hidden"
     aria-label="{{ __('Navigation principale') }}">
    <ul class="grid grid-cols-2 text-xs">
        <li>
            <a href="{{ route('locale.home') }}" wire:navigate
               @class(['flex flex-col items-center gap-0.5 py-2', 'text-brand-700' => request()->routeIs('city.home', 'locale.home'), 'text-stone-600' => ! request()->routeIs('city.home', 'locale.home')])>
                <x-ui.icon name="house" />
                <span>{{ __('Accueil') }}</span>
            </a>
        </li>
        <li>
            @auth
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="flex w-full flex-col items-center gap-0.5 py-2 text-stone-600">
                        <x-ui.icon name="log-out" />
                        <span>{{ __('Déconnexion') }}</span>
                    </button>
                </form>
            @else
                <a href="{{ route('login') }}" wire:navigate
                   @class(['flex flex-col items-center gap-0.5 py-2', 'text-brand-700' => request()->routeIs('login'), 'text-stone-600' => ! request()->routeIs('login')])>
                    <x-ui.icon name="user" />
                    <span>{{ __('Connexion') }}</span>
                </a>
            @endauth
        </li>
    </ul>
</nav>

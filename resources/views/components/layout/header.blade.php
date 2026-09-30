@php
    use App\Support\Locales;
@endphp
<header class="sticky top-0 z-40 border-b border-stone-200 bg-white/95 backdrop-blur">
    <div class="mx-auto flex h-14 max-w-5xl items-center justify-between gap-3 px-4">
        <a href="{{ route('locale.home') }}" wire:navigate class="text-lg font-bold text-brand-700">
            {{ config('app.name') }}
        </a>

        <div class="flex items-center gap-2">
            <div x-data="{ open: false }" class="relative">
                <button type="button" x-on:click="open = ! open" x-bind:aria-expanded="open"
                        class="flex items-center gap-1 rounded-lg px-2 py-1.5 text-sm text-stone-700 hover:bg-stone-100">
                    <x-ui.icon name="globe" size="18" />
                    <span>{{ Locales::nativeName(app()->getLocale()) }}</span>
                </button>
                <ul x-show="open" x-cloak x-on:click.outside="open = false"
                    class="absolute end-0 mt-1 w-40 overflow-hidden rounded-lg border border-stone-200 bg-white py-1 shadow-lg">
                    @foreach (Locales::supported() as $code)
                        <li>
                            <a href="{{ Locales::switchUrl(request(), $code) }}" hreflang="{{ $code }}" lang="{{ $code }}"
                               @class(['block px-3 py-2 text-sm hover:bg-stone-100', 'font-semibold text-brand-700' => $code === app()->getLocale()])>
                                {{ Locales::nativeName($code) }}
                            </a>
                        </li>
                    @endforeach
                </ul>
            </div>

            @auth
                <form method="POST" action="{{ route('logout') }}" class="hidden sm:block">
                    @csrf
                    <button type="submit" class="flex items-center gap-1 rounded-lg px-2 py-1.5 text-sm text-stone-700 hover:bg-stone-100">
                        <x-ui.icon name="log-out" size="18" />
                        <span>{{ __('Déconnexion') }}</span>
                    </button>
                </form>
            @else
                <a href="{{ route('login') }}" wire:navigate class="rounded-lg bg-brand-600 px-3 py-1.5 text-sm font-medium text-white hover:bg-brand-700">
                    {{ __('Connexion') }}
                </a>
            @endauth
        </div>
    </div>
</header>

{{-- Small grey link line, like the legal links under a social network's side rail. --}}
<footer class="mx-auto max-w-[1240px] px-4 pb-28 pt-4 text-[13px] leading-6 text-stone-600 md:pb-8">
    <nav aria-label="{{ __('Informations légales') }}">
        <ul class="flex flex-wrap gap-x-1">
            @foreach (['a-propos', 'cgu', 'confidentialite', 'regles-des-avis', 'cookies', 'mentions-legales', 'contact'] as $slug)
                <li class="after:ms-1 after:content-['·']">
                    <a href="{{ route('page.show', ['page' => $slug]) }}" wire:navigate class="hover:underline">{{ __("pages.{$slug}") }}</a>
                </li>
            @endforeach
            @auth
                <li class="after:ms-1 after:content-['·']"><a href="{{ route('account.privacy') }}" wire:navigate class="hover:underline">{{ __('Mes données') }}</a></li>
            @endauth
            <li class="after:ms-1 after:content-['·']">
                <button type="button" x-data x-on:click="$dispatch('open-cookie-consent')" class="hover:underline">{{ __('Gérer les cookies') }}</button>
            </li>
            @php $footerCity = request()->route('city'); @endphp
            @if ($footerCity instanceof \App\Models\City)
                <li class="after:ms-1 after:content-['·']"><a href="{{ route('city.add', ['city' => $footerCity]) }}" class="font-semibold text-brand-600 hover:underline">{{ __('Ajouter un établissement') }}</a></li>
            @endif
            <li>
                {{ config('app.name') }} &copy; {{ now()->year }}
                @if (config('directory.cndp_number'))
                    · {{ __('Déclaration CNDP n° :number', ['number' => config('directory.cndp_number')]) }}
                @endif
            </li>
        </ul>
    </nav>
</footer>

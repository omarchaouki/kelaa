{{-- Non-essential cookies stay off until the visitor accepts (CNDP / law 09-08). --}}
<div x-data="cookieConsent" x-on:open-cookie-consent.window="reopen()" x-show="open" x-cloak
     role="dialog" aria-modal="false" aria-labelledby="cookie-consent-title"
     class="fb-card fixed inset-x-2 bottom-20 z-40 mx-auto max-w-xl p-4 shadow-lg sm:bottom-4"
     wire:ignore>
    <h2 id="cookie-consent-title" class="font-semibold">{{ __('Cookies') }}</h2>
    <p class="mt-1 text-sm text-stone-600">
        {{ __('Nous utilisons uniquement les cookies nécessaires au fonctionnement du site (session, sécurité, langue). Avec votre accord, nous pourrions mesurer l’audience de façon anonyme.') }}
        <a href="{{ route('page.show', ['page' => 'cookies']) }}" class="text-brand-600 hover:underline">{{ __('En savoir plus') }}</a>
    </p>
    <div class="mt-3 flex flex-wrap gap-2">
        <button type="button" x-on:click="choose(false)" class="fb-btn fb-btn-gray">{{ __('Refuser') }}</button>
        <button type="button" x-on:click="choose(true)" class="fb-btn fb-btn-gray">{{ __('Accepter') }}</button>
    </div>
</div>

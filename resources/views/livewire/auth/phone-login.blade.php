<div class="mx-auto grid max-w-[980px] items-center gap-8 py-6 md:min-h-[calc(100dvh-180px)] md:grid-cols-[1fr_400px] md:py-10">
    {{-- Welcome side, like a social network's sign-in page. --}}
    <div class="text-center md:text-start">
        <p class="text-[40px] font-extrabold leading-none tracking-tight text-brand-600 md:text-[56px]">{{ config('app.name') }}</p>
        <p class="mx-auto mt-3 max-w-md text-[20px] leading-7 md:mx-0 md:text-[26px] md:leading-8">
            {{ __('Trouvez un artisan, une pharmacie de garde ou un commerce, et partagez vos avis avec vos voisins.') }}
        </p>
    </div>

    <div>
        <div class="fb-card p-4 shadow-[0_2px_4px_rgb(0_0_0/0.1),0_8px_16px_rgb(0_0_0/0.1)]">
            <h1 class="mb-3 text-center text-[20px] font-bold">{{ __('Connexion') }}</h1>

            @if ($normalizedPhone === null)
                <form wire:submit="sendCode" class="space-y-3">
                    <div>
                        <label for="phone" class="sr-only">{{ __('Numéro de mobile') }}</label>
                        {{-- Phone numbers are always written left to right, even in Arabic. --}}
                        <input id="phone" type="tel" wire:model="phone" inputmode="tel" autocomplete="tel" dir="ltr"
                               placeholder="{{ __('Numéro de mobile') }} (06 12 34 56 78)" required autofocus
                               class="fb-field h-[52px] text-start text-[17px]">
                        @error('phone') <p class="mt-1 text-[13px] text-red-700" role="alert">{{ $message }}</p> @enderror
                    </div>
                    <p class="text-[13px] text-stone-600">{{ __('Entrez votre numéro de mobile, nous vous envoyons un code de connexion.') }}</p>
                    <button type="submit" wire:loading.attr="disabled" class="fb-btn fb-btn-primary h-12 w-full text-[20px]">
                        {{ __('Recevoir le code') }}
                    </button>
                </form>
            @else
                <p class="text-center text-[15px] text-stone-600">
                    {{ __('Code envoyé au') }} <span dir="ltr" class="font-semibold text-stone-900">{{ $displayPhone }}</span>.
                </p>
                <form wire:submit="verify" class="mt-3 space-y-3">
                    <div>
                        <label for="code" class="sr-only">{{ __('Code à :digits chiffres', ['digits' => config('otp.length')]) }}</label>
                        <input id="code" type="text" wire:model="code" inputmode="numeric" autocomplete="one-time-code" dir="ltr"
                               maxlength="{{ config('otp.length') }}" required autofocus placeholder="••••••"
                               class="fb-field h-[52px] text-center text-2xl tracking-[0.5em]">
                        @error('code') <p class="mt-1 text-[13px] text-red-700" role="alert">{{ $message }}</p> @enderror
                    </div>
                    <button type="submit" wire:loading.attr="disabled" class="fb-btn fb-btn-primary h-12 w-full text-[20px]">
                        {{ __('Se connecter') }}
                    </button>
                    <div class="flex justify-center gap-4 text-[14px]">
                        <button type="button" wire:click="sendCode" class="text-brand-600 hover:underline">{{ __('Renvoyer le code') }}</button>
                        <button type="button" wire:click="changeNumber" class="text-brand-600 hover:underline">{{ __('Changer de numéro') }}</button>
                    </div>
                </form>
            @endif

            @if ($normalizedPhone === null && (config('auth_options.email_password') || config('auth_options.google')))
                <div class="fb-divider my-4"></div>
                <div class="space-y-2">
                    @if (config('auth_options.google'))
                        <a href="{{ route('login.google') }}" class="fb-btn fb-btn-gray h-11 w-full">{{ __('Continuer avec Google') }}</a>
                    @endif
                    @if (config('auth_options.email_password'))
                        <a href="{{ route('login.email') }}" wire:navigate class="fb-btn fb-btn-green h-11 w-full">{{ __('Se connecter avec un e-mail') }}</a>
                    @endif
                </div>
            @endif
        </div>

        <p class="mt-6 text-center text-[13px] text-stone-600">
            {{ __('En continuant, vous acceptez nos') }}
            <a href="{{ route('page.show', ['page' => 'cgu']) }}" class="font-semibold hover:underline">{{ __('pages.cgu') }}</a>
            {{ __('et notre') }}
            <a href="{{ route('page.show', ['page' => 'confidentialite']) }}" class="font-semibold hover:underline">{{ __('pages.confidentialite') }}</a>.
        </p>
    </div>
</div>

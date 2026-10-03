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
            <div class="mb-4 grid grid-cols-2 gap-1 rounded-lg bg-stone-100 p-1" role="tablist">
                <button type="button" role="tab" wire:click="switchMode('login')" aria-selected="{{ $mode === 'login' ? 'true' : 'false' }}"
                        @class(['h-10 rounded-md text-[15px] font-semibold', 'bg-white text-brand-600 shadow-sm' => $mode === 'login', 'text-stone-600' => $mode !== 'login'])>
                    {{ __('Se connecter') }}
                </button>
                <button type="button" role="tab" wire:click="switchMode('register')" aria-selected="{{ $mode === 'register' ? 'true' : 'false' }}"
                        @class(['h-10 rounded-md text-[15px] font-semibold', 'bg-white text-brand-600 shadow-sm' => $mode === 'register', 'text-stone-600' => $mode !== 'register'])>
                    {{ __('Créer un compte') }}
                </button>
            </div>

            <h1 class="sr-only">{{ $mode === 'register' ? __('Créer un compte') : __('Connexion') }}</h1>

            <form wire:submit="{{ $mode === 'register' ? 'register' : 'login' }}" class="space-y-3" wire:key="form-{{ $mode }}">
                <div>
                    <label for="phone" class="sr-only">{{ __('Numéro de mobile') }}</label>
                    {{-- Phone numbers are always written left to right, even in Arabic. --}}
                    <input id="phone" type="tel" wire:model="phone" inputmode="tel" autocomplete="tel" dir="ltr" maxlength="20"
                           placeholder="{{ __('Numéro de mobile') }} (06 12 34 56 78)" required autofocus
                           class="fb-field h-[52px] text-start text-[17px]">
                    @error('phone') <p class="mt-1 text-[13px] text-red-700" role="alert">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="password" class="sr-only">{{ __('Mot de passe') }}</label>
                    <input id="password" type="password" wire:model="password" required maxlength="255"
                           autocomplete="{{ $mode === 'register' ? 'new-password' : 'current-password' }}"
                           placeholder="{{ __('Mot de passe') }}"
                           class="fb-field h-[52px] text-[17px]">
                    @error('password') <p class="mt-1 text-[13px] text-red-700" role="alert">{{ $message }}</p> @enderror
                </div>

                @if ($mode === 'register')
                    <div>
                        <label for="password_confirmation" class="sr-only">{{ __('Confirmer le mot de passe') }}</label>
                        <input id="password_confirmation" type="password" wire:model="password_confirmation" required maxlength="255"
                               autocomplete="new-password" placeholder="{{ __('Confirmer le mot de passe') }}"
                               class="fb-field h-[52px] text-[17px]">
                    </div>
                    <p class="text-[13px] text-stone-600">{{ __('Au moins 8 caractères, avec des lettres et des chiffres.') }}</p>
                    <button type="submit" wire:loading.attr="disabled" class="fb-btn fb-btn-green h-12 w-full text-[20px]">
                        {{ __('Créer mon compte') }}
                    </button>
                @else
                    <button type="submit" wire:loading.attr="disabled" class="fb-btn fb-btn-primary h-12 w-full text-[20px]">
                        {{ __('Se connecter') }}
                    </button>
                @endif
            </form>

            <div class="fb-divider my-4"></div>
            <p class="text-center text-[14px] text-stone-600">
                @if ($mode === 'register')
                    {{ __('Déjà un compte ?') }}
                    <button type="button" wire:click="switchMode('login')" class="font-semibold text-brand-600 hover:underline">{{ __('Se connecter') }}</button>
                @else
                    {{ __('Pas encore de compte ?') }}
                    <button type="button" wire:click="switchMode('register')" class="font-semibold text-brand-600 hover:underline">{{ __('Créer un compte') }}</button>
                @endif
            </p>

            @if (config('auth_options.email_password') || config('auth_options.google'))
                <div class="mt-3 space-y-2">
                    @if (config('auth_options.google'))
                        <a href="{{ route('login.google') }}" class="fb-btn fb-btn-gray h-11 w-full">{{ __('Continuer avec Google') }}</a>
                    @endif
                    @if (config('auth_options.email_password'))
                        <a href="{{ route('login.email') }}" wire:navigate class="fb-btn fb-btn-gray h-11 w-full">{{ __('Se connecter avec un e-mail') }}</a>
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

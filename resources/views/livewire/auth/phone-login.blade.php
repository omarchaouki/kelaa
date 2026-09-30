<div class="mx-auto max-w-sm py-6">
    <h1 class="text-2xl font-bold">{{ __('Connexion') }}</h1>

    @if ($normalizedPhone === null)
        <p class="mt-2 text-stone-600">{{ __('Entrez votre numéro de mobile, nous vous envoyons un code de connexion.') }}</p>

        <form wire:submit="sendCode" class="mt-6 space-y-4">
            <div>
                <label for="phone" class="block text-sm font-medium">{{ __('Numéro de mobile') }}</label>
                {{-- Phone numbers are always written left to right, even in Arabic. --}}
                <input id="phone" type="tel" wire:model="phone" inputmode="tel" autocomplete="tel" dir="ltr"
                       placeholder="06 12 34 56 78" required autofocus
                       class="mt-1 block w-full rounded-lg border border-stone-300 px-3 py-2.5 text-start text-lg focus:border-brand-600 focus:outline-none focus:ring-2 focus:ring-brand-600/30">
                @error('phone') <p class="mt-1 text-sm text-red-700" role="alert">{{ $message }}</p> @enderror
            </div>

            <button type="submit" wire:loading.attr="disabled"
                    class="w-full rounded-lg bg-brand-600 px-4 py-3 font-semibold text-white hover:bg-brand-700 disabled:opacity-60">
                {{ __('Recevoir le code') }}
            </button>
        </form>
    @else
        <p class="mt-2 text-stone-600">
            {{ __('Code envoyé au') }} <span dir="ltr" class="font-medium">{{ $displayPhone }}</span>.
            <button type="button" wire:click="changeNumber" class="text-brand-700 underline">{{ __('Modifier') }}</button>
        </p>

        <form wire:submit="verify" class="mt-6 space-y-4">
            <div>
                <label for="code" class="block text-sm font-medium">{{ __('Code à :digits chiffres', ['digits' => config('otp.length')]) }}</label>
                <input id="code" type="text" wire:model="code" inputmode="numeric" autocomplete="one-time-code" dir="ltr"
                       maxlength="{{ config('otp.length') }}" required autofocus
                       class="mt-1 block w-full rounded-lg border border-stone-300 px-3 py-2.5 text-center text-2xl tracking-[0.5em] focus:border-brand-600 focus:outline-none focus:ring-2 focus:ring-brand-600/30">
                @error('code') <p class="mt-1 text-sm text-red-700" role="alert">{{ $message }}</p> @enderror
            </div>

            <button type="submit" wire:loading.attr="disabled"
                    class="w-full rounded-lg bg-brand-600 px-4 py-3 font-semibold text-white hover:bg-brand-700 disabled:opacity-60">
                {{ __('Se connecter') }}
            </button>

            <button type="button" wire:click="sendCode" class="w-full text-sm text-brand-700 underline">
                {{ __('Renvoyer le code') }}
            </button>
        </form>
    @endif

    <p class="mt-8 text-xs text-stone-500">
        {{ __('En continuant, vous acceptez nos') }}
        <a href="{{ route('page.show', ['page' => 'cgu']) }}" class="underline">{{ __('pages.cgu') }}</a>
        {{ __('et notre') }}
        <a href="{{ route('page.show', ['page' => 'confidentialite']) }}" class="underline">{{ __('pages.confidentialite') }}</a>.
    </p>
</div>

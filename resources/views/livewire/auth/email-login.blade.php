<div class="mx-auto max-w-[400px] py-6 md:py-10">
    <div class="fb-card p-4 shadow-[0_2px_4px_rgb(0_0_0/0.1),0_8px_16px_rgb(0_0_0/0.1)]">
        <h1 class="mb-3 text-center text-[20px] font-bold">{{ __('Connexion par e-mail') }}</h1>

        <form wire:submit="login" class="space-y-3">
            <div>
                <label for="email" class="sr-only">{{ __('E-mail') }}</label>
                <input id="email" type="email" wire:model="email" autocomplete="email" dir="ltr" required autofocus
                       placeholder="{{ __('E-mail') }}" class="fb-field h-[52px] text-start text-[17px]">
                @error('email') <p class="mt-1 text-[13px] text-red-700" role="alert">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="password" class="sr-only">{{ __('Mot de passe') }}</label>
                <input id="password" type="password" wire:model="password" autocomplete="current-password" required
                       placeholder="{{ __('Mot de passe') }}" class="fb-field h-[52px] text-[17px]">
                @error('password') <p class="mt-1 text-[13px] text-red-700" role="alert">{{ $message }}</p> @enderror
            </div>

            <label class="flex items-center gap-2 text-[14px]">
                <input type="checkbox" wire:model="remember" class="rounded border-stone-300 text-brand-600">
                {{ __('Rester connecté') }}
            </label>

            <button type="submit" wire:loading.attr="disabled" class="fb-btn fb-btn-primary h-12 w-full text-[20px]">
                {{ __('Se connecter') }}
            </button>
        </form>

        <div class="fb-divider my-4"></div>

        <a href="{{ route('login') }}" wire:navigate class="fb-btn fb-btn-green h-11 w-full">
            {{ __('Se connecter avec mon numéro de mobile') }}
        </a>
    </div>
</div>

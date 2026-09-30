<div class="mx-auto max-w-sm py-6">
    <h1 class="text-2xl font-bold">{{ __('Connexion par e-mail') }}</h1>

    <form wire:submit="login" class="mt-6 space-y-4">
        <div>
            <label for="email" class="block text-sm font-medium">{{ __('E-mail') }}</label>
            <input id="email" type="email" wire:model="email" autocomplete="email" dir="ltr" required autofocus
                   class="mt-1 block w-full rounded-lg border border-stone-300 px-3 py-2.5 text-start focus:border-brand-600 focus:outline-none focus:ring-2 focus:ring-brand-600/30">
            @error('email') <p class="mt-1 text-sm text-red-700" role="alert">{{ $message }}</p> @enderror
        </div>

        <div>
            <label for="password" class="block text-sm font-medium">{{ __('Mot de passe') }}</label>
            <input id="password" type="password" wire:model="password" autocomplete="current-password" required
                   class="mt-1 block w-full rounded-lg border border-stone-300 px-3 py-2.5 focus:border-brand-600 focus:outline-none focus:ring-2 focus:ring-brand-600/30">
            @error('password') <p class="mt-1 text-sm text-red-700" role="alert">{{ $message }}</p> @enderror
        </div>

        <label class="flex items-center gap-2 text-sm">
            <input type="checkbox" wire:model="remember" class="rounded border-stone-300 text-brand-600">
            {{ __('Rester connecté') }}
        </label>

        <button type="submit" wire:loading.attr="disabled"
                class="w-full rounded-lg bg-brand-600 px-4 py-3 font-semibold text-white hover:bg-brand-700 disabled:opacity-60">
            {{ __('Se connecter') }}
        </button>
    </form>

    <a href="{{ route('login') }}" wire:navigate class="mt-6 block text-center text-sm text-brand-700 underline">
        {{ __('Se connecter avec mon numéro de mobile') }}
    </a>
</div>

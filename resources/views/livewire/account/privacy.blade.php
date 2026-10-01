<div class="mx-auto max-w-xl space-y-8 py-6">
    <header>
        <h1 class="text-2xl font-bold">{{ __('Mes données') }}</h1>
        <p class="mt-2 text-stone-600">
            {{ __('Conformément à la loi 09-08, vous pouvez obtenir une copie de vos données et supprimer votre compte.') }}
            <a href="{{ route('page.show', ['page' => 'confidentialite']) }}" class="text-brand-700 underline">{{ __('Politique de confidentialité') }}</a>
        </p>
    </header>

    @if ($flash)
        <p class="rounded-lg bg-brand-50 p-3 text-sm text-brand-800" role="status">{{ $flash }}</p>
    @endif

    <section class="space-y-3 rounded-xl border border-stone-200 bg-white p-4">
        <h2 class="text-lg font-semibold">{{ __('Télécharger mes données') }}</h2>
        <p class="text-sm text-stone-600">{{ __('Un fichier JSON avec votre profil, vos avis, réponses, signalements, demandes et paiements. Le lien reste valable 7 jours.') }}</p>
        <button type="button" wire:click="requestExport" wire:loading.attr="disabled"
                class="rounded-lg border border-stone-300 px-4 py-2 text-sm font-medium hover:bg-stone-50 disabled:opacity-60">
            {{ __('Demander une copie') }}
        </button>
        @error('export') <p class="text-sm text-red-700" role="alert">{{ $message }}</p> @enderror
    </section>

    <section class="space-y-3 rounded-xl border border-red-200 bg-white p-4">
        <h2 class="text-lg font-semibold text-red-800">{{ __('Supprimer mon compte') }}</h2>
        <ul class="list-disc space-y-1 ps-5 text-sm text-stone-600">
            <li>{{ __('Votre profil, vos favoris, vos votes et vos demandes de revendication sont supprimés.') }}</li>
            <li>{{ __('Vos avis (:count) restent visibles signés « Utilisateur supprimé », sauf si vous demandez leur suppression.', ['count' => $reviewCount]) }}</li>
            @if ($ownsBusinesses)
                <li>{{ __('Vos établissements restent dans l’annuaire mais ne sont plus gérés par vous.') }}</li>
            @endif
            <li>{{ __('Les factures sont conservées (obligation comptable).') }}</li>
        </ul>

        <label class="flex items-start gap-2 text-sm">
            <input type="checkbox" wire:model="removeReviews" class="mt-1 rounded border-stone-300">
            <span>{{ __('Supprimer aussi mes avis et mes réponses') }}</span>
        </label>
        <label class="flex items-start gap-2 text-sm">
            <input type="checkbox" wire:model="understood" class="mt-1 rounded border-stone-300">
            <span>{{ __('Je comprends que la suppression est définitive.') }}</span>
        </label>
        @error('understood') <p class="text-sm text-red-700" role="alert">{{ $message }}</p> @enderror

        @if ($user->hasVerifiedPhone())
            @if (! $codeSent)
                <button type="button" wire:click="startDeletion" wire:loading.attr="disabled"
                        class="rounded-lg bg-red-700 px-4 py-2 text-sm font-semibold text-white hover:bg-red-800 disabled:opacity-60">
                    {{ __('Recevoir un code de confirmation') }}
                </button>
            @else
                <form wire:submit="confirmDeletion" class="space-y-3">
                    <p class="text-sm text-stone-600">{{ __('Code envoyé au') }} <span dir="ltr" class="font-medium">{{ $displayPhone }}</span>.</p>
                    <div>
                        <label for="delete-code" class="block text-sm font-medium">{{ __('Code à :digits chiffres', ['digits' => config('otp.length')]) }}</label>
                        <input id="delete-code" type="text" wire:model="code" inputmode="numeric" autocomplete="one-time-code" dir="ltr"
                               maxlength="{{ config('otp.length') }}" required
                               class="mt-1 block w-40 rounded-lg border border-stone-300 px-3 py-2 text-center text-xl tracking-[0.4em]">
                        @error('code') <p class="mt-1 text-sm text-red-700" role="alert">{{ $message }}</p> @enderror
                    </div>
                    <button type="submit" wire:loading.attr="disabled"
                            class="rounded-lg bg-red-700 px-4 py-2 text-sm font-semibold text-white hover:bg-red-800 disabled:opacity-60">
                        {{ __('Supprimer définitivement mon compte') }}
                    </button>
                </form>
            @endif
        @else
            <button type="button" wire:click="confirmDeletion" wire:confirm="{{ __('Supprimer définitivement votre compte ?') }}" wire:loading.attr="disabled"
                    class="rounded-lg bg-red-700 px-4 py-2 text-sm font-semibold text-white hover:bg-red-800 disabled:opacity-60">
                {{ __('Supprimer définitivement mon compte') }}
            </button>
        @endif
    </section>
</div>

@php
    $input = 'mt-1 block w-full rounded-lg border border-stone-300 px-3 py-2 focus:border-brand-600 focus:outline-none focus:ring-2 focus:ring-brand-600/30';
@endphp
<div class="mx-auto max-w-xl space-y-4 py-4">
    <a href="{{ route('city.business', ['city' => $city, 'business' => $business]) }}" wire:navigate class="text-sm text-brand-700 underline">← {{ $business->name }}</a>
    <h1 class="text-2xl font-bold">{{ __('Revendiquer :business', ['business' => $business->name]) }}</h1>

    @if ($isOwner)
        <p class="rounded-xl border border-emerald-200 bg-emerald-50 p-4">
            {{ __('Cette fiche est à vous.') }}
            <a href="{{ route('dashboard.business', ['business' => $business->id]) }}" class="font-semibold underline">{{ __('Mon espace professionnel') }}</a>
        </p>
    @elseif ($alreadyOwned)
        <p class="rounded-xl border border-stone-200 bg-white p-4">
            {{ __('Cette fiche a déjà un propriétaire. S’il s’agit d’une erreur, contactez-nous.') }}
            <a href="{{ route('page.show', ['page' => 'contact']) }}" class="underline">{{ __('pages.contact') }}</a>
        </p>
    @elseif ($result === 'documents_sent' || $pending && $phoneClaimId === null)
        <p class="rounded-xl border border-emerald-200 bg-emerald-50 p-4" role="status">
            {{ __('Votre demande est en cours d’examen. Nous vous prévenons dès qu’elle est traitée.') }}
        </p>
    @else
        <p class="text-stone-600">{{ __('Une fois la fiche à vous, vous pourrez répondre aux avis, mettre à jour vos horaires et vos photos.') }}</p>

        @if ($maskedPhone)
            <section class="space-y-3 rounded-xl border border-stone-200 bg-white p-4">
                <h2 class="font-semibold">{{ __('Option 1 : code par SMS (immédiat)') }}</h2>
                <p class="text-sm text-stone-600">{{ __('Nous envoyons un code au numéro affiché sur la fiche :') }} <span dir="ltr" class="font-medium">{{ $maskedPhone }}</span></p>

                @if ($phoneClaimId === null)
                    <button type="button" wire:click="sendCode" class="rounded-lg bg-brand-600 px-4 py-2 font-semibold text-white">{{ __('Recevoir le code') }}</button>
                @else
                    <form wire:submit="verifyCode" class="flex gap-2">
                        <input type="text" wire:model="code" inputmode="numeric" autocomplete="one-time-code" maxlength="{{ config('otp.length') }}" dir="ltr"
                               class="w-36 rounded-lg border border-stone-300 px-3 py-2 text-center tracking-widest" aria-label="{{ __('Code') }}">
                        <button type="submit" class="rounded-lg bg-brand-600 px-4 py-2 font-semibold text-white">{{ __('Valider') }}</button>
                    </form>
                @endif
                @error('code') <p class="text-sm text-red-700" role="alert">{{ $message }}</p> @enderror
            </section>
        @endif

        <section class="space-y-3 rounded-xl border border-stone-200 bg-white p-4">
            <h2 class="font-semibold">{{ $maskedPhone ? __('Option 2 : justificatifs') : __('Envoyer des justificatifs') }}</h2>
            <p class="text-sm text-stone-600">{{ __('Registre de commerce, patente ou carte d’identité (artisans). Ils restent privés et ne sont vus que par notre équipe.') }}</p>
            <form wire:submit="submitDocuments" class="space-y-3">
                <input type="file" wire:model="documents" multiple accept="application/pdf,image/jpeg,image/png,image/webp" class="block text-sm">
                <div wire:loading wire:target="documents" class="text-sm text-stone-500">{{ __('Envoi…') }}</div>
                @error('documents') <p class="text-sm text-red-700">{{ $message }}</p> @enderror
                @error('documents.*') <p class="text-sm text-red-700">{{ $message }}</p> @enderror
                <textarea wire:model="notes" rows="2" maxlength="1000" dir="auto" placeholder="{{ __('Message pour notre équipe (facultatif)') }}" class="{{ $input }}"></textarea>
                <button type="submit" wire:loading.attr="disabled" class="rounded-lg border border-brand-600 px-4 py-2 font-semibold text-brand-700">{{ __('Envoyer les justificatifs') }}</button>
            </form>
        </section>
    @endif
</div>

@php
    $input = 'mt-1 block w-full rounded-lg border border-stone-300 px-3 py-2 focus:border-brand-600 focus:outline-none focus:ring-2 focus:ring-brand-600/30';
@endphp
<div class="mx-auto max-w-xl space-y-4 py-4">
    <a href="{{ route('city.business', ['city' => $city, 'business' => $business]) }}" wire:navigate class="text-sm text-brand-600 hover:underline">← {{ $business->name }}</a>
    <h1 class="text-2xl font-bold">{{ __('Proposer une modification') }}</h1>

    @if ($sent)
        <p class="rounded-lg bg-brand-50 p-4 text-brand-900" role="status">
            {{ __('Merci ! Notre équipe vérifiera votre proposition avant de modifier la fiche.') }}
        </p>
    @else
        <p class="text-sm text-stone-600">
            {{ $isOwner
                ? __('Ces informations identifient votre établissement : chaque changement est vérifié par notre équipe.')
                : __('Une information est fausse ou a changé ? Proposez la correction, notre équipe la vérifiera.') }}
        </p>

        <form wire:submit="submit" class="fb-card space-y-3 p-4">
            <div>
                <label for="name" class="text-sm font-medium">{{ __('Nom') }}</label>
                <input id="name" type="text" wire:model="name" maxlength="120" class="{{ $input }}">
                @error('name') <p class="mt-1 text-sm text-red-700">{{ $message }}</p> @enderror
            </div>
            <div class="grid gap-3 sm:grid-cols-2">
                <div>
                    <label for="whatsapp" class="text-sm font-medium">WhatsApp</label>
                    <input id="whatsapp" type="tel" wire:model="whatsapp" dir="ltr" class="{{ $input }}">
                    @error('whatsapp') <p class="mt-1 text-sm text-red-700">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="phone" class="text-sm font-medium">{{ __('Téléphone') }}</label>
                    <input id="phone" type="tel" wire:model="phone" dir="ltr" class="{{ $input }}">
                    @error('phone') <p class="mt-1 text-sm text-red-700">{{ $message }}</p> @enderror
                </div>
            </div>
            <div>
                <label for="address" class="text-sm font-medium">{{ __('Adresse') }}</label>
                <input id="address" type="text" wire:model="address" maxlength="255" class="{{ $input }}">
                @error('address') <p class="mt-1 text-sm text-red-700">{{ $message }}</p> @enderror
            </div>
            <div>
                <label for="website" class="text-sm font-medium">{{ __('Site web') }}</label>
                <input id="website" type="url" wire:model="website" dir="ltr" class="{{ $input }}">
                @error('website') <p class="mt-1 text-sm text-red-700">{{ $message }}</p> @enderror
            </div>
            @unless ($isOwner)
                <label class="flex items-center gap-2 text-sm">
                    <input type="checkbox" wire:model="permanentlyClosed" class="rounded border-stone-300">
                    {{ __('Cet établissement est fermé définitivement') }}
                </label>
            @endunless
            <div>
                <label for="comment" class="text-sm font-medium">{{ __('Commentaire (facultatif)') }}</label>
                <textarea id="comment" wire:model="comment" rows="2" maxlength="1000" dir="auto" class="{{ $input }}"></textarea>
                @error('comment') <p class="mt-1 text-sm text-red-700">{{ $message }}</p> @enderror
            </div>
            <button type="submit" class="fb-btn fb-btn-primary">{{ __('Envoyer') }}</button>
        </form>
    @endif
</div>

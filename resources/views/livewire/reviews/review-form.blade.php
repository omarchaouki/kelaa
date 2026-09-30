<div class="rounded-xl border border-stone-200 bg-white p-4">
    @if ($flash)
        <p class="mb-3 rounded-lg bg-emerald-50 p-3 text-sm text-emerald-800" role="status">{{ $flash }}</p>
    @endif

    @if ($existing && ! $editing)
        <div class="flex items-center justify-between gap-2">
            <div>
                <p class="font-medium">{{ __('Votre avis') }}</p>
                <x-directory.stars :rating="$existing->rating" size="14" />
                @if ($existing->status !== \App\Enums\ReviewStatus::Published)
                    <p class="mt-1 text-sm text-stone-600">{{ __('Statut : :status', ['status' => $existing->status->getLabel()]) }}</p>
                @endif
            </div>
            @if ($existing->status !== \App\Enums\ReviewStatus::HiddenByModeration && $denial === null)
                <button type="button" wire:click="startEditing" class="rounded-lg border border-stone-300 px-3 py-1.5 text-sm">{{ __('Modifier') }}</button>
            @endif
        </div>
    @elseif ($denial === \App\Services\Reviews\ReviewDenial::Guest)
        <p class="text-sm">{{ __('Vous connaissez cet établissement ?') }}</p>
        <button type="button" wire:click="save" class="mt-2 rounded-lg bg-brand-600 px-4 py-2 text-sm font-semibold text-white">{{ __('Connectez-vous pour laisser un avis') }}</button>
    @elseif ($denial === \App\Services\Reviews\ReviewDenial::PhoneNotVerified)
        <p class="text-sm">{{ $denial->message() }}</p>
        <a href="{{ route('account.phone') }}" class="mt-2 inline-block text-sm font-semibold text-brand-700 underline">{{ __('Vérifier mon numéro') }}</a>
    @elseif ($denial !== null)
        <p class="text-sm text-stone-600">{{ $denial->message() }}</p>
    @else
        <form wire:submit="save" class="space-y-3">
            <fieldset>
                <legend class="text-sm font-medium">{{ __('Votre note') }} <span class="text-rose-700">*</span></legend>
                <div class="mt-1 flex gap-1" role="radiogroup" x-data="{ hover: 0 }">
                    @for ($star = 1; $star <= 5; $star++)
                        <label class="cursor-pointer" x-on:mouseenter="hover = {{ $star }}" x-on:mouseleave="hover = 0">
                            <input type="radio" wire:model="rating" value="{{ $star }}" class="sr-only" name="rating">
                            <span class="sr-only">{{ trans_choice(':count étoile|:count étoiles', $star, ['count' => $star]) }}</span>
                            <svg width="32" height="32" viewBox="0 0 24 24" aria-hidden="true"
                                 x-bind:class="(hover || $wire.rating) >= {{ $star }} ? 'fill-accent-500' : 'fill-stone-300'">
                                <path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/>
                            </svg>
                        </label>
                    @endfor
                </div>
                @error('rating') <p class="mt-1 text-sm text-red-700" role="alert">{{ $message }}</p> @enderror
            </fieldset>

            <div>
                <label for="review-body" class="text-sm font-medium">{{ __('Votre expérience (facultatif)') }}</label>
                <textarea id="review-body" wire:model="body" rows="4" maxlength="{{ $maxLength }}" dir="auto"
                          class="mt-1 block w-full rounded-lg border border-stone-300 px-3 py-2 focus:border-brand-600 focus:outline-none focus:ring-2 focus:ring-brand-600/30"
                          placeholder="{{ __('Au moins :min caractères si vous écrivez un texte.', ['min' => $minLength]) }}"></textarea>
                @error('body') <p class="mt-1 text-sm text-red-700" role="alert">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="review-photos" class="text-sm font-medium">{{ __('Photos (5 maximum)') }}</label>
                <input id="review-photos" type="file" wire:model="photos" multiple accept="image/jpeg,image/png,image/webp" class="mt-1 block text-sm">
                <div wire:loading wire:target="photos" class="text-sm text-stone-500">{{ __('Envoi…') }}</div>
                @error('photos') <p class="mt-1 text-sm text-red-700" role="alert">{{ $message }}</p> @enderror
                @error('photos.*') <p class="mt-1 text-sm text-red-700" role="alert">{{ $message }}</p> @enderror

                <div class="mt-2 flex flex-wrap gap-2">
                    @foreach ($photos as $index => $photo)
                        @if ($photo->isPreviewable())
                            <div class="relative" wire:key="upload-{{ $index }}">
                                <img src="{{ $photo->temporaryUrl() }}" alt="" class="size-16 rounded-lg object-cover">
                                <button type="button" wire:click="removeUpload({{ $index }})" class="absolute -end-1 -top-1 rounded-full bg-white p-0.5 shadow" aria-label="{{ __('Retirer') }}">
                                    <x-ui.icon name="x" size="14" />
                                </button>
                            </div>
                        @endif
                    @endforeach
                    @foreach ($existing?->getMedia('photos') ?? [] as $media)
                        <button type="button" wire:click="toggleRemovePhoto({{ $media->id }})" wire:key="media-{{ $media->id }}"
                                @class(['relative rounded-lg', 'opacity-40' => in_array($media->id, $removePhotoIds, true)])
                                aria-pressed="{{ in_array($media->id, $removePhotoIds, true) ? 'true' : 'false' }}"
                                aria-label="{{ __('Retirer') }}">
                            <img src="{{ $media->getUrl('thumb') }}" alt="" class="size-16 rounded-lg object-cover">
                        </button>
                    @endforeach
                </div>
            </div>

            <p class="text-xs text-stone-500">
                {{ __('Votre avis doit respecter nos') }}
                <a href="{{ route('page.show', ['page' => 'regles-des-avis']) }}" class="underline" target="_blank">{{ __('pages.regles-des-avis') }}</a>.
                {{ __('Les avis ne peuvent être ni achetés ni supprimés par les établissements.') }}
            </p>

            <button type="submit" wire:loading.attr="disabled" class="rounded-lg bg-brand-600 px-4 py-2 font-semibold text-white hover:bg-brand-700 disabled:opacity-60">
                {{ $existing ? __('Enregistrer les modifications') : __('Publier mon avis') }}
            </button>
        </form>
    @endif
</div>

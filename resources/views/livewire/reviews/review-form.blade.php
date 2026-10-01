<div class="fb-card p-4">
    @if ($flash)
        <p class="mb-3 rounded-lg bg-brand-50 p-3 text-brand-800" role="status">{{ $flash }}</p>
    @endif

    @if ($existing && ! $editing)
        <div class="flex items-center gap-3">
            <x-ui.avatar :name="auth()->user()?->displayName()" size="40" />
            <div class="min-w-0 flex-1">
                <p class="font-semibold">{{ __('Votre avis') }}</p>
                <x-directory.stars :rating="$existing->rating" size="14" />
                @if ($existing->status !== \App\Enums\ReviewStatus::Published)
                    <p class="text-[13px] text-stone-600">{{ __('Statut : :status', ['status' => $existing->status->getLabel()]) }}</p>
                @endif
            </div>
            @if ($existing->status !== \App\Enums\ReviewStatus::HiddenByModeration && $denial === null)
                <button type="button" wire:click="startEditing" class="fb-btn fb-btn-gray">{{ __('Modifier') }}</button>
            @endif
        </div>
    @elseif ($denial === \App\Services\Reviews\ReviewDenial::Guest)
        {{-- Looks like the composer; clicking it asks to sign in. --}}
        <p class="mb-2 font-semibold">{{ __('Vous connaissez cet établissement ?') }}</p>
        <div class="flex items-center gap-2">
            <span class="flex size-10 shrink-0 items-center justify-center rounded-full bg-stone-200 text-stone-600"><x-ui.icon name="user" size="20" /></span>
            <button type="button" wire:click="save" class="fb-input h-10 flex-1 text-start text-stone-600 hover:bg-stone-200">
                {{ __('Connectez-vous pour laisser un avis') }}
            </button>
        </div>
    @elseif ($denial === \App\Services\Reviews\ReviewDenial::PhoneNotVerified)
        <p>{{ $denial->message() }}</p>
        <a href="{{ route('account.phone') }}" class="fb-btn fb-btn-primary mt-3">{{ __('Vérifier mon numéro') }}</a>
    @elseif ($denial !== null)
        <p class="text-stone-600">{{ $denial->message() }}</p>
    @else
        <form wire:submit="save" class="space-y-3">
            <div class="flex items-center gap-2">
                <x-ui.avatar :name="auth()->user()?->displayName()" size="40" />
                <div>
                    <p class="font-semibold">{{ auth()->user()?->displayName() }}</p>
                    <p class="text-[13px] text-stone-600">{{ __('Avis public') }} · <x-ui.icon name="globe" size="12" class="inline" /></p>
                </div>
            </div>

            <fieldset>
                <legend class="fb-label">{{ __('Votre note') }} <span class="text-rose-700">*</span></legend>
                <div class="mt-1 flex gap-1" role="radiogroup" x-data="{ hover: 0 }">
                    @for ($star = 1; $star <= 5; $star++)
                        <label class="cursor-pointer" x-on:mouseenter="hover = {{ $star }}" x-on:mouseleave="hover = 0">
                            <input type="radio" wire:model="rating" value="{{ $star }}" class="peer sr-only" name="rating">
                            <span class="sr-only">{{ trans_choice(':count étoile|:count étoiles', $star, ['count' => $star]) }}</span>
                            <svg width="34" height="34" viewBox="0 0 24 24" aria-hidden="true" class="rounded peer-focus-visible:outline peer-focus-visible:outline-2 peer-focus-visible:outline-brand-600"
                                 x-bind:class="(hover || $wire.rating) >= {{ $star }} ? 'fill-accent-400' : 'fill-stone-300'">
                                <path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/>
                            </svg>
                        </label>
                    @endfor
                </div>
                @error('rating') <p class="mt-1 text-[13px] text-red-700" role="alert">{{ $message }}</p> @enderror
            </fieldset>

            <div>
                <label for="review-body" class="sr-only">{{ __('Votre expérience (facultatif)') }}</label>
                <textarea id="review-body" wire:model="body" rows="4" maxlength="{{ $maxLength }}" dir="auto"
                          class="block w-full resize-y rounded-lg border-0 bg-stone-100 px-3 py-2 text-[17px] placeholder:text-stone-600 focus:bg-white focus:outline-2 focus:outline-brand-600"
                          placeholder="{{ __('Partagez votre expérience… (au moins :min caractères si vous écrivez un texte)', ['min' => $minLength]) }}"></textarea>
                @error('body') <p class="mt-1 text-[13px] text-red-700" role="alert">{{ $message }}</p> @enderror
            </div>

            <div class="rounded-lg border border-stone-300 p-2">
                <label for="review-photos" class="flex cursor-pointer items-center justify-between gap-2 rounded-md px-2 py-1.5 hover:bg-stone-100">
                    <span class="font-semibold">{{ __('Ajouter des photos (5 maximum)') }}</span>
                    <x-ui.icon name="image" size="22" class="text-online" />
                </label>
                <input id="review-photos" type="file" wire:model="photos" multiple accept="image/jpeg,image/png,image/webp" class="sr-only">
                <div wire:loading wire:target="photos" class="px-2 text-[13px] text-stone-600">{{ __('Envoi…') }}</div>
                @error('photos') <p class="px-2 text-[13px] text-red-700" role="alert">{{ $message }}</p> @enderror
                @error('photos.*') <p class="px-2 text-[13px] text-red-700" role="alert">{{ $message }}</p> @enderror

                @if (count($photos) || $existing?->getMedia('photos')->isNotEmpty())
                    <div class="mt-2 flex flex-wrap gap-2 px-2 pb-1">
                        @foreach ($photos as $index => $photo)
                            @if ($photo->isPreviewable())
                                <div class="relative" wire:key="upload-{{ $index }}">
                                    <img src="{{ $photo->temporaryUrl() }}" alt="" width="64" height="64" class="size-16 rounded-lg object-cover">
                                    <button type="button" wire:click="removeUpload({{ $index }})" class="absolute -end-1.5 -top-1.5 rounded-full bg-white p-0.5 shadow" aria-label="{{ __('Retirer') }}">
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
                                <img src="{{ $media->getUrl('thumb') }}" alt="" width="64" height="64" class="size-16 rounded-lg object-cover">
                            </button>
                        @endforeach
                    </div>
                @endif
            </div>

            <p class="text-[13px] text-stone-600">
                {{ __('Votre avis doit respecter nos') }}
                <a href="{{ route('page.show', ['page' => 'regles-des-avis']) }}" class="font-semibold hover:underline" target="_blank">{{ __('pages.regles-des-avis') }}</a>.
                {{ __('Les avis ne peuvent être ni achetés ni supprimés par les établissements.') }}
            </p>

            <button type="submit" wire:loading.attr="disabled" class="fb-btn fb-btn-primary w-full">
                {{ $existing ? __('Enregistrer les modifications') : __('Publier mon avis') }}
            </button>
        </form>
    @endif
</div>

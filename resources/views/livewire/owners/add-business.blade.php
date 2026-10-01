@php
    $input = 'mt-1 block w-full rounded-lg border border-stone-300 px-3 py-2 focus:border-brand-600 focus:outline-none focus:ring-2 focus:ring-brand-600/30';
@endphp
<div class="mx-auto max-w-2xl space-y-6 py-4">
    <h1 class="text-2xl font-bold">{{ __('Ajouter un établissement') }}</h1>

    @if ($submittedName)
        <div class="rounded-xl border border-emerald-200 bg-emerald-50 p-4" role="status">
            <p class="font-semibold text-emerald-900">{{ __('Merci ! « :name » a été envoyé.', ['name' => $submittedName]) }}</p>
            <p class="mt-1 text-sm text-emerald-900/80">{{ __('Notre équipe vérifie chaque fiche avant publication. Vous serez prévenu.') }}</p>
            <a href="{{ route('city.home', ['city' => $city]) }}" wire:navigate class="mt-3 inline-block text-sm font-semibold text-brand-700 underline">{{ __('Retour à l’accueil') }}</a>
        </div>
    @else
        <p class="text-stone-600">{{ __('Ajoutez gratuitement un établissement de :city. Il sera publié après vérification.', ['city' => $city->name]) }}</p>

        <form wire:submit="{{ $checked ? 'submit' : 'check' }}" class="space-y-5">
            <section class="space-y-3 rounded-xl border border-stone-200 bg-white p-4">
                <div>
                    <label for="name" class="text-sm font-medium">{{ __('Nom') }} *</label>
                    <input id="name" type="text" wire:model="name" maxlength="120" required class="{{ $input }}">
                    @error('name') <p class="mt-1 text-sm text-red-700">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="category" class="text-sm font-medium">{{ __('Catégorie principale') }} *</label>
                    <select id="category" wire:model="categoryId" required class="{{ $input }}">
                        <option value="">{{ __('Choisir…') }}</option>
                        @foreach ($categories as $category)
                            <option value="{{ $category->id }}">{{ $category->parent?->name }} › {{ $category->name }}</option>
                        @endforeach
                    </select>
                    @error('categoryId') <p class="mt-1 text-sm text-red-700">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="extra" class="text-sm font-medium">{{ __('Autres catégories (2 maximum)') }}</label>
                    <select id="extra" wire:model="extraCategoryIds" multiple size="4" class="{{ $input }}">
                        @foreach ($categories as $category)
                            <option value="{{ $category->id }}">{{ $category->parent?->name }} › {{ $category->name }}</option>
                        @endforeach
                    </select>
                    @error('extraCategoryIds') <p class="mt-1 text-sm text-red-700">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="description" class="text-sm font-medium">{{ __('Description') }}</label>
                    <textarea id="description" wire:model="description" rows="3" maxlength="2000" dir="auto" class="{{ $input }}"></textarea>
                </div>
            </section>

            <section class="space-y-3 rounded-xl border border-stone-200 bg-white p-4">
                <h2 class="font-semibold">{{ __('Adresse et position') }}</h2>
                <div class="grid gap-3 sm:grid-cols-2">
                    <div>
                        <label for="address" class="text-sm font-medium">{{ __('Adresse') }} *</label>
                        <input id="address" type="text" wire:model="address" maxlength="255" required class="{{ $input }}">
                        @error('address') <p class="mt-1 text-sm text-red-700">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label for="neighborhood" class="text-sm font-medium">{{ __('Quartier') }}</label>
                        <select id="neighborhood" wire:model="neighborhoodId" class="{{ $input }}">
                            <option value="">—</option>
                            @foreach ($neighborhoods as $neighborhood)
                                <option value="{{ $neighborhood->id }}">{{ $neighborhood->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <p class="text-sm text-stone-600">{{ __('Touchez la carte ou déplacez le repère à l’emplacement exact.') }}</p>
                <div wire:ignore x-data="locationPicker(@js([
                    'center' => ['lat' => $city->center_latitude, 'lng' => $city->center_longitude],
                    'lat' => $latitude, 'lng' => $longitude, 'zoom' => 14,
                    'tiles' => ['url' => config('directory.maps.tile_url'), 'attribution' => config('directory.maps.attribution'), 'maxZoom' => config('directory.maps.max_zoom')],
                ]))" class="overflow-hidden rounded-lg border border-stone-200">
                    <div x-ref="canvas" class="h-64 w-full"></div>
                </div>
                @error('latitude') <p class="text-sm text-red-700">{{ $message }}</p> @enderror
            </section>

            <section class="grid gap-3 rounded-xl border border-stone-200 bg-white p-4 sm:grid-cols-2">
                <div>
                    <label for="whatsapp" class="text-sm font-medium">WhatsApp</label>
                    <input id="whatsapp" type="tel" wire:model="whatsapp" dir="ltr" placeholder="06 12 34 56 78" class="{{ $input }}">
                    @error('whatsapp') <p class="mt-1 text-sm text-red-700">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="phone" class="text-sm font-medium">{{ __('Téléphone') }}</label>
                    <input id="phone" type="tel" wire:model="phone" dir="ltr" placeholder="05 22 12 34 56" class="{{ $input }}">
                    @error('phone') <p class="mt-1 text-sm text-red-700">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="website" class="text-sm font-medium">{{ __('Site web') }}</label>
                    <input id="website" type="url" wire:model="website" dir="ltr" class="{{ $input }}">
                    @error('website') <p class="mt-1 text-sm text-red-700">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="price" class="text-sm font-medium">{{ __('Prix') }}</label>
                    <select id="price" wire:model="priceRange" class="{{ $input }}">
                        <option value="">—</option>
                        @foreach ([1, 2, 3, 4] as $level)
                            <option value="{{ $level }}">{{ \App\Support\PriceRange::label($level) }}</option>
                        @endforeach
                    </select>
                </div>
            </section>

            <section class="space-y-3 rounded-xl border border-stone-200 bg-white p-4">
                <h2 class="font-semibold">{{ __('Horaires') }}</h2>
                <label class="flex items-center gap-2 text-sm">
                    <input type="checkbox" wire:model.live="alwaysOpen" class="rounded border-stone-300"> {{ __('Ouvert 24h/24, 7j/7') }}
                </label>
                @unless ($alwaysOpen)
                    <x-owner.hours-editor :week="$week" />
                @endunless
            </section>

            @if ($checked && $duplicates->isNotEmpty())
                <section class="space-y-2 rounded-xl border border-amber-300 bg-amber-50 p-4" role="alert">
                    <p class="font-semibold text-amber-900">{{ __('Cet établissement existe peut-être déjà :') }}</p>
                    <ul class="list-disc ps-5 text-sm">
                        @foreach ($duplicates as $duplicate)
                            <li>
                                @if ($duplicate->isPublished())
                                    <a href="{{ route('city.business', ['city' => $city, 'business' => $duplicate]) }}" target="_blank" class="underline">{{ $duplicate->name }}</a>
                                @else
                                    {{ $duplicate->name }} ({{ __('en cours de vérification') }})
                                @endif
                                · {{ $duplicate->categories->first()?->name }}
                            </li>
                        @endforeach
                    </ul>
                    <label class="flex items-center gap-2 text-sm">
                        <input type="checkbox" wire:model="confirmedNotDuplicate" class="rounded border-stone-300">
                        {{ __('Je confirme qu’il s’agit d’un autre établissement.') }}
                    </label>
                    @error('confirmedNotDuplicate') <p class="text-sm text-red-700">{{ $message }}</p> @enderror
                </section>
            @endif

            <button type="submit" wire:loading.attr="disabled" class="w-full rounded-lg bg-brand-600 px-4 py-3 font-semibold text-white hover:bg-brand-700 disabled:opacity-60">
                {{ $checked ? __('Envoyer pour vérification') : __('Continuer') }}
            </button>
        </form>
    @endif
</div>

@php
    use App\Support\Icons\Lucide;
    use App\Support\PriceRange;

    $stepLabels = [1 => __('Activité'), 2 => __('Contact'), 3 => __('Adresse'), 4 => __('Horaires'), 5 => __('Vérification')];
@endphp
<div class="mx-auto max-w-2xl space-y-4 py-2">
    @if ($submittedName)
        {{-- Done --}}
        <section class="fb-card p-8 text-center" role="status">
            <span class="mx-auto flex size-16 items-center justify-center rounded-full bg-online text-white"><x-ui.icon name="badge-check" size="34" /></span>
            <h1 class="mt-4 text-[24px] font-bold">{{ __('Merci ! « :name » a été envoyé.', ['name' => $submittedName]) }}</h1>
            <p class="mx-auto mt-2 max-w-md text-stone-600">{{ __('Notre équipe vérifie chaque fiche avant publication. Vous serez prévenu par notification dès qu’elle sera en ligne.') }}</p>
            <div class="mt-5 flex flex-wrap justify-center gap-2">
                <a href="{{ route('dashboard') }}" wire:navigate class="fb-btn fb-btn-primary">{{ __('Suivre mes demandes') }}</a>
                <a href="{{ route('city.home', ['city' => $city]) }}" wire:navigate class="fb-btn fb-btn-gray">{{ __('Retour à l’accueil') }}</a>
            </div>
        </section>
    @else
        {{-- Header + progress --}}
        <section class="fb-card p-4">
            <h1 class="text-[22px] font-bold">{{ __('Ajouter un établissement') }}</h1>
            <p class="text-[14px] text-stone-600">{{ __('Gratuit · publié à :city après vérification par notre équipe.', ['city' => $city->name]) }}</p>

            <ol class="mt-4 grid grid-cols-5 gap-1" aria-label="{{ __('Étapes') }}">
                @foreach ($stepLabels as $number => $label)
                    <li>
                        <button type="button" wire:click="goToStep({{ $number }})" @disabled($number > $maxStep)
                                @if ($number === $step) aria-current="step" @endif
                                class="group flex w-full flex-col items-start gap-1 text-start disabled:cursor-default">
                            <span @class([
                                'h-1.5 w-full rounded-full transition-colors',
                                'bg-brand-600' => $number <= $step,
                                'bg-brand-200' => $number > $step && $number <= $maxStep,
                                'bg-stone-200' => $number > $maxStep,
                            ])></span>
                            <span @class([
                                'text-[12px] font-semibold sm:text-[13px]',
                                'text-brand-600' => $number === $step,
                                'text-stone-900 group-hover:underline' => $number < $step || ($number > $step && $number <= $maxStep),
                                'text-stone-400' => $number > $maxStep,
                            ])>{{ $number }}. {{ $label }}</span>
                        </button>
                    </li>
                @endforeach
            </ol>
        </section>

        <form wire:submit="{{ $step === 5 ? 'submit' : 'next' }}" class="space-y-4">
            {{-- 1. Activity --}}
            @if ($step === 1)
                <section class="fb-card space-y-4 p-4" wire:key="step-1">
                    <div>
                        <label for="name" class="fb-label">{{ __('Nom de l’établissement') }} *</label>
                        <input id="name" type="text" wire:model="name" maxlength="120" required autofocus dir="auto"
                               placeholder="{{ __('Ex. Garage Atlas, Pharmacie Nakhla…') }}" class="fb-field mt-1">
                        @error('name') <p class="mt-1 text-[13px] text-red-700">{{ $message }}</p> @enderror
                    </div>

                    <div x-data="categoryPicker">
                        <p class="fb-label">{{ __('Activité principale') }} *</p>
                        @if ($category)
                            <p class="mt-1 flex items-center gap-2 rounded-lg bg-brand-50 p-2 text-brand-800">
                                <span class="flex size-8 items-center justify-center rounded-full bg-brand-600 text-white">{{ Lucide::svg($category->icon, 16) }}</span>
                                <span class="font-semibold">{{ $category->name }}</span>
                            </p>
                        @endif
                        <div class="relative mt-2">
                            <span class="pointer-events-none absolute inset-y-0 start-3 flex items-center text-stone-500"><x-ui.icon name="search" size="16" /></span>
                            <input type="search" x-model="q" placeholder="{{ __('Chercher une activité : taxi, avocat, école…') }}" class="fb-input h-10 ps-9" aria-label="{{ __('Chercher une activité') }}">
                        </div>
                        <div class="mt-2 max-h-80 space-y-3 overflow-y-auto rounded-lg border border-stone-200 p-2" role="radiogroup" aria-label="{{ __('Activité principale') }}">
                            @foreach ($groups as $group)
                                @php
                                    $groupText = implode(' ', array_filter($group->getTranslations('name'), 'is_string'));
                                    $texts = $group->children->mapWithKeys(fn ($child) => [$child->id => implode(' ', array_filter($child->getTranslations('name'), 'is_string')).' '.$groupText]);
                                @endphp
                                <div wire:key="pick-group-{{ $group->id }}" x-show="@js($texts->values()).some((text) => shows(text))">
                                    <p class="px-1 text-[12px] font-semibold uppercase tracking-wide text-stone-500">{{ $group->name }}</p>
                                    <div class="mt-1 flex flex-wrap gap-1.5">
                                        @foreach ($group->children as $child)
                                            @php $text = $texts[$child->id]; @endphp
                                            <label x-show="shows(@js($text))" wire:key="pick-{{ $child->id }}"
                                                   @class(['fb-chip cursor-pointer', 'fb-chip--active' => $categoryId === $child->id])>
                                                <input type="radio" wire:model.live="categoryId" value="{{ $child->id }}" class="sr-only">
                                                {{ Lucide::svg($child->icon, 14) }} {{ $child->name }}
                                            </label>
                                        @endforeach
                                    </div>
                                </div>
                            @endforeach
                        </div>
                        @error('categoryId') <p class="mt-1 text-[13px] text-red-700">{{ $message }}</p> @enderror
                    </div>

                    @if ($category)
                        <details class="rounded-lg border border-stone-200 p-3" @if ($extraCategoryIds !== []) open @endif>
                            <summary class="cursor-pointer font-semibold">{{ __('Autres activités (2 maximum, facultatif)') }}</summary>
                            <div class="mt-2 flex flex-wrap gap-1.5">
                                @foreach ($extraCategories as $extra)
                                    <button type="button" wire:click="toggleExtraCategory({{ $extra->id }})" class="fb-chip fb-chip--active" wire:key="extra-on-{{ $extra->id }}">
                                        {{ $extra->name }} <x-ui.icon name="x" size="12" />
                                    </button>
                                @endforeach
                            </div>
                            <div class="mt-2 flex flex-wrap gap-1.5">
                                @foreach ($groups->firstWhere('id', $category->parent_id)?->children ?? [] as $sibling)
                                    @continue($sibling->id === $category->id || in_array($sibling->id, $extraCategoryIds, true))
                                    <button type="button" wire:click="toggleExtraCategory({{ $sibling->id }})" class="fb-chip" wire:key="extra-{{ $sibling->id }}" @disabled(count($extraCategoryIds) >= 2)>
                                        + {{ $sibling->name }}
                                    </button>
                                @endforeach
                            </div>
                            @error('extraCategoryIds') <p class="mt-1 text-[13px] text-red-700">{{ $message }}</p> @enderror
                        </details>
                    @endif
                </section>
            @endif

            {{-- 2. Contact --}}
            @if ($step === 2)
                <section class="fb-card space-y-4 p-4" wire:key="step-2">
                    <p class="text-[14px] text-stone-600">{{ __('Comment les clients peuvent-ils vous joindre ? Indiquez au moins un numéro.') }}</p>
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <label for="whatsapp" class="fb-label">WhatsApp</label>
                            <input id="whatsapp" type="tel" wire:model="whatsapp" inputmode="tel" dir="ltr" placeholder="06 12 34 56 78" class="fb-field mt-1">
                            @error('whatsapp') <p class="mt-1 text-[13px] text-red-700">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label for="phone" class="fb-label">{{ __('Téléphone') }}</label>
                            <input id="phone" type="tel" wire:model="phone" inputmode="tel" dir="ltr" placeholder="05 24 41 23 45" class="fb-field mt-1">
                            @error('phone') <p class="mt-1 text-[13px] text-red-700">{{ $message }}</p> @enderror
                        </div>
                    </div>
                    <div>
                        <label for="website" class="fb-label">{{ __('Site web ou page Facebook / Instagram') }}</label>
                        <input id="website" type="url" wire:model="website" dir="ltr" placeholder="https://" class="fb-field mt-1">
                        @error('website') <p class="mt-1 text-[13px] text-red-700">{{ $message }}</p> @enderror
                    </div>
                </section>
            @endif

            {{-- 3. Address --}}
            @if ($step === 3)
                <section class="fb-card space-y-4 p-4" wire:key="step-3">
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <label for="address" class="fb-label">{{ __('Adresse') }} *</label>
                            <input id="address" type="text" wire:model="address" maxlength="255" required dir="auto" placeholder="{{ __('Ex. 12, avenue Mohammed V') }}" class="fb-field mt-1">
                            @error('address') <p class="mt-1 text-[13px] text-red-700">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label for="neighborhood" class="fb-label">{{ __('Quartier') }}</label>
                            <select id="neighborhood" wire:model="neighborhoodId" class="fb-field mt-1">
                                <option value="">—</option>
                                @foreach ($neighborhoods as $option)
                                    <option value="{{ $option->id }}">{{ $option->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div>
                        <p class="fb-label">{{ __('Position exacte') }} *</p>
                        <p class="text-[13px] text-stone-600">{{ __('Touchez la carte ou déplacez le repère à l’emplacement exact.') }}</p>
                        <div wire:ignore x-data="locationPicker(@js([
                            'center' => ['lat' => $city->center_latitude, 'lng' => $city->center_longitude],
                            'lat' => $latitude, 'lng' => $longitude, 'zoom' => 15,
                            'tiles' => ['url' => config('directory.maps.tile_url'), 'attribution' => config('directory.maps.attribution'), 'maxZoom' => config('directory.maps.max_zoom')],
                        ]))" class="mt-2 overflow-hidden rounded-lg border border-stone-300">
                            <div x-ref="canvas" class="h-72 w-full"></div>
                        </div>
                        @error('latitude') <p class="mt-1 text-[13px] text-red-700">{{ $message }}</p> @enderror
                    </div>
                </section>
            @endif

            {{-- 4. Hours and details --}}
            @if ($step === 4)
                <section class="fb-card space-y-4 p-4" wire:key="step-4">
                    <div>
                        <p class="fb-label">{{ __('Horaires') }}</p>
                        <label class="mt-1 flex items-center gap-2 text-[14px]">
                            <input type="checkbox" wire:model.live="alwaysOpen" class="rounded border-stone-300"> {{ __('Ouvert 24h/24, 7j/7') }}
                        </label>
                        @unless ($alwaysOpen)
                            <div class="mt-2"><x-owner.hours-editor :week="$week" /></div>
                        @endunless
                    </div>
                    <div>
                        <label for="description" class="fb-label">{{ __('Description') }}</label>
                        <textarea id="description" wire:model="description" rows="3" maxlength="2000" dir="auto"
                                  placeholder="{{ __('Services proposés, spécialités, langues parlées…') }}" class="fb-field mt-1"></textarea>
                    </div>
                    <div>
                        <label for="price" class="fb-label">{{ __('Gamme de prix') }}</label>
                        <select id="price" wire:model="priceRange" class="fb-field mt-1">
                            <option value="">—</option>
                            @foreach ([1, 2, 3, 4] as $level)
                                <option value="{{ $level }}">{{ PriceRange::label($level) }}</option>
                            @endforeach
                        </select>
                    </div>
                </section>
            @endif

            {{-- 5. Review --}}
            @if ($step === 5)
                <section class="fb-card divide-y divide-stone-200 p-0" wire:key="step-5">
                    @php
                        $rows = [
                            1 => [__('Activité'), $name.' · '.$category?->name.($extraCategories->isNotEmpty() ? ' + '.$extraCategories->pluck('name')->implode(', ') : '')],
                            2 => [__('Contact'), collect([$whatsapp ? 'WhatsApp '.$whatsapp : null, $phone ? __('Tél.').' '.$phone : null, $website ?: null])->filter()->implode(' · ')],
                            3 => [__('Adresse'), $address.($neighborhood ? ', '.$neighborhood->name : '')],
                            4 => [__('Horaires'), $alwaysOpen ? __('Ouvert 24h/24, 7j/7') : __('Horaires renseignés')],
                        ];
                    @endphp
                    @foreach ($rows as $number => [$label, $value])
                        <div class="flex items-start gap-3 p-4">
                            <div class="min-w-0 flex-1">
                                <p class="text-[13px] font-semibold text-stone-600">{{ $label }}</p>
                                <p class="text-[15px]" dir="auto">{{ $value }}</p>
                            </div>
                            <button type="button" wire:click="goToStep({{ $number }})" class="fb-btn fb-btn-gray min-h-8 px-3 text-[13px]">{{ __('Modifier') }}</button>
                        </div>
                    @endforeach
                </section>

                @if ($duplicates->isNotEmpty())
                    <section class="space-y-2 rounded-lg border-s-4 border-amber-400 bg-amber-50 p-4" role="alert">
                        <p class="font-semibold text-amber-900">{{ __('Cet établissement existe peut-être déjà :') }}</p>
                        <ul class="list-disc ps-5 text-[14px]">
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
                        <p class="text-[14px]">{{ __('Si c’est le vôtre, revendiquez la fiche existante plutôt que d’en créer une nouvelle.') }}</p>
                        <label class="flex items-center gap-2 text-[14px]">
                            <input type="checkbox" wire:model="confirmedNotDuplicate" class="rounded border-stone-300">
                            {{ __('Je confirme qu’il s’agit d’un autre établissement.') }}
                        </label>
                        @error('confirmedNotDuplicate') <p class="text-[13px] text-red-700">{{ $message }}</p> @enderror
                    </section>
                @endif

                <p class="px-1 text-[13px] text-stone-600">{{ __('En envoyant, vous confirmez que ces informations sont exactes. La fiche sera visible après vérification.') }}</p>
            @endif

            {{-- Errors of other steps (e.g. after "check"), shown here too. --}}
            @if ($step === 5 && $errors->any() && ! $errors->has('confirmedNotDuplicate'))
                <p class="rounded-lg bg-red-50 p-3 text-[14px] text-red-800" role="alert">{{ $errors->first() }}</p>
            @endif

            <div class="flex items-center justify-between gap-2">
                @if ($step > 1)
                    <button type="button" wire:click="previous" class="fb-btn fb-btn-gray">
                        <x-ui.icon name="arrow-left" size="16" class="rtl:rotate-180" /> {{ __('Retour') }}
                    </button>
                @else
                    <span></span>
                @endif
                <button type="submit" wire:loading.attr="disabled" class="fb-btn fb-btn-primary px-6">
                    <span wire:loading.remove>{{ $step === 5 ? __('Envoyer pour vérification') : __('Continuer') }}</span>
                    <span wire:loading>{{ __('Un instant…') }}</span>
                </button>
            </div>
        </form>
    @endif
</div>

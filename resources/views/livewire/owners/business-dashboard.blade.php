@php
    $input = 'mt-1 block w-full rounded-lg border border-stone-300 px-3 py-2 focus:border-brand-600 focus:outline-none focus:ring-2 focus:ring-brand-600/30';
    $tabs = [
        'overview' => __('Aperçu'),
        'info' => __('Informations'),
        'hours' => __('Horaires'),
        'photos' => __('Photos'),
        'reviews' => __('Avis'),
        'stats' => __('Statistiques'),
    ];
    $checklistLabels = [
        'description' => __('Description (40 caractères minimum)'),
        'phone' => __('Téléphone'),
        'whatsapp' => 'WhatsApp',
        'hours' => __('Horaires'),
        'photos' => __('3 photos ou plus'),
        'address' => __('Adresse'),
        'neighborhood' => __('Quartier'),
        'price_range' => __('Gamme de prix'),
        'website_or_social' => __('Site web ou réseau social'),
        'logo' => __('Logo'),
    ];
    $typeLabels = collect(\App\Enums\ContactEventType::cases())->mapWithKeys(fn ($t) => [$t->value => $t->getLabel()]);
@endphp
<div class="space-y-4 py-4">
    <div class="flex flex-wrap items-center justify-between gap-2">
        <div>
            <a href="{{ route('dashboard') }}" wire:navigate class="text-sm text-brand-700 underline">← {{ __('Mon espace professionnel') }}</a>
            <h1 class="text-2xl font-bold">{{ $business->name }}</h1>
            <p class="text-sm text-stone-600">
                {{ __('Formule : :plan', ['plan' => $plan?->name ?? __('Gratuit')]) }}
                @if ($business->plan_expires_at?->isFuture()) · {{ __('jusqu’au :date', ['date' => $business->plan_expires_at->format('d/m/Y')]) }} @endif
            </p>
        </div>
        <div class="flex gap-2">
            <a href="{{ route('dashboard.billing', ['business' => $business->id]) }}" wire:navigate class="rounded-lg bg-accent-500 px-3 py-2 text-sm font-semibold text-stone-900">{{ __('Formules et paiements') }}</a>
            @if ($business->isPublished())
                <a href="{{ route('city.business', ['city' => $business->city, 'business' => $business]) }}" target="_blank" class="rounded-lg border border-stone-300 px-3 py-2 text-sm">{{ __('Voir ma fiche') }}</a>
            @endif
        </div>
    </div>

    <nav class="-mx-4 flex gap-1 overflow-x-auto px-4 text-sm" aria-label="{{ __('Sections') }}">
        @foreach ($tabs as $key => $label)
            <button type="button" wire:click="setTab('{{ $key }}')" aria-current="{{ $tab === $key ? 'page' : 'false' }}"
                    @class(['whitespace-nowrap rounded-full px-3 py-1.5', 'bg-brand-600 text-white' => $tab === $key, 'bg-white border border-stone-300' => $tab !== $key])>
                {{ $label }}
            </button>
        @endforeach
    </nav>

    @if ($flash)
        <p class="rounded-lg bg-emerald-50 p-3 text-sm text-emerald-800" role="status">{{ $flash }}</p>
    @endif

    @if ($tab === 'overview')
        <section class="space-y-3 rounded-xl border border-stone-200 bg-white p-4">
            <h2 class="font-semibold">{{ __('Fiche complétée à :percent %', ['percent' => $business->profile_completeness]) }}</h2>
            <div class="h-2 overflow-hidden rounded-full bg-stone-200">
                <span class="block h-full bg-brand-500" style="width: {{ (int) $business->profile_completeness }}%"></span>
            </div>
            <p class="text-sm text-stone-600">{{ __('Une fiche complète aide les clients à vous choisir.') }}</p>
            <ul class="grid gap-1 text-sm sm:grid-cols-2">
                @foreach ($checklist as $item => $done)
                    <li @class(['text-emerald-700' => $done, 'text-stone-600' => ! $done])>{{ $done ? '✓' : '○' }} {{ $checklistLabels[$item] }}</li>
                @endforeach
            </ul>
            <p class="text-sm">
                {{ __('Nom, adresse, position ou numéros à changer ?') }}
                <a href="{{ route('city.business.suggest', ['city' => $business->city, 'business' => $business]) }}" class="text-brand-700 underline">{{ __('Demander une modification') }}</a>
            </p>
        </section>
    @endif

    @if ($tab === 'info')
        <form wire:submit="saveInfo" class="space-y-3 rounded-xl border border-stone-200 bg-white p-4">
            @foreach (['fr' => 'Français', 'ar' => 'العربية', 'en' => 'English'] as $locale => $label)
                <div>
                    <label for="description-{{ $locale }}" class="text-sm font-medium">{{ __('Description') }} · {{ $label }}</label>
                    <textarea id="description-{{ $locale }}" wire:model="description.{{ $locale }}" rows="3" maxlength="2000" lang="{{ $locale }}" dir="{{ $locale === 'ar' ? 'rtl' : 'ltr' }}" class="{{ $input }}"></textarea>
                    @error("description.{$locale}") <p class="mt-1 text-sm text-red-700">{{ $message }}</p> @enderror
                </div>
            @endforeach
            <div class="grid gap-3 sm:grid-cols-2">
                <div>
                    <label for="email" class="text-sm font-medium">{{ __('E-mail') }}</label>
                    <input id="email" type="email" wire:model="email" dir="ltr" class="{{ $input }}">
                    @error('email') <p class="mt-1 text-sm text-red-700">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="website" class="text-sm font-medium">{{ __('Site web') }}</label>
                    <input id="website" type="url" wire:model="website" dir="ltr" class="{{ $input }}">
                    @error('website') <p class="mt-1 text-sm text-red-700">{{ $message }}</p> @enderror
                </div>
                @foreach (\App\Models\Business::SOCIAL_NETWORKS as $network)
                    <div>
                        <label for="social-{{ $network }}" class="text-sm font-medium">{{ ucfirst($network) }}</label>
                        <input id="social-{{ $network }}" type="url" wire:model="social.{{ $network }}" dir="ltr" class="{{ $input }}">
                        @error("social.{$network}") <p class="mt-1 text-sm text-red-700">{{ $message }}</p> @enderror
                    </div>
                @endforeach
                <div>
                    <label for="price" class="text-sm font-medium">{{ __('Prix') }}</label>
                    <select id="price" wire:model="priceRange" class="{{ $input }}">
                        <option value="">—</option>
                        @foreach ([1, 2, 3, 4] as $level)
                            <option value="{{ $level }}">{{ \App\Support\PriceRange::label($level) }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <button type="submit" class="rounded-lg bg-brand-600 px-4 py-2 font-semibold text-white">{{ __('Enregistrer') }}</button>
        </form>
    @endif

    @if ($tab === 'hours')
        <form wire:submit="saveHours" class="space-y-3 rounded-xl border border-stone-200 bg-white p-4">
            <label class="flex items-center gap-2 text-sm">
                <input type="checkbox" wire:model.live="alwaysOpen" class="rounded border-stone-300"> {{ __('Ouvert 24h/24, 7j/7') }}
            </label>
            @unless ($alwaysOpen)
                <x-owner.hours-editor :week="$week" />
            @endunless
            <button type="submit" class="rounded-lg bg-brand-600 px-4 py-2 font-semibold text-white">{{ __('Enregistrer les horaires') }}</button>
        </form>

        <section class="space-y-3 rounded-xl border border-stone-200 bg-white p-4">
            <h2 class="font-semibold">{{ __('Horaires exceptionnels (Ramadan, Aïd, jours fériés)') }}</h2>
            <ul class="space-y-1 text-sm">
                @forelse ($business->specialHours->where('ends_on', '>=', now()->startOfDay()) as $period)
                    <li class="flex items-center justify-between gap-2" wire:key="special-{{ $period->id }}">
                        <span>
                            {{ $period->label ?: __('Période spéciale') }} ·
                            <span dir="ltr">{{ $period->starts_on->format('d/m') }}–{{ $period->ends_on->format('d/m') }}</span> ·
                            {{ $period->is_closed ? __('Fermé') : substr((string) $period->opens_at, 0, 5).'–'.substr((string) $period->closes_at, 0, 5) }}
                        </span>
                        <button type="button" wire:click="removeSpecialPeriod({{ $period->id }})" wire:confirm="{{ __('Supprimer cette période ?') }}" class="text-rose-700">{{ __('Supprimer') }}</button>
                    </li>
                @empty
                    <li class="text-stone-500">{{ __('Aucune période à venir.') }}</li>
                @endforelse
            </ul>
            <form wire:submit="addSpecialPeriod" class="grid gap-2 sm:grid-cols-3">
                <input type="text" wire:model="special.label" maxlength="80" placeholder="{{ __('Libellé (ex. Ramadan)') }}" class="{{ $input }} sm:col-span-3">
                <label class="text-sm">{{ __('Du') }} <input type="date" wire:model="special.starts_on" class="{{ $input }}"></label>
                <label class="text-sm">{{ __('Au') }} <input type="date" wire:model="special.ends_on" class="{{ $input }}"></label>
                <label class="flex items-center gap-2 self-end text-sm"><input type="checkbox" wire:model.live="special.is_closed" class="rounded border-stone-300"> {{ __('Fermé') }}</label>
                @unless ($special['is_closed'])
                    <label class="text-sm">{{ __('Ouverture') }} <input type="time" wire:model="special.opens_at" class="{{ $input }}"></label>
                    <label class="text-sm">{{ __('Fermeture') }} <input type="time" wire:model="special.closes_at" class="{{ $input }}"></label>
                @endunless
                @error('special.*') <p class="text-sm text-red-700 sm:col-span-3">{{ $message }}</p> @enderror
                <button type="submit" class="rounded-lg border border-brand-600 px-4 py-2 font-semibold text-brand-700 sm:col-span-3">{{ __('Ajouter la période') }}</button>
            </form>
        </section>
    @endif

    @if ($tab === 'photos')
        @php $gallery = $business->getMedia('gallery'); @endphp
        <section class="space-y-3 rounded-xl border border-stone-200 bg-white p-4">
            <h2 class="font-semibold">{{ __('Photos (:count / :max)', ['count' => $gallery->count(), 'max' => $maxPhotos]) }}</h2>
            @if ($gallery->count() > $maxPhotos)
                <p class="text-sm text-amber-800">{{ __('Votre formule affiche :max photos. Les autres sont conservées mais masquées.', ['max' => $maxPhotos]) }}</p>
            @endif
            <div class="flex flex-wrap gap-2">
                @foreach ($gallery as $index => $photo)
                    <div class="relative" wire:key="photo-{{ $photo->id }}">
                        <img src="{{ $photo->getUrl('thumb') }}" alt="" width="96" height="72" loading="lazy" @class(['h-18 w-24 rounded-lg object-cover', 'opacity-40' => $index >= $maxPhotos])>
                        <button type="button" wire:click="removePhoto({{ $photo->id }})" wire:confirm="{{ __('Supprimer cette photo ?') }}" class="absolute -end-1 -top-1 rounded-full bg-white p-0.5 shadow" aria-label="{{ __('Supprimer') }}">
                            <x-ui.icon name="x" size="14" />
                        </button>
                    </div>
                @endforeach
            </div>
            @if ($gallery->count() < $maxPhotos)
                <form wire:submit="uploadPhotos" class="space-y-2">
                    <input type="file" wire:model="photos" multiple accept="image/jpeg,image/png,image/webp" class="block text-sm">
                    @error('photos') <p class="text-sm text-red-700">{{ $message }}</p> @enderror
                    @error('photos.*') <p class="text-sm text-red-700">{{ $message }}</p> @enderror
                    <button type="submit" wire:loading.attr="disabled" class="rounded-lg bg-brand-600 px-4 py-2 font-semibold text-white">{{ __('Ajouter') }}</button>
                </form>
            @endif
        </section>

        <section class="space-y-2 rounded-xl border border-stone-200 bg-white p-4">
            <h2 class="font-semibold">{{ __('Logo') }}</h2>
            @if ($logoAllowed)
                @if ($business->getFirstMedia('logo'))
                    <img src="{{ $business->getFirstMediaUrl('logo', 'thumb') }}" alt="" width="80" height="80" loading="lazy" class="size-20 rounded-lg object-cover">
                @endif
                <form wire:submit="uploadLogo" class="space-y-2">
                    <input type="file" wire:model="logo" accept="image/jpeg,image/png,image/webp" class="block text-sm">
                    @error('logo') <p class="text-sm text-red-700">{{ $message }}</p> @enderror
                    <button type="submit" class="rounded-lg border border-brand-600 px-4 py-2 font-semibold text-brand-700">{{ __('Enregistrer le logo') }}</button>
                </form>
            @else
                <p class="text-sm text-stone-600">{{ __('Le logo et la photo de couverture font partie des formules payantes.') }}</p>
            @endif
        </section>
    @endif

    @if ($tab === 'reviews' && $reviews)
        <section class="space-y-3">
            <p class="text-sm text-stone-600">{{ __('Vous pouvez répondre publiquement à chaque avis, une fois. Les avis ne peuvent pas être supprimés : signalez ceux qui ne respectent pas les règles.') }}</p>
            @forelse ($reviews as $review)
                <article class="rounded-xl border border-stone-200 bg-white p-4" wire:key="dash-review-{{ $review->id }}">
                    <div class="flex items-center justify-between">
                        <p class="font-medium">{{ $review->authorName() }}</p>
                        <x-directory.stars :rating="$review->rating" size="14" />
                    </div>
                    @if ($review->body)
                        <p class="mt-2 text-stone-700" dir="auto">{!! nl2br(e($review->body)) !!}</p>
                    @endif
                    @if ($review->reply)
                        <p class="ms-4 mt-2 border-s-2 border-brand-200 ps-3 text-sm" dir="auto">{!! nl2br(e($review->reply->body)) !!}</p>
                    @endif
                    <form wire:submit="saveReply({{ $review->id }})" class="mt-3 space-y-2">
                        <textarea wire:model="replyBodies.{{ $review->id }}" rows="2" maxlength="{{ \App\Actions\Reviews\ReplyToReview::MAX_LENGTH }}" dir="auto"
                                  placeholder="{{ $review->reply ? __('Modifier votre réponse') : __('Répondre publiquement') }}" class="{{ $input }}"></textarea>
                        @error('replyBodies.'.$review->id) <p class="text-sm text-red-700">{{ $message }}</p> @enderror
                        <div class="flex gap-3">
                            <button type="submit" class="rounded-lg bg-brand-600 px-3 py-1.5 text-sm font-semibold text-white">{{ __('Envoyer') }}</button>
                            <a href="{{ route('city.business', ['city' => $business->city, 'business' => $business]) }}#avis" class="text-sm text-stone-600 underline">{{ __('Signaler depuis la fiche') }}</a>
                        </div>
                    </form>
                </article>
            @empty
                <p class="text-stone-600">{{ __('Pas encore d’avis.') }}</p>
            @endforelse
            {{ $reviews->links() }}
        </section>
    @endif

    @if ($tab === 'stats')
        @if (! $canSeeStats)
            <section class="rounded-xl border border-dashed border-brand-300 bg-brand-50 p-4">
                <p class="font-semibold text-brand-900">{{ __('Les statistiques font partie des formules payantes.') }}</p>
                <p class="mt-1 text-sm text-brand-900/80">{{ __('Vues de la fiche, clics WhatsApp, appels et itinéraires, jour par jour.') }}</p>
            </section>
        @elseif ($stats)
            <div class="flex gap-2 text-sm">
                @foreach (array_keys(\App\Services\Owners\OwnerStatistics::PERIODS) as $key)
                    <button type="button" wire:click="$set('period', '{{ $key }}')" @class(['rounded-full px-3 py-1', 'bg-brand-600 text-white' => $period === $key, 'border border-stone-300 bg-white' => $period !== $key])>
                        {{ __(':days jours', ['days' => \App\Services\Owners\OwnerStatistics::PERIODS[$key]]) }}
                    </button>
                @endforeach
            </div>
            <div class="grid grid-cols-2 gap-2 sm:grid-cols-5">
                @foreach ($stats['totals'] as $type => $total)
                    <div class="rounded-xl border border-stone-200 bg-white p-3">
                        <p class="text-2xl font-bold">{{ $total }}</p>
                        <p class="text-xs text-stone-600">{{ $typeLabels[$type] }}</p>
                    </div>
                @endforeach
            </div>
            <section class="rounded-xl border border-stone-200 bg-white p-4">
                <h2 class="font-semibold">{{ __('Vues de la fiche par jour') }}</h2>
                <x-owner.bar-chart :series="collect($stats['daily'])->map(fn ($day) => $day['profile_view'])->all()" :label="__('Vues de la fiche par jour')" class="mt-2" />
            </section>
            <section class="rounded-xl border border-stone-200 bg-white p-4">
                <h2 class="font-semibold">{{ __('Contacts par jour (WhatsApp, appels, itinéraires, site)') }}</h2>
                <x-owner.bar-chart :series="collect($stats['daily'])->map(fn ($day) => $day['whatsapp_click'] + $day['call_click'] + $day['directions_click'] + $day['website_click'])->all()" :label="__('Contacts par jour')" class="mt-2" />
            </section>
            <section class="rounded-xl border border-stone-200 bg-white p-4 text-sm">
                <h2 class="font-semibold">{{ __('Provenance') }}</h2>
                <ul class="mt-2 grid grid-cols-2 gap-1">
                    @foreach ($stats['by_source'] as $source => $count)
                        <li>{{ \App\Enums\ContactEventSource::from($source)->getLabel() }} : <strong>{{ $count }}</strong></li>
                    @endforeach
                </ul>
            </section>
        @endif
    @endif
</div>

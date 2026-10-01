@php
    use App\Services\Hours\OpeningHours;
    use App\Support\Phone\MoroccanPhone;
    use App\Support\PriceRange;
    use App\Support\Security\SafeUrl;

    $website = SafeUrl::normalize($business->website);
    $lat = $business->latitude;
    $lng = $business->longitude;
    $monday = now()->startOfWeek(\Carbon\CarbonInterface::MONDAY)->locale(app()->getLocale());
    $today = now($hours->timezone())->isoWeekday();
    $track = fn (string $type) => "\$track({$business->id}, '{$type}', '{$source}', ".($placementId ?? 'null').')';
    $totalReviews = array_sum($distribution);
    $cover = $business->getFirstMediaUrl('cover', 'large') ?: null;
    $logo = $business->getFirstMediaUrl('logo', 'thumb') ?: null;
@endphp
<div class="mx-auto max-w-[1100px] space-y-4">
    {{-- Page header: cover, profile picture, name, actions, tabs. --}}
    <section class="fb-card overflow-hidden rounded-none sm:rounded-lg">
        <div class="relative h-40 sm:h-60 lg:h-72">
            @if ($cover)
                <img src="{{ $cover }}" alt="" class="size-full object-cover" fetchpriority="high">
            @else
                <div class="flex size-full items-end justify-end overflow-hidden bg-gradient-to-br from-brand-500 via-brand-600 to-brand-900 p-6 text-white/15" aria-hidden="true">
                    <x-ui.category-icon :icon="$primaryCategory?->icon" size="220" class="-mb-16 -me-6 shrink-0" />
                </div>
            @endif
        </div>

        <div class="px-4 sm:px-8">
            <div class="flex flex-col items-center gap-3 pb-4 sm:flex-row sm:items-end sm:gap-5">
                <div class="-mt-[72px] rounded-full bg-white p-1 sm:-mt-[52px]">
                    <x-ui.avatar :name="$business->name" :src="$logo" size="160" :open="$status?->isOpen" />
                </div>

                <div class="min-w-0 flex-1 text-center sm:pb-2 sm:text-start">
                    <nav aria-label="{{ __('Fil d’Ariane') }}" class="text-[13px] text-stone-600">
                        <ol class="flex flex-wrap items-center justify-center gap-1 sm:justify-start">
                            <li><a href="{{ route('city.home', ['city' => $city]) }}" wire:navigate class="hover:underline">{{ $city->name }}</a></li>
                            @if ($primaryCategory)
                                <li aria-hidden="true">›</li>
                                <li><a href="{{ route('city.category', ['city' => $city, 'category' => $primaryCategory]) }}" wire:navigate class="hover:underline">{{ $primaryCategory->name }}</a></li>
                            @endif
                        </ol>
                    </nav>
                    <h1 class="mt-0.5 text-[28px] font-bold leading-8 sm:text-[32px] sm:leading-9">
                        {{ $business->name }}
                        @if ($business->is_verified)
                            <span class="inline-flex align-middle text-brand-600" title="{{ __('Vérifié') }}">
                                <x-ui.icon name="badge-check" size="24" class="fill-brand-600 stroke-white" />
                                <span class="sr-only">{{ __('Vérifié') }}</span>
                            </span>
                        @endif
                    </h1>
                    <p class="mt-1 flex flex-wrap items-center justify-center gap-x-2 gap-y-1 text-[15px] text-stone-600 sm:justify-start">
                        @if ($business->rating_count > 0)
                            <a href="#avis" class="inline-flex items-center gap-1 font-semibold text-stone-900 hover:underline">
                                <x-directory.stars :rating="$business->rating_avg" size="15" />
                                {{ number_format($business->rating_avg, 1, ',', ' ') }}
                                <span class="font-normal text-stone-600">({{ trans_choice(':count avis|:count avis', $business->rating_count, ['count' => $business->rating_count]) }})</span>
                            </a>
                            <span aria-hidden="true">·</span>
                        @endif
                        <span>{{ $business->categories->pluck('name')->implode(' · ') }}</span>
                        @if ($business->neighborhood) <span aria-hidden="true">·</span> <span>{{ $business->neighborhood->name }}</span> @endif
                        @if (PriceRange::label($business->price_range)) <span aria-hidden="true">·</span> <span>{{ PriceRange::label($business->price_range) }}</span> @endif
                    </p>
                    <x-directory.open-badge :status="$status" class="mt-1 text-[15px]" />
                </div>

                {{-- Contact actions: WhatsApp is the main button, it is how Morocco reaches a business. --}}
                <div class="flex w-full flex-wrap justify-center gap-2 sm:w-auto sm:pb-3" aria-label="{{ __('Contacter') }}">
                    @if ($whatsappUrl)
                        <a href="{{ $whatsappUrl }}" target="_blank" rel="noopener noreferrer" x-data x-on:click="{{ $track('whatsapp_click') }}"
                           class="fb-btn fb-btn-primary flex-1 sm:flex-none">
                            <x-ui.icon name="message-circle" size="18" /> WhatsApp
                        </a>
                    @endif
                    @if ($business->phone)
                        <a href="tel:{{ $business->phone }}" x-data x-on:click="{{ $track('call_click') }}" class="fb-btn fb-btn-gray flex-1 sm:flex-none">
                            <x-ui.icon name="phone" size="18" /> {{ __('Appeler') }}
                        </a>
                    @endif
                    <a href="https://www.google.com/maps/dir/?api=1&amp;destination={{ $lat }},{{ $lng }}" target="_blank" rel="noopener noreferrer"
                       x-data x-on:click="{{ $track('directions_click') }}" class="fb-btn fb-btn-gray flex-1 sm:flex-none">
                        <x-ui.icon name="navigation" size="18" /> {{ __('Itinéraire') }}
                    </a>
                    <button type="button" x-data x-on:click="$share(@js($business->name), @js(route('city.business', ['city' => $city, 'business' => $business])), @js(__('Lien copié.')))"
                            class="fb-btn fb-btn-gray px-3" aria-label="{{ __('Partager') }}" title="{{ __('Partager') }}">
                        <x-ui.icon name="share" size="18" />
                    </button>
                </div>
            </div>

            <div class="fb-divider"></div>
            <nav class="-mx-2 flex overflow-x-auto" aria-label="{{ __('Sections de la page') }}">
                <a href="#avis" class="fb-tab fb-tab--active">{{ __('Avis') }}</a>
                <a href="#apropos" class="fb-tab">{{ __('À propos') }}</a>
                <a href="#horaires" class="fb-tab">{{ __('Horaires') }}</a>
                @if ($gallery->isNotEmpty())
                    <a href="#photos" class="fb-tab">{{ __('Photos') }}</a>
                @endif
            </nav>
        </div>
    </section>

    <div class="grid gap-4 lg:grid-cols-[minmax(0,2fr)_minmax(0,3fr)]">
        {{-- Left column: Intro, hours, map, photos. --}}
        <div class="space-y-4">
            <section id="apropos" aria-labelledby="about-title" class="fb-card scroll-mt-20 p-4">
                <h2 id="about-title" class="text-[20px] font-bold">{{ __('Intro') }}</h2>
                @if ($business->description)
                    {{-- User-written text: escaped, then line breaks restored. --}}
                    <p class="mt-3 text-center text-[15px]" dir="auto">{!! nl2br(e($business->description)) !!}</p>
                    <div class="fb-divider my-4"></div>
                @endif

                <ul class="mt-3 space-y-3 text-[15px]">
                    <li class="flex items-start gap-3">
                        <x-ui.icon name="info" size="20" class="mt-px shrink-0 text-stone-500" />
                        <span><span class="text-stone-600">{{ __('Page') }}</span> · {{ $business->categories->pluck('name')->implode(', ') }}</span>
                    </li>
                    @if ($business->address)
                        <li class="flex items-start gap-3">
                            <x-ui.icon name="map-pin" size="20" class="mt-px shrink-0 text-stone-500" />
                            <span>{{ $business->address }}@if ($business->neighborhood), {{ $business->neighborhood->name }}@endif</span>
                        </li>
                    @endif
                    @if ($business->phone)
                        <li class="flex items-start gap-3">
                            <x-ui.icon name="phone" size="20" class="mt-px shrink-0 text-stone-500" />
                            <a href="tel:{{ $business->phone }}" dir="ltr" class="hover:underline" x-data x-on:click="{{ $track('call_click') }}">{{ MoroccanPhone::formatNational($business->phone) }}</a>
                        </li>
                    @endif
                    @if ($website)
                        <li class="flex items-start gap-3">
                            <x-ui.icon name="globe" size="20" class="mt-px shrink-0 text-stone-500" />
                            <a href="{{ $website }}" target="_blank" rel="noopener noreferrer nofollow ugc" x-data x-on:click="{{ $track('website_click') }}" class="truncate font-semibold text-brand-600 hover:underline">{{ parse_url($website, PHP_URL_HOST) }}</a>
                        </li>
                    @endif
                    @foreach ($business->social_links ?? [] as $network => $url)
                        @if (SafeUrl::isSafe($url))
                            <li class="flex items-start gap-3">
                                <x-ui.icon name="share" size="20" class="mt-px shrink-0 text-stone-500" />
                                <a href="{{ $url }}" target="_blank" rel="noopener noreferrer nofollow ugc" class="font-semibold text-brand-600 hover:underline">{{ ucfirst($network) }}</a>
                            </li>
                        @endif
                    @endforeach
                    @if ($business->rating_count > 0)
                        <li class="flex items-start gap-3">
                            <x-ui.icon name="star" size="20" class="mt-px shrink-0 text-stone-500" />
                            <span>{{ __('Noté :rating sur 5', ['rating' => number_format($business->rating_avg, 1, ',', ' ')]) }} ({{ trans_choice(':count avis|:count avis', $business->rating_count, ['count' => $business->rating_count]) }})</span>
                        </li>
                    @endif
                </ul>
            </section>

            <section id="horaires" aria-labelledby="hours-title" class="fb-card scroll-mt-20 p-4">
                <h2 id="hours-title" class="text-[20px] font-bold">{{ __('Horaires') }}</h2>
                <x-directory.open-badge :status="$status" class="mt-1" />
                @if ($business->is_always_open)
                    <p class="mt-2">{{ __('Ouvert 24h/24, 7j/7') }}</p>
                @else
                    <table class="mt-2 w-full text-[15px]">
                        <tbody>
                            @foreach ($hours->weeklySchedule() as $day => $slots)
                                <tr @class(['font-semibold' => $day === $today])>
                                    <th scope="row" class="py-1 text-start font-normal">
                                        {{ mb_convert_case($monday->copy()->addDays($day - 1)->dayName, MB_CASE_TITLE) }}
                                        @if ($day === $today) <span class="ms-1 rounded bg-brand-50 px-1.5 py-0.5 text-[11px] font-semibold text-brand-600">{{ __('Aujourd’hui') }}</span> @endif
                                    </th>
                                    <td class="py-1 text-end" dir="ltr">
                                        @forelse ($slots as [$opens, $closes])
                                            <span class="block">{{ $opens }}–{{ $closes }}</span>
                                        @empty
                                            <span class="text-stone-600">{{ __('Fermé') }}</span>
                                        @endforelse
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @endif

                @php $special = $hours->upcomingSpecialPeriods(); @endphp
                @if ($special->isNotEmpty())
                    <div class="mt-3 rounded-lg bg-stone-100 p-3 text-[14px]">
                        <p class="font-semibold">{{ __('Horaires exceptionnels') }}</p>
                        <ul class="mt-1 space-y-1">
                            @foreach ($special as $period)
                                <li>
                                    {{ $period->label ?: __('Période spéciale') }} ·
                                    <span dir="ltr">{{ $period->starts_on->format('d/m') }}@if (! $period->starts_on->equalTo($period->ends_on))–{{ $period->ends_on->format('d/m') }}@endif</span> :
                                    @if ($period->is_closed)
                                        {{ __('Fermé') }}
                                    @else
                                        <span dir="ltr">{{ substr((string) $period->opens_at, 0, 5) }}–{{ substr((string) $period->closes_at, 0, 5) }}</span>
                                    @endif
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endif
            </section>

            <section aria-labelledby="address-title" class="fb-card overflow-hidden">
                <div class="flex items-center justify-between gap-2 p-4 pb-3">
                    <h2 id="address-title" class="text-[20px] font-bold">{{ __('Adresse') }}</h2>
                    <div class="flex gap-1">
                        <a href="https://waze.com/ul?ll={{ $lat }},{{ $lng }}&amp;navigate=yes" target="_blank" rel="noopener noreferrer" x-data x-on:click="{{ $track('directions_click') }}" class="fb-btn fb-btn-gray min-h-8 px-3 text-[13px]">Waze</a>
                        <a href="https://www.google.com/maps/search/?api=1&amp;query={{ $lat }},{{ $lng }}" target="_blank" rel="noopener noreferrer" class="fb-btn fb-btn-gray min-h-8 px-3 text-[13px]">Google Maps</a>
                    </div>
                </div>
                <div wire:ignore
                     x-data="directoryMap(@js([
                         'center' => ['lat' => $lat, 'lng' => $lng],
                         'zoom' => 16,
                         'tiles' => ['url' => config('directory.maps.tile_url'), 'attribution' => config('directory.maps.attribution'), 'maxZoom' => config('directory.maps.max_zoom')],
                         'labels' => ['sponsored' => __('Sponsorisé')],
                     ]))"
                     data-markers="{{ json_encode([['name' => $business->name, 'lat' => $lat, 'lng' => $lng, 'url' => route('city.business', ['city' => $city, 'business' => $business]), 'sponsored' => false, 'status' => null]], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE) }}">
                    <div x-ref="canvas" class="h-52 w-full"></div>
                </div>
            </section>

            @if ($gallery->isNotEmpty())
                <section id="photos" aria-labelledby="photos-title" class="fb-card scroll-mt-20 p-4">
                    <h2 id="photos-title" class="text-[20px] font-bold">{{ __('Photos') }}</h2>
                    <div class="mt-3 grid grid-cols-3 gap-1 overflow-hidden rounded-lg">
                        @foreach ($gallery as $photo)
                            <a href="{{ $photo->getUrl('large') }}" target="_blank" rel="noopener" wire:key="photo-{{ $photo->id }}" class="block aspect-square">
                                <img src="{{ $photo->getUrl('thumb') }}" alt="{{ __('Photo de :business', ['business' => $business->name]) }}"
                                     width="320" height="320" loading="lazy" decoding="async" class="size-full object-cover hover:opacity-90">
                            </a>
                        @endforeach
                    </div>
                </section>
            @endif

            @unless ($business->is_claimed)
                <section class="fb-card p-4">
                    <div class="flex items-start gap-3">
                        <span class="flex size-10 shrink-0 items-center justify-center rounded-full bg-brand-50 text-brand-600"><x-ui.icon name="store" size="20" /></span>
                        <div>
                            <p class="font-semibold">{{ __('C’est votre établissement ?') }}</p>
                            <p class="mt-0.5 text-[14px] text-stone-600">{{ __('Revendiquez cette fiche pour répondre aux avis et mettre à jour vos informations.') }}</p>
                        </div>
                    </div>
                    <a href="{{ route('city.business.claim', ['city' => $city, 'business' => $business]) }}" class="fb-btn fb-btn-soft mt-3 w-full">{{ __('Revendiquer la fiche') }}</a>
                </section>
            @endunless

            <p class="flex flex-wrap gap-x-3 px-2 text-[13px] text-stone-600">
                <button type="button" wire:click="openReport('business', {{ $business->id }})" class="inline-flex items-center gap-1 hover:underline">
                    <x-ui.icon name="flag" size="13" /> {{ __('Signaler cette fiche') }}
                </button>
                <a href="{{ route('city.business.suggest', ['city' => $city, 'business' => $business]) }}" class="hover:underline">{{ __('Proposer une modification') }}</a>
            </p>
        </div>

        {{-- Right column: reviews, posted like a feed. --}}
        <section id="avis" aria-labelledby="reviews-title" class="min-w-0 scroll-mt-20 space-y-4">
            <h2 id="reviews-title" class="sr-only">{{ __('Avis') }}</h2>

            <livewire:reviews.review-form :business="$business" :key="'review-form-'.$business->id" />

            @if ($reportFlash)
                <p class="fb-card bg-brand-50 p-3 text-brand-800" role="status">{{ $reportFlash }}</p>
            @endif

            @if ($totalReviews > 0)
                <div class="fb-card p-4">
                    <div class="flex items-center justify-between gap-2">
                        <h3 class="text-[20px] font-bold">{{ __('Avis') }}</h3>
                        <div class="flex items-center gap-2 text-[15px]">
                            <label for="review-sort" class="text-stone-600">{{ __('Trier') }}</label>
                            <select id="review-sort" wire:model.live="reviewSort" class="rounded-md border-0 bg-stone-200 py-1.5 ps-3 pe-8 font-semibold">
                                <option value="recent">{{ __('Plus récents') }}</option>
                                <option value="helpful">{{ __('Plus utiles') }}</option>
                                <option value="highest">{{ __('Meilleures notes') }}</option>
                                <option value="lowest">{{ __('Moins bonnes notes') }}</option>
                            </select>
                        </div>
                    </div>
                    <div class="mt-3 flex gap-6">
                        <div class="text-center">
                            <p class="text-[40px] font-bold leading-none">{{ number_format($business->rating_avg, 1, ',', ' ') }}</p>
                            <x-directory.stars :rating="$business->rating_avg" size="14" class="mt-1" />
                            <p class="mt-1 text-[13px] text-stone-600">{{ trans_choice(':count avis|:count avis', $totalReviews, ['count' => $totalReviews]) }}</p>
                        </div>
                        <dl class="flex-1 space-y-1">
                            @for ($star = 5; $star >= 1; $star--)
                                @php $count = $distribution[$star] ?? 0; @endphp
                                <div class="flex items-center gap-2 text-[13px]">
                                    <dt class="w-6 text-stone-600">{{ $star }}★</dt>
                                    <dd class="h-2 flex-1 overflow-hidden rounded-full bg-stone-200">
                                        <span class="block h-full rounded-full bg-brand-600" style="width: {{ $totalReviews ? round($count / $totalReviews * 100) : 0 }}%"></span>
                                    </dd>
                                    <dd class="w-6 text-end text-stone-600">{{ $count }}</dd>
                                </div>
                            @endfor
                        </dl>
                    </div>
                </div>

                @foreach ($reviews as $review)
                    @php $voted = in_array($review->id, $votedIds, true); @endphp
                    <article class="fb-card pt-3" wire:key="review-{{ $review->id }}">
                        <header class="flex items-center gap-2 px-4">
                            <x-ui.avatar :name="$review->authorName()" size="40" />
                            <div class="min-w-0 flex-1">
                                <p class="truncate font-semibold">{{ $review->authorName() }}</p>
                                <p class="flex items-center gap-1 text-[13px] text-stone-600">
                                    <time datetime="{{ $review->published_at?->toIso8601String() }}">{{ $review->published_at?->locale(app()->getLocale())->isoFormat('LL') }}</time>
                                    @if ($review->edited_at) · {{ __('Modifié') }} @endif
                                    · <x-ui.icon name="globe" size="12" />
                                </p>
                            </div>
                            <x-directory.stars :rating="$review->rating" size="15" />
                        </header>

                        @if ($review->body)
                            <p class="mt-3 px-4 text-[15px]" @if ($review->locale) lang="{{ $review->locale }}" @endif dir="auto">{!! nl2br(e($review->body)) !!}</p>
                        @endif

                        @php $photos = $review->getMedia('photos'); @endphp
                        @if ($photos->isNotEmpty())
                            <div class="mt-3 grid grid-cols-3 gap-0.5">
                                @foreach ($photos as $photo)
                                    <div class="relative aspect-square" wire:key="review-photo-{{ $photo->id }}">
                                        <a href="{{ $photo->getUrl('large') }}" target="_blank" rel="noopener" class="block size-full">
                                            <img src="{{ $photo->getUrl('thumb') }}" alt="{{ __('Photo de :business', ['business' => $business->name]) }}" width="240" height="240" loading="lazy" class="size-full object-cover">
                                        </a>
                                        <button type="button" wire:click="openReport('media', {{ $photo->id }})" class="absolute bottom-1 end-1 rounded-full bg-white/90 p-1 text-stone-600" aria-label="{{ __('Signaler cette photo') }}">
                                            <x-ui.icon name="flag" size="12" />
                                        </button>
                                    </div>
                                @endforeach
                            </div>
                        @endif

                        @if ($review->helpful_count > 0)
                            <p class="mt-3 flex items-center gap-1.5 px-4 text-[13px] text-stone-600">
                                <span class="flex size-[18px] items-center justify-center rounded-full bg-brand-600 text-white"><x-ui.icon name="thumbs-up" size="11" /></span>
                                {{ trans_choice(':count personne a trouvé cet avis utile|:count personnes ont trouvé cet avis utile', $review->helpful_count, ['count' => $review->helpful_count]) }}
                            </p>
                        @endif

                        <div class="mx-4 mt-2 border-t border-stone-300 py-1">
                            <div class="flex">
                                <button type="button" wire:click="toggleHelpful({{ $review->id }})" class="fb-action"
                                        aria-pressed="{{ $voted ? 'true' : 'false' }}"
                                        @disabled(auth()->id() === $review->user_id)>
                                    <x-ui.icon name="thumbs-up" size="18" :class="$voted ? 'fill-brand-600' : ''" /> {{ __('Utile') }}
                                </button>
                                <button type="button" wire:click="openReport('review', {{ $review->id }})" class="fb-action">
                                    <x-ui.icon name="flag" size="18" /> {{ __('Signaler') }}
                                </button>
                            </div>
                        </div>

                        @if ($review->reply || $isOwner)
                            <div class="space-y-2 border-t border-stone-300 px-4 py-3">
                                @if ($review->reply)
                                    <div class="flex gap-2">
                                        <x-ui.avatar :name="$business->name" :src="$logo" size="32" />
                                        <div class="min-w-0">
                                            <div class="fb-comment">
                                                <p class="text-[13px] font-semibold">{{ $business->name }} <span class="font-normal text-stone-600">· {{ __('Réponse du propriétaire') }}</span></p>
                                                <p class="text-[15px]" dir="auto">{!! nl2br(e($review->reply->body)) !!}</p>
                                            </div>
                                            <button type="button" wire:click="openReport('review_reply', {{ $review->reply->id }})" class="ms-3 mt-0.5 text-[12px] font-semibold text-stone-600 hover:underline">{{ __('Signaler') }}</button>
                                        </div>
                                    </div>
                                @endif

                                @if ($isOwner)
                                    <form wire:submit="saveReply({{ $review->id }})" class="flex items-start gap-2">
                                        <x-ui.avatar :name="$business->name" :src="$logo" size="32" />
                                        <div class="min-w-0 flex-1">
                                            <label for="reply-{{ $review->id }}" class="sr-only">{{ $review->reply ? __('Modifier votre réponse') : __('Répondre publiquement') }}</label>
                                            <div class="flex items-end gap-1 rounded-[18px] bg-stone-100 pe-1">
                                                <textarea id="reply-{{ $review->id }}" wire:model="replyBodies.{{ $review->id }}" rows="1" dir="auto"
                                                          maxlength="{{ \App\Actions\Reviews\ReplyToReview::MAX_LENGTH }}"
                                                          class="block min-h-9 w-full resize-y border-0 bg-transparent px-3 py-2 text-[15px] focus:outline-none"
                                                          placeholder="{{ $review->reply ? __('Modifier votre réponse') : __('Répondre publiquement') }}"></textarea>
                                                <button type="submit" class="mb-1 rounded-full p-1.5 text-brand-600 hover:bg-stone-200" aria-label="{{ __('Envoyer') }}">
                                                    <x-ui.icon name="navigation" size="18" class="rotate-45 rtl:-rotate-[135deg]" />
                                                </button>
                                            </div>
                                            @error('replyBodies.'.$review->id) <p class="ms-3 mt-1 text-[13px] text-red-700">{{ $message }}</p> @enderror
                                        </div>
                                    </form>
                                @endif
                            </div>
                        @endif
                    </article>
                @endforeach

                {{ $reviews->onEachSide(1)->links(data: ['scrollTo' => '#avis']) }}
            @else
                <div class="fb-card p-6 text-center">
                    <span class="mx-auto flex size-12 items-center justify-center rounded-full bg-stone-200 text-stone-600"><x-ui.icon name="message-circle" size="22" /></span>
                    <p class="mt-2 font-semibold">{{ __('Pas encore d’avis') }}</p>
                    <p class="text-stone-600">{{ __('Pas encore d’avis. Soyez le premier à partager votre expérience.') }}</p>
                </div>
            @endif

            {{-- Similar businesses: organic only, never sponsored. --}}
            @if ($similar->isNotEmpty())
                <section aria-labelledby="similar-title" class="fb-card p-4">
                    <h2 id="similar-title" class="text-[17px] font-semibold">{{ __('À proximité') }}</h2>
                    <div class="-mx-2 mt-2">
                        @foreach ($similar as $other)
                            <x-directory.business-card :business="$other" :city="$city" :status="OpeningHours::for($other)->statusAt()" source="organic" :framed="false" wire:key="similar-{{ $other->id }}" />
                        @endforeach
                    </div>
                </section>
            @endif
        </section>
    </div>

    @if ($reportType !== null)
        <div class="fixed inset-0 z-50 flex items-end justify-center bg-white/70 p-0 sm:items-center sm:p-4" role="dialog" aria-modal="true" aria-labelledby="report-title"
             x-data x-on:keydown.escape.window="$wire.closeReport()">
            <form wire:submit="submitReport" class="w-full max-w-lg rounded-t-xl bg-white shadow-[var(--shadow-pop)] sm:rounded-lg">
                <div class="relative border-b border-stone-300 px-4 py-4 text-center">
                    <h2 id="report-title" class="text-[20px] font-bold">{{ __('Signaler un contenu') }}</h2>
                    <button type="button" wire:click="closeReport" class="absolute end-3 top-3 flex size-9 items-center justify-center rounded-full bg-stone-200 hover:bg-stone-300" aria-label="{{ __('Annuler') }}">
                        <x-ui.icon name="x" size="20" />
                    </button>
                </div>
                <div class="space-y-3 p-4">
                    <div>
                        <label for="report-reason" class="fb-label">{{ __('Motif') }}</label>
                        <select id="report-reason" wire:model="reportReason" class="fb-field mt-1" required>
                            <option value="">{{ __('Choisir…') }}</option>
                            @foreach ($reportReasons as $reason)
                                <option value="{{ $reason->value }}">{{ $reason->getLabel() }}</option>
                            @endforeach
                        </select>
                        @error('reportReason') <p class="mt-1 text-[13px] text-red-700">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label for="report-details" class="fb-label">{{ __('Détails (facultatif)') }}</label>
                        <textarea id="report-details" wire:model="reportDetails" rows="3" maxlength="{{ \App\Actions\Reports\SubmitReport::MAX_DETAILS }}" dir="auto" class="fb-field mt-1"></textarea>
                    </div>
                    <button type="submit" class="fb-btn fb-btn-primary w-full">{{ __('Envoyer le signalement') }}</button>
                </div>
            </form>
        </div>
    @endif
</div>

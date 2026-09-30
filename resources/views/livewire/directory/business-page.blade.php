@php
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
@endphp
<div class="space-y-6">
    <nav aria-label="{{ __('Fil d’Ariane') }}" class="text-sm text-stone-500">
        <ol class="flex flex-wrap items-center gap-1">
            <li><a href="{{ route('city.home', ['city' => $city]) }}" wire:navigate class="hover:underline">{{ $city->name }}</a></li>
            @if ($primaryCategory)
                <li aria-hidden="true">›</li>
                <li><a href="{{ route('city.category', ['city' => $city, 'category' => $primaryCategory]) }}" wire:navigate class="hover:underline">{{ $primaryCategory->name }}</a></li>
            @endif
        </ol>
    </nav>

    {{-- Header --}}
    <header class="space-y-2">
        <h1 class="text-2xl font-bold sm:text-3xl">
            {{ $business->name }}
            @if ($business->is_verified)
                <span class="ms-1 inline-flex items-center gap-1 align-middle text-sm font-medium text-brand-700">
                    <x-ui.icon name="badge-check" size="18" /> {{ __('Vérifié') }}
                </span>
            @endif
        </h1>
        <p class="text-stone-600">
            {{ $business->categories->pluck('name')->implode(' · ') }}
            @if ($business->neighborhood) · {{ $business->neighborhood->name }} @endif
            @if (PriceRange::label($business->price_range)) · {{ PriceRange::label($business->price_range) }} @endif
        </p>
        <div class="flex flex-wrap items-center gap-3">
            @if ($business->rating_count > 0)
                <a href="#avis" class="inline-flex items-center gap-1 text-sm">
                    <x-directory.stars :rating="$business->rating_avg" />
                    <span class="font-medium">{{ number_format($business->rating_avg, 1, ',', ' ') }}</span>
                    <span class="text-stone-500">({{ trans_choice(':count avis|:count avis', $business->rating_count, ['count' => $business->rating_count]) }})</span>
                </a>
            @endif
            <x-directory.open-badge :status="$status" class="text-sm" />
        </div>
    </header>

    {{-- Contact actions: WhatsApp first, it is how Morocco reaches a business. --}}
    <section aria-label="{{ __('Contacter') }}" class="grid grid-cols-2 gap-2 sm:grid-cols-4">
        @if ($whatsappUrl)
            <a href="{{ $whatsappUrl }}" target="_blank" rel="noopener noreferrer" x-data x-on:click="{{ $track('whatsapp_click') }}"
               class="col-span-2 flex items-center justify-center gap-2 rounded-xl bg-[#1f9d55] px-4 py-3 font-semibold text-white hover:bg-[#188a49] sm:col-span-1">
                <x-ui.icon name="message-circle" /> WhatsApp
            </a>
        @endif
        @if ($business->phone)
            <a href="tel:{{ $business->phone }}" x-data x-on:click="{{ $track('call_click') }}"
               class="flex items-center justify-center gap-2 rounded-xl border border-stone-300 bg-white px-4 py-3 font-medium hover:bg-stone-50">
                <x-ui.icon name="phone" /> {{ __('Appeler') }}
            </a>
        @endif
        <a href="https://www.google.com/maps/dir/?api=1&amp;destination={{ $lat }},{{ $lng }}" target="_blank" rel="noopener noreferrer"
           x-data x-on:click="{{ $track('directions_click') }}"
           class="flex items-center justify-center gap-2 rounded-xl border border-stone-300 bg-white px-4 py-3 font-medium hover:bg-stone-50">
            <x-ui.icon name="navigation" /> {{ __('Itinéraire') }}
        </a>
        <button type="button" x-data x-on:click="$share(@js($business->name), @js(route('city.business', ['city' => $city, 'business' => $business])), @js(__('Lien copié.')))"
                class="flex items-center justify-center gap-2 rounded-xl border border-stone-300 bg-white px-4 py-3 font-medium hover:bg-stone-50">
            <x-ui.icon name="share" /> {{ __('Partager') }}
        </button>
    </section>

    {{-- Photos --}}
    @if ($gallery->isNotEmpty())
        <section aria-label="{{ __('Photos') }}" class="-mx-4 flex snap-x gap-2 overflow-x-auto px-4 pb-2">
            @foreach ($gallery as $photo)
                <img src="{{ $photo->getUrl('large') }}" srcset="{{ $photo->getSrcset('large') }}" sizes="(max-width: 640px) 85vw, 400px"
                     alt="{{ __('Photo de :business', ['business' => $business->name]) }}" width="400" height="300"
                     loading="{{ $loop->first ? 'eager' : 'lazy' }}" decoding="async"
                     class="h-56 w-[85vw] max-w-sm shrink-0 snap-start rounded-xl object-cover" wire:key="photo-{{ $photo->id }}">
            @endforeach
        </section>
    @endif

    <div class="grid gap-6 md:grid-cols-3">
        <div class="space-y-6 md:col-span-2">
            @if ($business->description)
                <section aria-labelledby="about-title">
                    <h2 id="about-title" class="text-lg font-semibold">{{ __('À propos') }}</h2>
                    {{-- User-written text: escaped, then line breaks restored. --}}
                    <p class="mt-2 text-stone-700">{!! nl2br(e($business->description)) !!}</p>
                </section>
            @endif

            {{-- Rating summary --}}
            <section id="avis" aria-labelledby="reviews-title" class="space-y-4">
                <h2 id="reviews-title" class="text-lg font-semibold">{{ __('Avis') }}</h2>

                @if ($totalReviews > 0)
                    <div class="flex gap-6 rounded-xl border border-stone-200 bg-white p-4">
                        <div class="text-center">
                            <p class="text-4xl font-bold">{{ number_format($business->rating_avg, 1, ',', ' ') }}</p>
                            <x-directory.stars :rating="$business->rating_avg" size="14" />
                            <p class="mt-1 text-xs text-stone-500">{{ trans_choice(':count avis|:count avis', $totalReviews, ['count' => $totalReviews]) }}</p>
                        </div>
                        <dl class="flex-1 space-y-1">
                            @for ($star = 5; $star >= 1; $star--)
                                @php $count = $distribution[$star] ?? 0; @endphp
                                <div class="flex items-center gap-2 text-sm">
                                    <dt class="w-6 text-stone-600">{{ $star }}★</dt>
                                    <dd class="h-2 flex-1 overflow-hidden rounded-full bg-stone-200">
                                        <span class="block h-full rounded-full bg-accent-500" style="width: {{ $totalReviews ? round($count / $totalReviews * 100) : 0 }}%"></span>
                                    </dd>
                                    <dd class="w-8 text-end text-stone-500">{{ $count }}</dd>
                                </div>
                            @endfor
                        </dl>
                    </div>

                    <div class="flex items-center justify-end gap-2 text-sm">
                        <label for="review-sort" class="text-stone-600">{{ __('Trier') }}</label>
                        <select id="review-sort" wire:model.live="reviewSort" class="rounded-lg border border-stone-300 bg-white px-2 py-1">
                            <option value="recent">{{ __('Plus récents') }}</option>
                            <option value="helpful">{{ __('Plus utiles') }}</option>
                            <option value="highest">{{ __('Meilleures notes') }}</option>
                            <option value="lowest">{{ __('Moins bonnes notes') }}</option>
                        </select>
                    </div>

                    <ul class="space-y-3">
                        @foreach ($reviews as $review)
                            <li class="rounded-xl border border-stone-200 bg-white p-4" wire:key="review-{{ $review->id }}">
                                <div class="flex items-center justify-between gap-2">
                                    <p class="font-medium">{{ $review->authorName() }}</p>
                                    <time class="text-xs text-stone-500" datetime="{{ $review->published_at?->toIso8601String() }}">
                                        {{ $review->published_at?->locale(app()->getLocale())->isoFormat('LL') }}
                                    </time>
                                </div>
                                <x-directory.stars :rating="$review->rating" size="14" class="mt-1" />
                                @if ($review->body)
                                    <p class="mt-2 text-stone-700" @if ($review->locale) lang="{{ $review->locale }}" @endif dir="auto">{!! nl2br(e($review->body)) !!}</p>
                                @endif
                                @if ($review->edited_at)
                                    <p class="mt-1 text-xs text-stone-500">{{ __('Modifié') }}</p>
                                @endif

                                @if ($review->reply)
                                    <div class="ms-4 mt-3 border-s-2 border-brand-200 ps-3">
                                        <p class="text-sm font-medium text-brand-800">{{ __('Réponse du propriétaire') }}</p>
                                        <p class="mt-1 text-sm text-stone-700" dir="auto">{!! nl2br(e($review->reply->body)) !!}</p>
                                    </div>
                                @endif
                            </li>
                        @endforeach
                    </ul>

                    {{ $reviews->onEachSide(1)->links(data: ['scrollTo' => '#avis']) }}
                @else
                    <p class="text-stone-600">{{ __('Pas encore d’avis. Soyez le premier à partager votre expérience.') }}</p>
                @endif
            </section>
        </div>

        <aside class="space-y-6">
            {{-- Hours --}}
            <section aria-labelledby="hours-title" class="rounded-xl border border-stone-200 bg-white p-4">
                <h2 id="hours-title" class="flex items-center gap-2 font-semibold"><x-ui.icon name="clock" size="18" /> {{ __('Horaires') }}</h2>
                @if ($business->is_always_open)
                    <p class="mt-2 text-sm">{{ __('Ouvert 24h/24, 7j/7') }}</p>
                @else
                    <table class="mt-2 w-full text-sm">
                        <tbody>
                            @foreach ($hours->weeklySchedule() as $day => $slots)
                                <tr @class(['font-semibold' => $day === $today])>
                                    <th scope="row" class="py-1 text-start font-normal">{{ mb_convert_case($monday->copy()->addDays($day - 1)->dayName, MB_CASE_TITLE) }}</th>
                                    <td class="py-1 text-end" dir="ltr">
                                        @forelse ($slots as [$opens, $closes])
                                            <span class="block">{{ $opens }}–{{ $closes }}</span>
                                        @empty
                                            <span class="text-stone-500">{{ __('Fermé') }}</span>
                                        @endforelse
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @endif

                @php $special = $hours->upcomingSpecialPeriods(); @endphp
                @if ($special->isNotEmpty())
                    <div class="mt-3 border-t border-stone-200 pt-3 text-sm">
                        <p class="font-medium">{{ __('Horaires exceptionnels') }}</p>
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

            {{-- Address and map --}}
            <section aria-labelledby="address-title" class="overflow-hidden rounded-xl border border-stone-200 bg-white">
                <div class="p-4">
                    <h2 id="address-title" class="flex items-center gap-2 font-semibold"><x-ui.icon name="map-pin" size="18" /> {{ __('Adresse') }}</h2>
                    @if ($business->address)
                        <p class="mt-1 text-sm text-stone-700">{{ $business->address }}</p>
                    @endif
                    <p class="mt-2 flex gap-3 text-sm">
                        <a href="https://waze.com/ul?ll={{ $lat }},{{ $lng }}&amp;navigate=yes" target="_blank" rel="noopener noreferrer" x-data x-on:click="{{ $track('directions_click') }}" class="text-brand-700 underline">Waze</a>
                        <a href="https://www.google.com/maps/search/?api=1&amp;query={{ $lat }},{{ $lng }}" target="_blank" rel="noopener noreferrer" class="text-brand-700 underline">Google Maps</a>
                    </p>
                </div>
                <div wire:ignore
                     x-data="directoryMap(@js([
                         'center' => ['lat' => $lat, 'lng' => $lng],
                         'zoom' => 16,
                         'tiles' => ['url' => config('directory.maps.tile_url'), 'attribution' => config('directory.maps.attribution'), 'maxZoom' => config('directory.maps.max_zoom')],
                         'labels' => ['sponsored' => __('Sponsorisé')],
                     ]))"
                     data-markers="{{ json_encode([['name' => $business->name, 'lat' => $lat, 'lng' => $lng, 'url' => route('city.business', ['city' => $city, 'business' => $business]), 'sponsored' => false, 'status' => null]], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE) }}">
                    <div x-ref="canvas" class="h-48 w-full"></div>
                </div>
            </section>

            {{-- Other contact details --}}
            @if ($business->phone || $website || $business->social_links)
                <section class="space-y-2 rounded-xl border border-stone-200 bg-white p-4 text-sm">
                    @if ($business->phone)
                        <p class="flex items-center gap-2"><x-ui.icon name="phone" size="16" /> <a href="tel:{{ $business->phone }}" dir="ltr" x-data x-on:click="{{ $track('call_click') }}">{{ MoroccanPhone::formatNational($business->phone) }}</a></p>
                    @endif
                    @if ($website)
                        <p class="flex items-center gap-2"><x-ui.icon name="globe" size="16" />
                            <a href="{{ $website }}" target="_blank" rel="noopener noreferrer nofollow ugc" x-data x-on:click="{{ $track('website_click') }}" class="truncate text-brand-700 underline">{{ parse_url($website, PHP_URL_HOST) }}</a>
                        </p>
                    @endif
                    @foreach ($business->social_links ?? [] as $network => $url)
                        @if (SafeUrl::isSafe($url))
                            <p><a href="{{ $url }}" target="_blank" rel="noopener noreferrer nofollow ugc" class="text-brand-700 underline">{{ ucfirst($network) }}</a></p>
                        @endif
                    @endforeach
                </section>
            @endif

            @unless ($business->is_claimed)
                <section class="rounded-xl border border-dashed border-brand-300 bg-brand-50 p-4 text-sm">
                    <p class="font-medium text-brand-900">{{ __('C’est votre établissement ?') }}</p>
                    <p class="mt-1 text-brand-900/80">{{ __('Revendiquez cette fiche pour répondre aux avis et mettre à jour vos informations.') }}</p>
                    <a href="{{ route('page.show', ['page' => 'contact']) }}" class="mt-2 inline-block font-semibold text-brand-700 underline">{{ __('Revendiquer la fiche') }}</a>
                </section>
            @endunless
        </aside>
    </div>

    {{-- Similar businesses: organic only, never sponsored. --}}
    @if ($similar->isNotEmpty())
        <section aria-labelledby="similar-title">
            <h2 id="similar-title" class="text-lg font-semibold">{{ __('À proximité') }}</h2>
            <div class="mt-3 grid gap-3 sm:grid-cols-2">
                @foreach ($similar as $other)
                    <x-directory.business-card :business="$other" :city="$city" :status="\App\Services\Hours\OpeningHours::for($other)->statusAt()" source="organic" wire:key="similar-{{ $other->id }}" />
                @endforeach
            </div>
        </section>
    @endif
</div>

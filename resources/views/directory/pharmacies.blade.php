<x-layouts.page
    :title="__('Pharmacies de garde à :city', ['city' => $city->name])"
    :meta-description="__('Pharmacies de garde ouvertes cette nuit et cette semaine à :city, avec adresse, téléphone et itinéraire.', ['city' => $city->name])"
    :canonical="route('city.pharmacies', ['city' => $city])">
    <div class="mx-auto max-w-[680px] space-y-4">
        <header class="fb-card flex items-center gap-3 p-4">
            <span class="flex size-12 shrink-0 items-center justify-center rounded-full bg-[#e41e3f] text-white"><x-ui.icon name="pill" size="24" /></span>
            <h1 class="text-[24px] font-bold leading-7">{{ __('Pharmacies de garde à :city', ['city' => $city->name]) }}</h1>
        </header>

        <section aria-labelledby="now-title" class="fb-card p-4">
            <h2 id="now-title" class="flex items-center gap-2 text-[17px] font-semibold">
                <span class="size-2.5 rounded-full bg-online" aria-hidden="true"></span>{{ __('De garde maintenant') }}
            </h2>
            <div class="-mx-2 mt-2">
                @forelse ($current as $duty)
                    @include('directory.partials.pharmacy-duty', ['duty' => $duty, 'city' => $city])
                @empty
                    <p class="px-2 text-stone-600">{{ __('Aucune pharmacie de garde enregistrée en ce moment.') }}</p>
                @endforelse
            </div>
        </section>

        @foreach ($upcoming as $date => $duties)
            <section class="fb-card p-4">
                <h2 class="text-[17px] font-semibold first-letter:uppercase">{{ \Illuminate\Support\Carbon::parse($date)->locale(app()->getLocale())->isoFormat('dddd LL') }}</h2>
                <div class="-mx-2 mt-2">
                    @foreach ($duties as $duty)
                        @include('directory.partials.pharmacy-duty', ['duty' => $duty, 'city' => $city])
                    @endforeach
                </div>
            </section>
        @endforeach

        <p class="flex items-start gap-2 rounded-lg bg-[#ffebe9] p-3 text-[14px] text-[#8a1121]">
            <x-ui.icon name="info" size="18" class="mt-px shrink-0" />
            {{ __('Les gardes sont mises à jour par notre équipe. En cas d’urgence, appelez le 15 (SAMU) ou le 150 (protection civile).') }}
        </p>
    </div>
</x-layouts.page>

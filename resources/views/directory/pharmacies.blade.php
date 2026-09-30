<x-layouts.page
    :title="__('Pharmacies de garde à :city', ['city' => $city->name])"
    :meta-description="__('Pharmacies de garde ouvertes cette nuit et cette semaine à :city, avec adresse, téléphone et itinéraire.', ['city' => $city->name])"
    :canonical="route('city.pharmacies', ['city' => $city])">
    <div class="space-y-6">
        <h1 class="flex items-center gap-2 text-2xl font-bold">
            <x-ui.icon name="pill" class="text-emerald-700" /> {{ __('Pharmacies de garde à :city', ['city' => $city->name]) }}
        </h1>

        <section aria-labelledby="now-title">
            <h2 id="now-title" class="text-lg font-semibold">{{ __('De garde maintenant') }}</h2>
            @forelse ($current as $duty)
                @include('directory.partials.pharmacy-duty', ['duty' => $duty, 'city' => $city])
            @empty
                <p class="mt-2 text-stone-600">{{ __('Aucune pharmacie de garde enregistrée en ce moment.') }}</p>
            @endforelse
        </section>

        @foreach ($upcoming as $date => $duties)
            <section>
                <h2 class="text-lg font-semibold">{{ \Illuminate\Support\Carbon::parse($date)->locale(app()->getLocale())->isoFormat('dddd LL') }}</h2>
                @foreach ($duties as $duty)
                    @include('directory.partials.pharmacy-duty', ['duty' => $duty, 'city' => $city])
                @endforeach
            </section>
        @endforeach

        <p class="text-sm text-stone-500">{{ __('Les gardes sont mises à jour par notre équipe. En cas d’urgence, appelez le 15 (SAMU) ou le 150 (protection civile).') }}</p>
    </div>
</x-layouts.page>

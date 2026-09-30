<x-layouts.page :title="$city->name">
    <section class="py-6">
        <h1 class="text-2xl font-bold sm:text-3xl">{{ __('De quoi avez-vous besoin à :city ?', ['city' => $city->name]) }}</h1>
        <p class="mt-2 text-stone-600">{{ __('Artisans, mécaniciens, pharmacies, restaurants : trouvez le bon service près de chez vous.') }}</p>
    </section>
</x-layouts.page>

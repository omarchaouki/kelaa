<div class="space-y-6 py-4">
    <h1 class="text-2xl font-bold">{{ __('Mon espace professionnel') }}</h1>

    @if ($owned->isEmpty())
        <div class="fb-card p-4">
            <p>{{ __('Vous ne gérez encore aucun établissement.') }}</p>
            <p class="mt-1 text-sm text-stone-600">{{ __('Trouvez votre fiche et cliquez sur « Revendiquer la fiche », ou ajoutez votre établissement.') }}</p>
        </div>
    @else
        <ul class="space-y-2">
            @foreach ($owned as $business)
                <li class="fb-card flex items-center justify-between gap-3 p-4" wire:key="owned-{{ $business->id }}">
                    <div class="min-w-0">
                        <p class="truncate font-semibold">{{ $business->name }}</p>
                        <p class="text-sm text-stone-600">
                            {{ $business->categories->firstWhere('pivot.is_primary', true)?->name }} · {{ $business->city->name }}
                            · <span>{{ $business->status->getLabel() }}</span>
                        </p>
                        <p class="text-xs text-stone-500">{{ __('Fiche complétée à :percent %', ['percent' => $business->profile_completeness]) }}</p>
                    </div>
                    <a href="{{ route('dashboard.business', ['business' => $business->id]) }}" wire:navigate class="fb-btn fb-btn-primary shrink-0">{{ __('Gérer') }}</a>
                </li>
            @endforeach
        </ul>
    @endif

    @if ($submitted->isNotEmpty())
        <section>
            <h2 class="text-lg font-semibold">{{ __('Fiches que vous avez proposées') }}</h2>
            <ul class="mt-2 space-y-1 text-sm">
                @foreach ($submitted as $business)
                    <li>{{ $business->name }} · <span class="text-stone-600">{{ $business->status->getLabel() }}</span></li>
                @endforeach
            </ul>
        </section>
    @endif
</div>

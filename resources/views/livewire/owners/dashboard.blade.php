@php
    use App\Enums\BusinessStatus;

    $defaultCity = config('directory.default_city');
@endphp
<div class="mx-auto max-w-3xl space-y-4 py-2">
    <div class="flex flex-wrap items-center justify-between gap-2">
        <h1 class="text-[24px] font-bold">{{ __('Mon espace professionnel') }}</h1>
        <a href="{{ route('city.add', ['city' => $defaultCity]) }}" wire:navigate class="fb-btn fb-btn-primary">
            <x-ui.icon name="plus" size="18" /> {{ __('Ajouter un établissement') }}
        </a>
    </div>

    {{-- Listings this account manages --}}
    <section class="fb-card p-4" aria-labelledby="owned-title">
        <h2 id="owned-title" class="text-[17px] font-semibold">{{ __('Mes établissements') }}</h2>
        @if ($owned->isEmpty())
            <p class="mt-2 text-stone-600">{{ __('Vous ne gérez encore aucun établissement.') }}</p>
            <p class="mt-1 text-[14px] text-stone-600">{{ __('Trouvez votre fiche et cliquez sur « Revendiquer la fiche », ou ajoutez votre établissement.') }}</p>
        @else
            <ul class="-mx-2 mt-2">
                @foreach ($owned as $business)
                    <li class="flex items-center gap-3 rounded-lg p-2 hover:bg-stone-100" wire:key="owned-{{ $business->id }}">
                        <x-ui.avatar :name="$business->name" :src="$business->getFirstMediaUrl('logo', 'thumb') ?: null" size="48" />
                        <div class="min-w-0 flex-1">
                            <p class="truncate font-semibold">{{ $business->name }}</p>
                            <p class="text-[13px] text-stone-600">
                                {{ $business->categories->firstWhere('pivot.is_primary', true)?->name }} · {{ $business->city->name }} · {{ $business->status->getLabel() }}
                            </p>
                            <p class="text-[12px] text-stone-500">{{ __('Fiche complétée à :percent %', ['percent' => $business->profile_completeness]) }}</p>
                        </div>
                        <a href="{{ route('dashboard.business', ['business' => $business->id]) }}" wire:navigate class="fb-btn fb-btn-primary shrink-0">{{ __('Gérer') }}</a>
                    </li>
                @endforeach
            </ul>
        @endif
    </section>

    {{-- Requests: listings this account proposed --}}
    <section class="fb-card p-4" aria-labelledby="requests-title">
        <h2 id="requests-title" class="text-[17px] font-semibold">{{ __('Mes demandes d’ajout') }}</h2>
        @if ($submitted->isEmpty())
            <p class="mt-2 text-stone-600">{{ __('Vous n’avez proposé aucun établissement pour le moment.') }}</p>
        @else
            <ul class="mt-2 divide-y divide-stone-200">
                @foreach ($submitted as $business)
                    <li class="flex items-start gap-3 py-3" wire:key="request-{{ $business->id }}">
                        <x-ui.avatar :name="$business->name" size="40" />
                        <div class="min-w-0 flex-1">
                            <p class="truncate font-semibold">{{ $business->name }}</p>
                            <p class="text-[13px] text-stone-600">{{ __('Envoyée le :date', ['date' => $business->created_at?->format('d/m/Y')]) }}</p>
                            @if ($business->status === BusinessStatus::Rejected && $business->rejection_reason)
                                <p class="mt-1 rounded-lg bg-red-50 p-2 text-[13px] text-red-800">{{ __('Motif : :reason', ['reason' => $business->rejection_reason]) }}</p>
                            @endif
                        </div>
                        @switch($business->status)
                            @case(BusinessStatus::Pending)
                                <span class="shrink-0 rounded-full bg-amber-100 px-2.5 py-1 text-[12px] font-semibold text-amber-800">{{ __('En vérification') }}</span>
                                @break
                            @case(BusinessStatus::Published)
                                <a href="{{ route('city.business', ['city' => $business->city, 'business' => $business]) }}" wire:navigate class="shrink-0 rounded-full bg-green-100 px-2.5 py-1 text-[12px] font-semibold text-online-dark hover:underline">{{ __('Publiée · voir') }}</a>
                                @break
                            @case(BusinessStatus::Rejected)
                                <span class="shrink-0 rounded-full bg-red-100 px-2.5 py-1 text-[12px] font-semibold text-red-800">{{ __('Refusée') }}</span>
                                @break
                            @default
                                <span class="shrink-0 rounded-full bg-stone-200 px-2.5 py-1 text-[12px] font-semibold text-stone-700">{{ $business->status->getLabel() }}</span>
                        @endswitch
                    </li>
                @endforeach
            </ul>
        @endif
    </section>
</div>

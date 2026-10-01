@php
    $input = 'mt-1 block w-full rounded-lg border border-stone-300 px-3 py-2 focus:border-brand-600 focus:outline-none focus:ring-2 focus:ring-brand-600/30';
    $money = fn ($amount) => number_format((float) $amount, 2, ',', ' ').' DH';
@endphp
<div class="space-y-6 py-4">
    <div>
        <a href="{{ route('dashboard.business', ['business' => $business->id]) }}" wire:navigate class="text-sm text-brand-600 hover:underline">← {{ $business->name }}</a>
        <h1 class="text-2xl font-bold">{{ __('Formules et paiements') }}</h1>
        <p class="text-sm text-stone-600">
            {{ __('Formule : :plan', ['plan' => $currentPlan?->name ?? __('Gratuit')]) }}
            @if ($business->plan_expires_at?->isFuture()) · {{ __('jusqu’au :date', ['date' => $business->plan_expires_at->format('d/m/Y')]) }} @endif
        </p>
    </div>

    @if ($flash)
        <p class="rounded-lg bg-brand-50 p-3 text-brand-800" role="status">{{ $flash }}</p>
    @endif

    <p class="rounded-lg bg-stone-100 p-3 text-sm text-stone-700">
        {{ __('Les formules et emplacements sponsorisés n’achètent jamais de meilleures notes ni une meilleure position dans les résultats naturels. Les emplacements sponsorisés sont toujours signalés « Sponsorisé ».') }}
    </p>

    {{-- Pending payments: instructions and receipt upload --}}
    @foreach ($pending as $payment)
        @php $info = $instructions($payment); @endphp
        <section class="space-y-2 rounded-lg border-s-4 border-amber-400 bg-amber-50 p-4" wire:key="pending-{{ $payment->id }}">
            <h2 class="font-semibold">{{ __('Paiement en attente : :purpose', ['purpose' => $payment->purpose->getLabel()]) }}</h2>
            <dl class="grid grid-cols-2 gap-1 text-sm">
                <dt>{{ __('Montant') }}</dt><dd class="font-semibold">{{ $money($payment->amount) }}</dd>
                <dt>{{ __('Référence à indiquer') }}</dt><dd class="font-mono font-semibold" dir="ltr">{{ $payment->reference }}</dd>
                @if ($payment->method === \App\Enums\PaymentMethod::BankTransfer)
                    <dt>{{ __('Bénéficiaire') }}</dt><dd>{{ $info['beneficiary'] ?? '—' }}</dd>
                    <dt>{{ __('Banque') }}</dt><dd>{{ $info['bank_name'] ?? '—' }}</dd>
                    <dt>RIB</dt><dd class="font-mono" dir="ltr">{{ $info['rib'] ?? '—' }}</dd>
                @else
                    <dt>{{ __('Agences') }}</dt><dd>{{ $info['cash_agencies'] }}</dd>
                    <dt>{{ __('Destinataire') }}</dt><dd>{{ $info['cash_recipient'] ?? '—' }}</dd>
                @endif
                <dt>{{ __('À payer avant le') }}</dt><dd>{{ $payment->expires_at?->format('d/m/Y H:i') }}</dd>
            </dl>
            @if ($payment->getFirstMedia('receipt'))
                <p class="text-sm text-brand-700">{{ __('Reçu reçu, validation en cours.') }}</p>
            @else
                <form wire:submit="uploadReceipt({{ $payment->id }})" class="flex flex-wrap items-center gap-2 text-sm">
                    <input type="file" wire:model="receipts.{{ $payment->id }}" accept="application/pdf,image/jpeg,image/png,image/webp">
                    <button type="submit" class="fb-btn fb-btn-primary">{{ __('Envoyer le reçu') }}</button>
                    @error('receipts.'.$payment->id) <p class="w-full text-red-700">{{ $message }}</p> @enderror
                </form>
            @endif
        </section>
    @endforeach

    {{-- Plans --}}
    <section class="fb-card space-y-3 p-4">
        <h2 class="font-semibold">{{ __('Changer de formule') }}</h2>
        <div class="grid gap-3 sm:grid-cols-2">
            @foreach ($plans as $plan)
                <label @class(['cursor-pointer rounded-xl border p-3', 'border-brand-600 ring-2 ring-brand-600/30' => $planId === $plan->id, 'border-stone-200' => $planId !== $plan->id]) wire:key="plan-{{ $plan->id }}">
                    <input type="radio" wire:model.live="planId" value="{{ $plan->id }}" class="sr-only">
                    <p class="font-semibold">{{ $plan->name }}</p>
                    <p class="text-sm">{{ $money($plan->price_monthly) }} / {{ __('mois') }} · {{ $money($plan->price_yearly) }} / {{ __('an') }}</p>
                    <ul class="mt-2 space-y-0.5 text-xs text-stone-600">
                        <li>{{ __(':count photos', ['count' => $plan->feature('max_photos')]) }}</li>
                        @if ($plan->feature('show_stats')) <li>{{ __('Statistiques') }}</li> @endif
                        @if ($plan->feature('logo_and_cover')) <li>{{ __('Logo et couverture') }}</li> @endif
                        @if ($plan->feature('highlighted_card')) <li>{{ __('Carte mise en avant (même position)') }}</li> @endif
                        @if ($plan->feature('remove_competitor_ads')) <li>{{ __('Pas de concurrents sponsorisés sur votre fiche') }}</li> @endif
                        @if ($plan->feature('priority_support')) <li>{{ __('Traitement prioritaire') }}</li> @endif
                    </ul>
                </label>
            @endforeach
        </div>
        <form wire:submit="orderPlan" class="grid gap-2 sm:grid-cols-3">
            <select wire:model="period" class="{{ $input }}" aria-label="{{ __('Durée') }}">
                <option value="monthly">{{ __('Mensuel') }}</option>
                <option value="yearly">{{ __('Annuel') }}</option>
            </select>
            <select wire:model="method" class="{{ $input }}" aria-label="{{ __('Moyen de paiement') }}">
                <option value="bank_transfer">{{ __('Virement bancaire') }}</option>
                <option value="cash_agency">{{ __('Espèces en agence (CashPlus, Wafacash)') }}</option>
            </select>
            <button type="submit" @disabled($planId === null) class="fb-btn fb-btn-primary mt-1">{{ __('Commander') }}</button>
            @error('planId') <p class="text-sm text-red-700 sm:col-span-3">{{ $message }}</p> @enderror
        </form>
    </section>

    {{-- Sponsored placement --}}
    <section class="fb-card space-y-3 p-4">
        <h2 class="font-semibold">{{ __('Emplacement sponsorisé') }}</h2>
        <p class="text-sm text-stone-600">{{ __('Apparaissez en tête de votre catégorie, avec la mention « Sponsorisé ». 3 places maximum par catégorie.') }}</p>
        <form wire:submit="orderPlacement" class="grid gap-3 sm:grid-cols-2">
            <label class="text-sm">{{ __('Catégorie') }}
                <select wire:model.live="categoryId" class="{{ $input }}">
                    @foreach ($business->categories as $category)
                        <option value="{{ $category->id }}">{{ $category->name }}</option>
                    @endforeach
                </select>
            </label>
            <label class="text-sm">{{ __('Zone') }}
                <select wire:model.live="neighborhoodId" class="{{ $input }}">
                    <option value="">{{ __('Toute la ville') }}</option>
                    @foreach ($neighborhoods as $neighborhood)
                        <option value="{{ $neighborhood->id }}">{{ $neighborhood->name }}</option>
                    @endforeach
                </select>
            </label>
            <label class="text-sm">{{ __('Position') }}
                <select wire:model.live="position" class="{{ $input }}">
                    @foreach ([1, 2, 3] as $p) <option value="{{ $p }}">{{ $p }}</option> @endforeach
                </select>
            </label>
            <label class="text-sm">{{ __('Début') }}
                <input type="date" wire:model="startDate" class="{{ $input }}">
            </label>
            <label class="text-sm">{{ __('Durée') }}
                <span class="mt-1 flex gap-2">
                    <input type="number" min="1" max="12" wire:model.live="units" class="fb-field w-20">
                    <select wire:model.live="unit" class="fb-field flex-1">
                        <option value="week">{{ __('semaine(s)') }}</option>
                        <option value="month">{{ __('mois') }}</option>
                    </select>
                </span>
            </label>
            <div class="text-sm">
                {{ __('Prix') }}
                <p class="mt-2 text-lg font-semibold">{{ $quote ? $money($quote) : __('Non disponible') }}</p>
            </div>
            @error('position') <p class="text-sm text-red-700 sm:col-span-2" role="alert">{{ $message }}</p> @enderror
            @error('units') <p class="text-sm text-red-700 sm:col-span-2">{{ $message }}</p> @enderror
            @error('startDate') <p class="text-sm text-red-700 sm:col-span-2">{{ $message }}</p> @enderror
            <button type="submit" @disabled($quote === null) class="fb-btn fb-btn-primary sm:col-span-2">{{ __('Réserver et payer') }}</button>
        </form>

        @if ($grid !== [])
            <div class="overflow-x-auto">
                <table class="w-full text-center text-xs">
                    <caption class="mb-1 text-start text-sm font-medium">{{ __('Disponibilités par semaine') }}</caption>
                    <thead><tr><th class="text-start">{{ __('Semaine du') }}</th><th>1</th><th>2</th><th>3</th></tr></thead>
                    <tbody>
                        @foreach ($grid as $row)
                            <tr>
                                <td class="text-start" dir="ltr">{{ $row['week']->format('d/m') }}</td>
                                @foreach ($row['positions'] as $free)
                                    <td><span @class(['inline-block rounded px-2 py-0.5', 'bg-emerald-100 text-emerald-800' => $free, 'bg-stone-200 text-stone-500' => ! $free])>{{ $free ? __('libre') : __('pris') }}</span></td>
                                @endforeach
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </section>

    {{-- Invoicing details --}}
    <form wire:submit="saveLegal" class="fb-card grid gap-3 p-4 sm:grid-cols-3">
        <h2 class="font-semibold sm:col-span-3">{{ __('Informations de facturation') }}</h2>
        <label class="text-sm">{{ __('Raison sociale') }} <input type="text" wire:model="legalName" maxlength="190" class="{{ $input }}"></label>
        <label class="text-sm">ICE <input type="text" wire:model="ice" inputmode="numeric" maxlength="15" dir="ltr" class="{{ $input }}"></label>
        <label class="text-sm">{{ __('Adresse') }} <input type="text" wire:model="legalAddress" maxlength="255" class="{{ $input }}"></label>
        @error('ice') <p class="text-sm text-red-700 sm:col-span-3">{{ $message }}</p> @enderror
        <button type="submit" class="rounded-lg border border-brand-600 px-4 py-2 font-semibold text-brand-700 sm:col-span-3 sm:justify-self-start">{{ __('Enregistrer') }}</button>
    </form>

    {{-- History --}}
    <section class="fb-card p-4">
        <h2 class="font-semibold">{{ __('Historique') }}</h2>
        <ul class="mt-2 divide-y divide-stone-200 text-sm">
            @forelse ($history as $payment)
                <li class="flex flex-wrap items-center justify-between gap-2 py-2" wire:key="history-{{ $payment->id }}">
                    <span>{{ $payment->created_at->format('d/m/Y') }} · {{ $payment->purpose->getLabel() }} · {{ $money($payment->amount) }}</span>
                    <span class="flex items-center gap-3">
                        <span>{{ $payment->status->getLabel() }}</span>
                        @if ($payment->invoice)
                            <a href="{{ route('dashboard.invoice', ['business' => $business->id, 'invoice' => $payment->invoice->id]) }}" class="text-brand-600 hover:underline">{{ __('Facture :number', ['number' => $payment->invoice->number]) }}</a>
                        @endif
                    </span>
                </li>
            @empty
                <li class="py-2 text-stone-500">{{ __('Aucun paiement.') }}</li>
            @endforelse
        </ul>
    </section>
</div>

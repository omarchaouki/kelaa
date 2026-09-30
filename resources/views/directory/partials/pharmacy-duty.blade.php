<article class="mt-2 flex items-center justify-between gap-3 rounded-xl border border-stone-200 bg-white p-3">
    <div class="min-w-0">
        <a href="{{ route('city.business', ['city' => $city, 'business' => $duty->business]) }}" class="font-semibold hover:text-brand-700">{{ $duty->business->name }}</a>
        <p class="truncate text-sm text-stone-600">
            {{ $duty->business->address }}@if ($duty->business->neighborhood) · {{ $duty->business->neighborhood->name }}@endif
        </p>
        <p class="text-sm text-emerald-800" dir="ltr">{{ $duty->starts_at->format('d/m H:i') }} → {{ $duty->ends_at->format('d/m H:i') }}</p>
    </div>
    @if ($duty->business->phone)
        <a href="tel:{{ $duty->business->phone }}" x-data x-on:click="$track({{ $duty->business->id }}, 'call_click')"
           class="shrink-0 rounded-lg bg-emerald-700 px-3 py-2 text-sm font-semibold text-white" aria-label="{{ __('Appeler :business', ['business' => $duty->business->name]) }}">
            <x-ui.icon name="phone" size="18" />
        </a>
    @endif
</article>

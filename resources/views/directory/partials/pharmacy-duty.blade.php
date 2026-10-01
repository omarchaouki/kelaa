<article class="flex items-center gap-3 rounded-lg p-2 hover:bg-stone-100">
    <x-ui.avatar :name="$duty->business->name" size="48" :open="$duty->starts_at->isPast() && $duty->ends_at->isFuture()" />
    <div class="min-w-0 flex-1">
        <a href="{{ route('city.business', ['city' => $city, 'business' => $duty->business]) }}" class="font-semibold hover:underline">{{ $duty->business->name }}</a>
        <p class="truncate text-[13px] text-stone-600">
            {{ $duty->business->address }}@if ($duty->business->neighborhood) · {{ $duty->business->neighborhood->name }}@endif
        </p>
        <p class="text-[13px] font-semibold text-online-dark" dir="ltr">{{ $duty->starts_at->format('d/m H:i') }} → {{ $duty->ends_at->format('d/m H:i') }}</p>
    </div>
    @if ($duty->business->phone)
        <a href="tel:{{ $duty->business->phone }}" x-data x-on:click="$track({{ $duty->business->id }}, 'call_click')"
           class="flex size-10 shrink-0 items-center justify-center rounded-full bg-brand-50 text-brand-600 hover:bg-brand-100" aria-label="{{ __('Appeler :business', ['business' => $duty->business->name]) }}">
            <x-ui.icon name="phone" size="18" />
        </a>
    @endif
</article>

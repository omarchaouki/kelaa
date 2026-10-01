@props(['week'])
@php
    $monday = now()->startOfWeek(\Carbon\CarbonInterface::MONDAY)->locale(app()->getLocale());
@endphp
<div class="space-y-2">
    @foreach ($week as $day => $entry)
        <div class="fb-card flex flex-wrap items-center gap-2 p-2 text-sm" wire:key="day-{{ $day }}">
            <span class="w-24 font-medium">{{ mb_convert_case($monday->copy()->addDays($day - 1)->dayName, MB_CASE_TITLE) }}</span>
            <label class="flex items-center gap-1">
                <input type="checkbox" wire:model.live="week.{{ $day }}.closed" class="rounded border-stone-300">
                {{ __('Fermé') }}
            </label>
            @unless ($entry['closed'])
                @foreach ($entry['slots'] as $index => $slot)
                    <span class="flex items-center gap-1" dir="ltr" wire:key="slot-{{ $day }}-{{ $index }}">
                        <input type="time" wire:model="week.{{ $day }}.slots.{{ $index }}.0" class="fb-field" aria-label="{{ __('Ouverture') }}">
                        –
                        <input type="time" wire:model="week.{{ $day }}.slots.{{ $index }}.1" class="fb-field" aria-label="{{ __('Fermeture') }}">
                        @if (count($entry['slots']) > 1)
                            <button type="button" wire:click="removeSlot({{ $day }}, {{ $index }})" class="text-stone-500" aria-label="{{ __('Retirer') }}"><x-ui.icon name="x" size="14" /></button>
                        @endif
                    </span>
                    @error("week.{$day}.slots.{$index}") <span class="text-xs text-red-700">{{ $message }}</span> @enderror
                @endforeach
                @if (count($entry['slots']) < \App\Support\Hours\WeeklyHours::MAX_SLOTS_PER_DAY)
                    <button type="button" wire:click="addSlot({{ $day }})" class="text-xs text-brand-600 hover:underline">{{ __('+ créneau') }}</button>
                @endif
            @endunless
        </div>
    @endforeach
    <p class="text-xs text-stone-500">{{ __('Une fermeture avant l’ouverture (ex. 20:00–02:00) continue après minuit.') }}</p>
</div>

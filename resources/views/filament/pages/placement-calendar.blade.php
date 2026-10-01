<x-filament-panels::page>
    <div class="grid gap-4 sm:grid-cols-3">
        <label class="text-sm">{{ __('Ville') }}
            <select wire:model.live="cityId" class="mt-1 block w-full rounded-lg border-gray-300">
                @foreach ($cities as $city)
                    <option value="{{ $city->id }}">{{ $city->name }}</option>
                @endforeach
            </select>
        </label>
        <label class="text-sm">{{ __('Catégorie') }}
            <select wire:model.live="categoryId" class="mt-1 block w-full rounded-lg border-gray-300">
                <option value="">{{ __('Choisir…') }}</option>
                @foreach ($categories as $category)
                    <option value="{{ $category->id }}">{{ $category->name }}</option>
                @endforeach
            </select>
        </label>
        <label class="text-sm">{{ __('Zone') }}
            <select wire:model.live="neighborhoodId" class="mt-1 block w-full rounded-lg border-gray-300">
                <option value="">{{ __('Toute la ville') }}</option>
                @foreach ($neighborhoods as $neighborhood)
                    <option value="{{ $neighborhood->id }}">{{ $neighborhood->name }}</option>
                @endforeach
            </select>
        </label>
    </div>

    @if ($rows === [])
        <p class="text-sm text-gray-500">{{ __('Choisissez une catégorie.') }}</p>
    @else
        <table class="w-full text-sm">
            <thead>
                <tr class="text-left">
                    <th class="p-2">{{ __('Semaine du') }}</th>
                    <th class="p-2">{{ __('Position 1') }}</th>
                    <th class="p-2">{{ __('Position 2') }}</th>
                    <th class="p-2">{{ __('Position 3') }}</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($rows as $row)
                    <tr class="border-t border-gray-200">
                        <td class="p-2">{{ $row['week']->format('d/m/Y') }}</td>
                        @foreach ($row['cells'] as $cell)
                            <td class="p-2">
                                @if ($cell)
                                    <span class="font-medium">{{ $cell['business'] }}</span>
                                    <span class="text-xs text-gray-500">({{ $cell['status'] }})</span>
                                @else
                                    <span class="text-green-700">{{ __('libre') }}</span>
                                @endif
                            </td>
                        @endforeach
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif
</x-filament-panels::page>

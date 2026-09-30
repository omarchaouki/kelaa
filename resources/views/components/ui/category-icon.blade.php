@props(['icon' => null, 'size' => 24])
@php
    // Icon names come from the database: only plain Lucide names are accepted,
    // so a crafted value can never point outside the icon directory.
    $name = is_string($icon) && preg_match('/^[a-z0-9]+(-[a-z0-9]+)*$/', $icon) ? $icon : null;
    $exists = $name !== null && is_file(base_path("vendor/mallardduck/blade-lucide-icons/resources/svg/{$name}.svg"));
@endphp
@if ($exists)
    <x-dynamic-component :component="'lucide-'.$name" width="{{ $size }}" height="{{ $size }}" aria-hidden="true" {{ $attributes }} />
@else
    <x-ui.icon name="store" :size="$size" {{ $attributes }} />
@endif

@props(['status'])
@if ($status)
    <span {{ $attributes->merge(['class' => 'inline-flex items-center gap-1 text-xs font-medium '.($status->isOpen ? 'text-emerald-700' : 'text-rose-700')]) }}>
        <span class="size-1.5 rounded-full {{ $status->isOpen ? 'bg-emerald-600' : 'bg-rose-600' }}" aria-hidden="true"></span>
        {{ $status->label() }}
    </span>
@endif

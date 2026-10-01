@props(['status'])
@if ($status)
    <span {{ $attributes->merge(['class' => 'inline-flex items-center gap-1 font-semibold '.($status->isOpen ? 'text-online-dark' : 'text-stone-600')]) }}>
        <span class="size-2 rounded-full {{ $status->isOpen ? 'bg-online' : 'bg-stone-400' }}" aria-hidden="true"></span>
        {{ $status->label() }}
    </span>
@endif

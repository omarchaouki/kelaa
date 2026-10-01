{{-- Always visible and in the reader's language: paid visibility must never look organic. --}}
<span {{ $attributes->merge(['class' => 'inline-flex items-center gap-1 text-[13px] font-semibold text-stone-600']) }}>
    {{ __('Sponsorisé') }}
    <span aria-hidden="true">·</span>
    <x-ui.icon name="globe" size="12" />
</span>

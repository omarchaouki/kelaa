@php
    use App\Support\Locales;
    $locale = app()->getLocale();
    $pageTitle = isset($title) && $title ? $title.' · '.config('app.name') : config('app.name');
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', $locale) }}" dir="{{ Locales::direction() }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="contact-events-endpoint" content="{{ route('contact-events.store') }}">
    <meta name="theme-color" content="#ffffff">

    <title>{{ $pageTitle }}</title>
    @if (! empty($metaDescription))
        <meta name="description" content="{{ $metaDescription }}">
    @endif
    @if (! empty($noindex))
        <meta name="robots" content="noindex, follow">
    @endif
    @if (! empty($canonical))
        <link rel="canonical" href="{{ $canonical }}">
    @endif

    <meta property="og:site_name" content="{{ config('app.name') }}">
    <meta property="og:title" content="{{ $pageTitle }}">
    <meta property="og:locale" content="{{ Locales::regional() }}">
    <meta property="og:type" content="{{ $ogType ?? 'website' }}">
    <meta property="og:url" content="{{ $canonical ?? url()->current() }}">
    @if (! empty($metaDescription))
        <meta property="og:description" content="{{ $metaDescription }}">
    @endif
    @if (! empty($ogImage))
        <meta property="og:image" content="{{ $ogImage }}">
        <meta name="twitter:card" content="summary_large_image">
    @else
        <meta name="twitter:card" content="summary">
    @endif

    <link rel="manifest" href="/manifest.webmanifest">
    <link rel="icon" href="/favicon.ico" sizes="any">
    <link rel="apple-touch-icon" href="/icons/apple-touch-icon.png">

    <x-seo.hreflang />

    {{-- System fonts only (Segoe UI, San Francisco, Roboto…): nothing to download. --}}
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
    @foreach ($structuredData ?? [] as $schema)
        <x-seo.json-ld :data="$schema" />
    @endforeach
    @stack('head')
</head>
<body class="min-h-dvh bg-stone-100 text-[15px] leading-snug text-stone-900 antialiased">
    <a href="#main" class="sr-only focus:not-sr-only focus:absolute focus:start-2 focus:top-2 focus:z-50 focus:rounded-md focus:bg-white focus:px-3 focus:py-2 focus:shadow-[var(--shadow-pop)]">
        {{ __('Aller au contenu') }}
    </a>

    <x-layout.header />

    {{-- Feed-style pages (home, search, business) use the wide three-column canvas. --}}
    <main id="main" @class(['mx-auto w-full pb-24 pt-4 sm:pb-10', 'max-w-[1240px] px-0 sm:px-4' => ! empty($wide), 'max-w-5xl px-3 sm:px-4' => empty($wide)])>
        {{ $slot }}
    </main>

    <x-layout.footer />
    <x-layout.cookie-consent />
    <x-layout.bottom-nav />

    {{-- Livewire and Alpine are bundled in app.js (one nonce'd script, no injected inline code). --}}
    @livewireScriptConfig
    @stack('scripts')
</body>
</html>

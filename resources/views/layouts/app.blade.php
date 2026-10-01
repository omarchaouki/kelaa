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
    <meta name="theme-color" content="#0f5e57">

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

    @fonts(Locales::isRtl() ? ['inter', 'arabic'] : ['inter'])
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
    @foreach ($structuredData ?? [] as $schema)
        <x-seo.json-ld :data="$schema" />
    @endforeach
    @stack('head')
</head>
<body class="min-h-dvh bg-stone-50 text-stone-900 antialiased">
    <a href="#main" class="sr-only focus:not-sr-only focus:absolute focus:start-2 focus:top-2 focus:z-50 focus:rounded focus:bg-white focus:px-3 focus:py-2">
        {{ __('Aller au contenu') }}
    </a>

    <x-layout.header />

    <main id="main" class="mx-auto w-full max-w-5xl px-4 pb-24 pt-4 sm:pb-10">
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

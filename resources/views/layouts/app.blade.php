@php
    use App\Support\Locales;
    $locale = app()->getLocale();
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', $locale) }}" dir="{{ Locales::direction() }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#0f5e57">

    <title>{{ isset($title) && $title ? $title.' · '.config('app.name') : config('app.name') }}</title>
    @isset($metaDescription)
        <meta name="description" content="{{ $metaDescription }}">
    @endisset

    <x-seo.hreflang />

    @fonts(Locales::isRtl() ? ['inter', 'arabic'] : ['inter'])
    @vite(['resources/css/app.css', 'resources/js/app.js'])
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
    <x-layout.bottom-nav />

    @stack('scripts')
</body>
</html>

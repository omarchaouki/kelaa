@props(['title' => null, 'metaDescription' => null, 'canonical' => null, 'noindex' => false, 'structuredData' => [], 'ogType' => null, 'ogImage' => null, 'wide' => false])
{{-- Wrapper so controller views and Livewire pages share layouts/app. --}}
@include('layouts.app', ['title' => $title, 'metaDescription' => $metaDescription, 'canonical' => $canonical, 'noindex' => $noindex, 'structuredData' => $structuredData, 'ogType' => $ogType, 'ogImage' => $ogImage, 'wide' => $wide, 'slot' => $slot])

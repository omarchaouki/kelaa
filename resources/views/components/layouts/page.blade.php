@props(['title' => null, 'metaDescription' => null, 'canonical' => null, 'noindex' => false])
{{-- Wrapper so controller views and Livewire pages share layouts/app. --}}
@include('layouts.app', ['title' => $title, 'metaDescription' => $metaDescription, 'canonical' => $canonical, 'noindex' => $noindex, 'slot' => $slot])

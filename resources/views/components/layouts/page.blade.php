@props(['title' => null, 'metaDescription' => null])
{{-- Wrapper so controller views and Livewire pages share layouts/app. --}}
@include('layouts.app', ['title' => $title, 'metaDescription' => $metaDescription, 'slot' => $slot])

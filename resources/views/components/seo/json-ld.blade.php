@props(['data'])
{{-- Data block, never executed. Encoded by JsonLd::encode() so it cannot break out of the element. --}}
<script type="application/ld+json" nonce="{{ \Illuminate\Support\Facades\Vite::cspNonce() }}">{!! \App\Support\Seo\JsonLd::encode($data) !!}</script>

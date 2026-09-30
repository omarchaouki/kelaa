@php
    $alternates = \App\Support\Locales::alternates(request());
@endphp
@foreach ($alternates as $hreflang => $href)
    <link rel="alternate" hreflang="{{ $hreflang }}" href="{{ $href }}">
@endforeach
<link rel="alternate" hreflang="x-default" href="{{ $alternates[\App\Support\Locales::default()] }}">

<x-layouts.page :title="$page->title" :meta-description="$page->meta_description">
    <article class="prose prose-stone max-w-none py-6">
        <h1>{{ $page->title }}</h1>
        {{-- Admin-authored HTML, sanitized on save and again here (App\Support\Security\RichText). --}}
        {!! $page->safeBody() !!}
    </article>
</x-layouts.page>

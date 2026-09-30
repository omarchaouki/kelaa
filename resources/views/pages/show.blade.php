<x-layouts.page :title="$page->title" :meta-description="$page->meta_description">
    <article class="prose prose-stone max-w-none py-6">
        <h1>{{ $page->title }}</h1>
        {{-- Body is authored by admins in the panel's rich editor. --}}
        {!! $page->body !!}
    </article>
</x-layouts.page>

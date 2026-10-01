<x-layouts.page :title="$page->title" :meta-description="$page->meta_description" :canonical="route('page.show', ['page' => $page])">
    <article class="fb-card prose prose-stone mx-auto max-w-[760px] p-6 prose-headings:font-bold prose-a:text-brand-600 prose-a:no-underline hover:prose-a:underline sm:p-8">
        <h1 class="!text-[28px]">{{ $page->title }}</h1>
        {{-- Admin-authored HTML, sanitized on save and again here (App\Support\Security\RichText). --}}
        {!! $page->safeBody() !!}
    </article>
</x-layouts.page>

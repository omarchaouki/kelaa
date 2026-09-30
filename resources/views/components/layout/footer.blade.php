<footer class="border-t border-stone-200 bg-white">
    <div class="mx-auto max-w-5xl px-4 py-8 text-sm text-stone-600">
        <nav aria-label="{{ __('Informations légales') }}">
            <ul class="flex flex-wrap gap-x-4 gap-y-2">
                @foreach (['a-propos', 'cgu', 'confidentialite', 'regles-des-avis', 'cookies', 'mentions-legales', 'contact'] as $slug)
                    <li>
                        <a href="{{ route('page.show', ['page' => $slug]) }}" wire:navigate class="hover:text-brand-700 hover:underline">
                            {{ __("pages.{$slug}") }}
                        </a>
                    </li>
                @endforeach
            </ul>
        </nav>

        <p class="mt-4">
            &copy; {{ now()->year }} {{ config('app.name') }}
            @if (config('directory.cndp_number'))
                · {{ __('Déclaration CNDP n° :number', ['number' => config('directory.cndp_number')]) }}
            @endif
        </p>
    </div>
</footer>

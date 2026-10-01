{{-- Admin logo: the same blue pin bubble as the public site, then the name. --}}
<span class="flex items-center gap-2">
    <span class="flex size-9 items-center justify-center rounded-full bg-primary-600 text-white">
        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 10c0 4.993-5.539 10.193-7.399 11.799a1 1 0 0 1-1.202 0C9.539 20.193 4 14.993 4 10a8 8 0 0 1 16 0"/><circle cx="12" cy="10" r="3"/></svg>
    </span>
    <span class="text-lg font-bold text-gray-900 dark:text-white">{{ config('app.name') }} <span class="font-semibold text-primary-600">Admin</span></span>
</span>

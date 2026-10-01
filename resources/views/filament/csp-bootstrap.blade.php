{{--
    Filament's own inline <script> tags carry no nonce, so our CSP blocks them.
    This nonce'd copy does the two jobs that matter before Alpine starts:
    a valid collapsed-groups list for the sidebar (otherwise the menu crashes
    on null) and the dark-mode class. Semicolons are required: Blade's @js
    swallows the following line break.
--}}
<script nonce="{{ \Illuminate\Support\Facades\Vite::cspNonce() }}">
    (() => {
        let groups = null;
        try { groups = JSON.parse(localStorage.getItem('collapsedGroups')); } catch (e) {}
        if (! Array.isArray(groups)) localStorage.setItem('collapsedGroups', '[]');

        const defaultTheme = @js(filament()->getDefaultThemeMode()->value);

        window.loadDarkMode = () => {
            window.theme = localStorage.getItem('theme') ?? defaultTheme;
            const dark = window.theme === 'dark' || (window.theme === 'system' && window.matchMedia('(prefers-color-scheme: dark)').matches);
            document.documentElement.classList.toggle('dark', dark);
        };

        window.loadDarkMode();
        document.addEventListener('livewire:navigated', window.loadDarkMode);
    })();
</script>

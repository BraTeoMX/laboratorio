<meta charset="utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />

<title>{{ $title ?? config('app.name') }}</title>

<link rel="icon" href="/favicon.ico" sizes="any">
<link rel="icon" href="/favicon.svg" type="image/svg+xml">
<link rel="apple-touch-icon" href="/apple-touch-icon.png">

@persist('scripts')
<style>
    :root {
        color-scheme: light;
    }
</style>
<script>
    (function () {
        var root = document.documentElement;

        function forceLight() {
            try {
                window.localStorage.setItem('flux.appearance', 'light');
            } catch (e) {
                // Ignore storage errors and keep light mode enforced.
            }
            root.classList.remove('dark');
        }

        window.Flux = window.Flux || {};
        window.Flux.applyAppearance = function () {
            forceLight();
        };

        forceLight();

        // Keep forcing light even if another script tries to re-apply dark.
        new MutationObserver(function () {
            if (root.classList.contains('dark')) {
                root.classList.remove('dark');
            }
        }).observe(root, { attributes: true, attributeFilter: ['class'] });

        document.addEventListener('livewire:navigated', forceLight);
        document.addEventListener('DOMContentLoaded', forceLight);
    })();
</script>
@vite(['resources/css/app.css', 'resources/js/app.js'])
@endpersist

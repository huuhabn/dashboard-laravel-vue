<!DOCTYPE html>
<html lang="en" @class(['dark' => ($appearance ?? 'system') == 'dark'])>
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <script>
            window.__DASHBOARD_PREFIX__ = @json(config('services.dashboard.prefix'));
            (function () {
                const appearance = '{{ $appearance ?? "system" }}';
                if (appearance === 'system') {
                    const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
                    if (prefersDark) {
                        document.documentElement.classList.add('dark');
                    }
                }
            })();
        </script>
        <style>
            html {
                background-color: oklch(1 0 0);
            }
            html.dark {
                background-color: oklch(0.145 0 0);
            }
        </style>
        <link rel="icon" href="/favicon.ico" sizes="any">
        <link rel="icon" href="/favicon.svg" type="image/svg+xml">
        <link rel="apple-touch-icon" href="/apple-touch-icon.png">
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600" rel="stylesheet" />
        <link href="https://fonts.bunny.net/css?family=noto-sans-arabic:400,500,600" rel="stylesheet" />
        @vite(['resources/css/app.css', 'resources/js/app.ts'])
        <title>{{ config('app.name', 'Laravel') }}</title>
    </head>
    <body class="font-sans antialiased">
        <div id="app"></div>
    </body>
</html>

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}"  @class(['dark' => ($appearance ?? 'system') == 'dark'])>
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">

        {{-- PWA --}}
        <link rel="manifest" href="{{ asset('build/manifest.webmanifest') }}">
        <meta name="theme-color" media="(prefers-color-scheme: light)" content="#fafafa">
        <meta name="theme-color" media="(prefers-color-scheme: dark)" content="#18181b">
        <meta name="mobile-web-app-capable" content="yes">
        <meta name="apple-mobile-web-app-capable" content="yes">
        <meta name="apple-mobile-web-app-status-bar-style" content="default">
        <meta name="apple-mobile-web-app-title" content="Studiofinance">

        {{-- Inline script to detect system dark mode preference and apply it immediately --}}
        <script>
            (function() {
                const appearance = '{{ $appearance ?? "system" }}';

                if (appearance === 'system') {
                    const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;

                    if (prefersDark) {
                        document.documentElement.classList.add('dark');
                    }
                }
            })();
        </script>

        {{-- Inline style: set HTML background to match the theme zinc tokens
             (anti-FOUC). Valori allineati a :root / .dark di app.css. --}}
        <style>
            html {
                background-color: oklch(98.5% 0.001 286);
            }

            html.dark {
                background-color: oklch(14.5% 0.003 286);
            }
        </style>

        <link rel="icon" href="/favicon.ico" sizes="any">
        <link rel="icon" href="/favicon.svg" type="image/svg+xml">
        <link rel="apple-touch-icon" href="/apple-touch-icon.png">

        {{-- Font loading: <link> nel head + preconnect, NON @import in CSS.
             Parallelo e prioritario, evita il FOUT prolungato. --}}
        <link rel="preconnect" href="https://api.fontshare.com" crossorigin>
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://api.fontshare.com/v2/css?f[]=switzer@200,300,400,500,600,700&display=swap" rel="stylesheet">
        <link href="https://fonts.googleapis.com/css2?family=Google+Sans+Code:wght@300..800&display=swap" rel="stylesheet">

        @vite(['resources/css/app.css', 'resources/js/app.ts', "resources/js/pages/{$page['component']}.vue"])
        <x-inertia::head>
            <title>{{ config('app.name', 'Laravel') }}</title>
        </x-inertia::head>
    </head>
    <body class="font-sans antialiased">
        <x-inertia::app />
    </body>
</html>

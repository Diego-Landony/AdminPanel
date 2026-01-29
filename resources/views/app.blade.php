<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        {{-- Script de inicialización del tema para evitar FOUC (flash of unstyled content) --}}
        <script>
            (function() {
                const THEME_KEY = 'appearance';
                const theme = localStorage.getItem(THEME_KEY) || 'system';
                
                function applyTheme(themeValue) {
                    const html = document.documentElement;
                    html.classList.remove('light', 'dark');
                    if (themeValue === 'system') {
                        const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
                        html.classList.add(prefersDark ? 'dark' : 'light');
                    } else {
                        html.classList.add(themeValue);
                    }
                }
                
                applyTheme(theme);
            })();
        </script>

        {{-- Inline style to set the HTML background color based on our theme in app.css --}}
        <style>
            html {
                background-color: oklch(1 0 0);
            }

            html.dark {
                background-color: oklch(0.145 0 0);
            }
        </style>

        <title inertia>{{ config('app.name', 'Laravel') }}</title>

        <meta name="csrf-token" content="{{ csrf_token() }}">
        <meta name="google-site-verification" content="-5qwOCjotY61KpprRLAAlZDAtOewsVtlOTZJa-23o4c" />

        <link rel="icon" href="/favicon.png" type="image/png">
        <link rel="icon" href="/subway-icon.png" type="image/png" sizes="192x192">
        <link rel="apple-touch-icon" href="/subway-icon.png">

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600" rel="stylesheet" />

        @routes
        @viteReactRefresh
        @vite(['resources/js/app.tsx', "resources/js/pages/{$page['component']}.tsx"])
        @inertiaHead
    </head>
    <body class="font-sans antialiased">
        @inertia
    </body>
</html>

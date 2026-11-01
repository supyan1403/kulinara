<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title inertia>{{ config('app.name', 'Laravel') }}</title>

        <!-- Fonts: Plus Jakarta Sans (utama) · Inter (body) · JetBrains Mono (angka gizi) -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link
            href="https://fonts.bunny.net/css?family=inter:400,500,600|jetbrains-mono:400,500,600|plus-jakarta-sans:400,500,600,700,800&display=swap"
            rel="stylesheet"
        />

        <!-- Scripts -->
        @routes
        @viteReactRefresh
        @vite(['resources/js/app.jsx', "resources/js/Pages/{$page['component']}.jsx"])
        @inertiaHead
    </head>
    <body class="bg-warm-cream font-sans text-charcoal antialiased selection:bg-mint-light selection:text-primary">
        @inertia
    </body>
</html>

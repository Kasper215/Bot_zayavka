<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title inertia>{{ config('app.name', 'Laravel') }}</title>

        <!-- Scripts -->
        @routes
        @vite(['resources/css/app.css', 'resources/js/app.js'])
        @inertiaHead
        <link rel="manifest" href="/manifest.json" crossorigin="use-credentials">
        <link rel="apple-touch-icon" href="/pwa-icon.png">
        <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
        <meta name="theme-color" content="#0f172a">

    </head>
    <body class="font-sans antialiased">
        @inertia
    </body>
</html>

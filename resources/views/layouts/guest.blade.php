<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#4a7c59">
    <link rel="manifest" href="/manifest.webmanifest">
    <link rel="apple-touch-icon" href="/images/pwa/apple-touch-icon.png">
    <title>{{ config('app.name', 'ChickenCare') }} — @yield('title', __('auth.guest.default_title'))</title>
    <script>
        (function() {
            var t = document.cookie.match(/(?:^|; )theme=([^;]+)/)?.[1] || 'system';
            var d = window.matchMedia('(prefers-color-scheme: dark)').matches;
            if (t === 'dark' || (t === 'system' && d)) document.documentElement.classList.add('dark');
        })();
    </script>
    @vite(['resources/scss/app.scss', 'resources/js/app.js'])
</head>
<body>
    @hasSection('full-content')
        @yield('full-content')
    @else
        <main class="auth-layout">
            @yield('content')
        </main>
    @endif
    <x-ui.pwa-banner />
</body>
</html>

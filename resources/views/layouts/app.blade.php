<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'ChickenCare') }} — @yield('title', 'Dashboard')</title>
    <script>
        (function() {
            var t = document.cookie.match(/(?:^|; )theme=([^;]+)/)?.[1] || 'system';
            var d = window.matchMedia('(prefers-color-scheme: dark)').matches;
            if (t === 'dark' || (t === 'system' && d)) document.documentElement.classList.add('dark');
        })();
    </script>
    @vite(['resources/scss/app.scss', 'resources/js/app.js'])
</head>
<body hx-boost="true" hx-headers='{"X-CSRF-TOKEN": "{{ csrf_token() }}"}'>
    <div class="app-layout">
        <x-layout.sidebar />
        <main class="app-layout__main">
            <x-layout.navbar :title="$__env->yieldContent('title', 'Dashboard')" />
            <x-ui.flash />
            <div class="app-layout__content" id="main-content">
                @yield('content')
            </div>
        </main>
        <x-layout.mobile-dock />
    </div>
    <div id="modal-container" aria-live="polite"></div>
    <x-ui.toast />
    @stack('scripts')
</body>
</html>

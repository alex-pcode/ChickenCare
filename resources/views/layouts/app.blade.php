<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#4a7c59">
    <link rel="manifest" href="/manifest.webmanifest">
    <link rel="apple-touch-icon" href="/images/pwa/apple-touch-icon.png">
    <title>{{ config('app.name', 'ChickenCare') }} — @yield('title', __('dashboard.page.title'))</title>
    <script>
        (function() {
            var t = document.cookie.match(/(?:^|; )theme=([^;]+)/)?.[1] || 'system';
            var d = window.matchMedia('(prefers-color-scheme: dark)').matches;
            if (t === 'dark' || (t === 'system' && d)) document.documentElement.classList.add('dark');
        })();
    </script>
    {{-- Above-the-fold Fraunces weights (body + headings): preload so text doesn't wait for the CSS to discover them --}}
    <link rel="preload" href="{{ Vite::asset('resources/fonts/fraunces-400.woff2') }}" as="font" type="font/woff2" crossorigin>
    <link rel="preload" href="{{ Vite::asset('resources/fonts/fraunces-600.woff2') }}" as="font" type="font/woff2" crossorigin>
    @include('partials.first-paint-styles')
    @vite(['resources/scss/app.scss', 'resources/js/app.js'])
</head>
@php
    $warmRoutes = [
        route('app.dashboard'),
        route('app.eggs.index'),
        route('app.account.index'),
    ];

    if (auth()->user()->isPremium()) {
        $warmRoutes = array_merge($warmRoutes, [
            route('app.crm.index'),
            route('app.crm.reports'),
            route('app.expenses.index'),
            route('app.flock.index'),
            route('app.batches.index'),
            route('app.feed.index'),
            route('app.savings.index'),
            route('app.viability.index'),
        ]);
    }
@endphp
<body hx-boost="true"
      hx-headers='{"X-CSRF-TOKEN": "{{ csrf_token() }}"}'
      data-warm-routes='@json(array_values(array_unique($warmRoutes)))'>
    @php
        $fpRoute = request()->route()?->getName() ?? '';
        $fpVariant = match (true) {
            $fpRoute === 'app.dashboard' => 'dashboard',
            str_starts_with($fpRoute, 'app.eggs') => 'eggs',
            str_starts_with($fpRoute, 'app.account') => 'account',
            str_starts_with($fpRoute, 'app.flock') => 'flock',
            str_starts_with($fpRoute, 'app.batches') => 'batches',
            str_starts_with($fpRoute, 'app.crm') => 'crm',
            str_starts_with($fpRoute, 'app.expenses') => 'expenses',
            str_starts_with($fpRoute, 'app.feed') => 'feed',
            str_starts_with($fpRoute, 'app.savings') => 'savings',
            str_starts_with($fpRoute, 'app.viability') => 'viability',
            default => 'default',
        };
    @endphp
    @include("partials.fp-skeleton.{$fpVariant}")
    <div class="app-layout">
        <x-layout.sidebar />
        <main class="app-layout__main">
            <x-layout.navbar :title="$__env->yieldContent('title', __('dashboard.page.title'))" />
            <x-ui.flash />
            <x-ui.pwa-banner />
            <div class="app-layout__content has-loading-skeleton{{ request()->routeIs('app.dashboard') ? '' : ' app-layout__content--narrow' }}" id="main-content" data-loading-skeleton="page-shell">
                @yield('content')
            </div>
        </main>
        <x-layout.mobile-dock />
    </div>
    <div id="modal-container" aria-live="polite"></div>
    <x-ui.toast />
    <div hidden aria-hidden="true">
        <template id="skeleton-template-page-shell"><x-ui.skeleton-loader variant="page-shell" /></template>
        <template id="skeleton-template-crm-tab"><x-ui.skeleton-loader variant="crm-tab" /></template>
        <template id="skeleton-template-account-tab"><x-ui.skeleton-loader variant="account-tab" /></template>
    </div>
    @stack('scripts')
</body>
</html>

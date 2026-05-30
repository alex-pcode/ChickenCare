@props(['title' => __('dashboard.page.title')])

<header class="navbar">
    <h1 class="navbar__title">{{ $title }}</h1>

    <div class="navbar__user-menu" x-data="{ open: false }">
        <button @click="open = !open" class="navbar__user-button">
            {{ auth()->user()->name }}
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                <polyline points="6 9 12 15 18 9"/>
            </svg>
        </button>

        <div x-show="open" @click.away="open = false" x-cloak class="navbar__dropdown">
            @if(Route::has('app.account.index'))
            <a href="{{ route('app.account.index') }}" class="navbar__dropdown-item">{{ __('navigation.navbar.account_settings') }}</a>
            @endif
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="navbar__dropdown-item">{{ __('navigation.navbar.logout') }}</button>
            </form>
        </div>
    </div>
</header>

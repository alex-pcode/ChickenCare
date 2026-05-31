@extends('layouts.guest')

@section('title', __('auth.pages.login.title'))

@section('content')
    <form method="POST" action="{{ route('login') }}" class="auth-form">
        @csrf

        <img src="{{ asset('images/cute chicken sign in icon.webp') }}" alt="" aria-hidden="true" class="auth-form__icon">

        <h1 class="auth-form__title">{{ __('auth.pages.login.title') }}</h1>

        @if (session('auth_error'))
            <div class="auth-form__alert auth-form__alert--error" role="alert" aria-live="polite">
                {{ session('auth_error') }}
            </div>
        @endif

        @if (session('status'))
            <div class="auth-form__status" role="alert" aria-live="polite">
                {{ session('status') }}
            </div>
        @endif

        @include('auth.partials.social-providers', ['mode' => 'login'])

        <div class="auth-form__field">
            <label for="email" class="form-label">{{ __('auth.fields.email') }}</label>
            <input id="email" class="form-input" type="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="username"
                @if ($errors->has('email')) aria-invalid="true" aria-describedby="email-error" @endif>
            @error('email')
                <p id="email-error" class="form-error" role="alert">{{ $message }}</p>
            @enderror
        </div>

        <div class="auth-form__field">
            <label for="password" class="form-label">{{ __('auth.fields.password') }}</label>
            <input id="password" class="form-input" type="password" name="password" required autocomplete="current-password"
                @if ($errors->has('password')) aria-invalid="true" aria-describedby="password-error" @endif>
            @error('password')
                <p id="password-error" class="form-error" role="alert">{{ $message }}</p>
            @enderror
        </div>

        <div class="auth-form__field">
            <label for="remember_me" class="form-label form-label--inline">
                <input id="remember_me" type="checkbox" name="remember">
                {{ __('auth.pages.login.remember') }}
            </label>
        </div>

        <div class="auth-form__actions">
            @if (Route::has('password.request'))
                <a class="auth-form__link" href="{{ route('password.request') }}">
                    {{ __('auth.pages.login.forgot_password') }}
                </a>
            @endif

            <button type="submit" class="shiny-cta"><span>{{ __('auth.pages.login.submit') }}</span></button>
        </div>
    </form>
@endsection

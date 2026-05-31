@extends('layouts.guest')

@section('title', __('auth.pages.register.title'))

@section('content')
    <form method="POST" action="{{ route('register') }}" class="auth-form">
        @csrf

        <img src="{{ asset('images/cute chicken register icon.webp') }}" alt="" aria-hidden="true" class="auth-form__icon">

        <h1 class="auth-form__title">{{ __('auth.pages.register.title') }}</h1>

        @if (session('auth_error'))
            <div class="auth-form__alert auth-form__alert--error" role="alert" aria-live="polite">
                {{ session('auth_error') }}
            </div>
        @endif

        @include('auth.partials.social-providers', ['mode' => 'register'])

        <div class="auth-form__field">
            <label for="name" class="form-label">{{ __('auth.fields.name') }}</label>
            <input id="name" class="form-input" type="text" name="name" value="{{ old('name') }}" required autofocus autocomplete="name"
                @if ($errors->has('name')) aria-invalid="true" aria-describedby="name-error" @endif>
            @error('name')
                <p id="name-error" class="form-error" role="alert">{{ $message }}</p>
            @enderror
        </div>

        <div class="auth-form__field">
            <label for="email" class="form-label">{{ __('auth.fields.email') }}</label>
            <input id="email" class="form-input" type="email" name="email" value="{{ old('email') }}" required autocomplete="username"
                @if ($errors->has('email')) aria-invalid="true" aria-describedby="email-error" @endif>
            @error('email')
                <p id="email-error" class="form-error" role="alert">{{ $message }}</p>
            @enderror
        </div>

        <div class="auth-form__field">
            <label for="password" class="form-label">{{ __('auth.fields.password') }}</label>
            <input id="password" class="form-input" type="password" name="password" required autocomplete="new-password"
                @if ($errors->has('password')) aria-invalid="true" aria-describedby="password-error" @endif>
            @error('password')
                <p id="password-error" class="form-error" role="alert">{{ $message }}</p>
            @enderror
        </div>

        <div class="auth-form__field">
            <label for="password_confirmation" class="form-label">{{ __('auth.fields.password_confirmation') }}</label>
            <input id="password_confirmation" class="form-input" type="password" name="password_confirmation" required autocomplete="new-password">
        </div>

        <div class="auth-form__actions">
            <a class="auth-form__link" href="{{ route('login') }}">
                {{ __('auth.pages.register.already_registered') }}
            </a>

            <button type="submit" class="shiny-cta"><span>{{ __('auth.pages.register.submit') }}</span></button>
        </div>
    </form>
@endsection

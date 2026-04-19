@extends('layouts.guest')

@section('title', 'Log In')

@section('content')
    <form method="POST" action="{{ route('login') }}" class="auth-form">
        @csrf

        <h1 class="auth-form__title">{{ __('Log In') }}</h1>

        @if (session('status'))
            <div class="auth-form__status" role="alert" aria-live="polite">
                {{ session('status') }}
            </div>
        @endif

        <div class="auth-form__field">
            <label for="email" class="form-label">{{ __('Email') }}</label>
            <input id="email" class="form-input" type="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="username"
                @if ($errors->has('email')) aria-invalid="true" aria-describedby="email-error" @endif>
            @error('email')
                <p id="email-error" class="form-error" role="alert">{{ $message }}</p>
            @enderror
        </div>

        <div class="auth-form__field">
            <label for="password" class="form-label">{{ __('Password') }}</label>
            <input id="password" class="form-input" type="password" name="password" required autocomplete="current-password"
                @if ($errors->has('password')) aria-invalid="true" aria-describedby="password-error" @endif>
            @error('password')
                <p id="password-error" class="form-error" role="alert">{{ $message }}</p>
            @enderror
        </div>

        <div class="auth-form__field">
            <label for="remember_me" class="form-label form-label--inline">
                <input id="remember_me" type="checkbox" name="remember">
                {{ __('Remember me') }}
            </label>
        </div>

        <div class="auth-form__actions">
            @if (Route::has('password.request'))
                <a class="auth-form__link" href="{{ route('password.request') }}">
                    {{ __('Forgot your password?') }}
                </a>
            @endif

            <button type="submit" class="btn btn--primary">{{ __('Log in') }}</button>
        </div>
    </form>
@endsection

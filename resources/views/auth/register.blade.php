@extends('layouts.guest')

@section('title', 'Register')

@section('content')
    <form method="POST" action="{{ route('register') }}" class="auth-form">
        @csrf

        <h1 class="auth-form__title">{{ __('Register') }}</h1>

        <div class="auth-form__field">
            <label for="name" class="form-label">{{ __('Name') }}</label>
            <input id="name" class="form-input" type="text" name="name" value="{{ old('name') }}" required autofocus autocomplete="name"
                @if ($errors->has('name')) aria-invalid="true" aria-describedby="name-error" @endif>
            @error('name')
                <p id="name-error" class="form-error" role="alert">{{ $message }}</p>
            @enderror
        </div>

        <div class="auth-form__field">
            <label for="email" class="form-label">{{ __('Email') }}</label>
            <input id="email" class="form-input" type="email" name="email" value="{{ old('email') }}" required autocomplete="username"
                @if ($errors->has('email')) aria-invalid="true" aria-describedby="email-error" @endif>
            @error('email')
                <p id="email-error" class="form-error" role="alert">{{ $message }}</p>
            @enderror
        </div>

        <div class="auth-form__field">
            <label for="password" class="form-label">{{ __('Password') }}</label>
            <input id="password" class="form-input" type="password" name="password" required autocomplete="new-password"
                @if ($errors->has('password')) aria-invalid="true" aria-describedby="password-error" @endif>
            @error('password')
                <p id="password-error" class="form-error" role="alert">{{ $message }}</p>
            @enderror
        </div>

        <div class="auth-form__field">
            <label for="password_confirmation" class="form-label">{{ __('Confirm Password') }}</label>
            <input id="password_confirmation" class="form-input" type="password" name="password_confirmation" required autocomplete="new-password">
        </div>

        <div class="auth-form__actions">
            <a class="auth-form__link" href="{{ route('login') }}">
                {{ __('Already registered?') }}
            </a>

            <button type="submit" class="btn btn--primary">{{ __('Register') }}</button>
        </div>
    </form>
@endsection

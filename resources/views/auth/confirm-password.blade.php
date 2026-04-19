@extends('layouts.guest')

@section('title', 'Confirm Password')

@section('content')
    <form method="POST" action="{{ route('password.confirm') }}" class="auth-form">
        @csrf

        <h1 class="auth-form__title">{{ __('Confirm Password') }}</h1>

        <p class="auth-form__description">
            {{ __('This is a secure area of the application. Please confirm your password before continuing.') }}
        </p>

        <div class="auth-form__field">
            <label for="password" class="form-label">{{ __('Password') }}</label>
            <input id="password" class="form-input" type="password" name="password" required autocomplete="current-password"
                @if ($errors->has('password')) aria-invalid="true" aria-describedby="password-error" @endif>
            @error('password')
                <p id="password-error" class="form-error" role="alert">{{ $message }}</p>
            @enderror
        </div>

        <div class="auth-form__actions">
            <button type="submit" class="btn btn--primary">{{ __('Confirm') }}</button>
        </div>
    </form>
@endsection

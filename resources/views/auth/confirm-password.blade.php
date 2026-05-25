@extends('layouts.guest')

@section('title', __('auth.pages.confirm_password.title'))

@section('content')
    <form method="POST" action="{{ route('password.confirm') }}" class="auth-form">
        @csrf

        <h1 class="auth-form__title">{{ __('auth.pages.confirm_password.title') }}</h1>

        <p class="auth-form__description">
            {{ __('auth.pages.confirm_password.description') }}
        </p>

        <div class="auth-form__field">
            <label for="password" class="form-label">{{ __('auth.fields.password') }}</label>
            <input id="password" class="form-input" type="password" name="password" required autocomplete="current-password"
                @if ($errors->has('password')) aria-invalid="true" aria-describedby="password-error" @endif>
            @error('password')
                <p id="password-error" class="form-error" role="alert">{{ $message }}</p>
            @enderror
        </div>

        <div class="auth-form__actions">
            <button type="submit" class="btn btn--primary">{{ __('auth.pages.confirm_password.submit') }}</button>
        </div>
    </form>
@endsection

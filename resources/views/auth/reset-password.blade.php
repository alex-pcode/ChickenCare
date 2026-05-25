@extends('layouts.guest')

@section('title', __('auth.pages.reset_password.title'))

@section('content')
    <form method="POST" action="{{ route('password.store') }}" class="auth-form">
        @csrf

        <input type="hidden" name="token" value="{{ $request->route('token') }}">

        <h1 class="auth-form__title">{{ __('auth.pages.reset_password.title') }}</h1>

        <div class="auth-form__field">
            <label for="email" class="form-label">{{ __('auth.fields.email') }}</label>
            <input id="email" class="form-input" type="email" name="email" value="{{ old('email', $request->email) }}" required autofocus autocomplete="username"
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
            <button type="submit" class="btn btn--primary">{{ __('auth.pages.reset_password.submit') }}</button>
        </div>
    </form>
@endsection

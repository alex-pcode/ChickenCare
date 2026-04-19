@extends('layouts.guest')

@section('title', 'Forgot Password')

@section('content')
    <form method="POST" action="{{ route('password.email') }}" class="auth-form">
        @csrf

        <h1 class="auth-form__title">{{ __('Forgot Password') }}</h1>

        <p class="auth-form__description">
            {{ __('Forgot your password? No problem. Just let us know your email address and we will email you a password reset link that will allow you to choose a new one.') }}
        </p>

        @if (session('status'))
            <div class="auth-form__status" role="alert" aria-live="polite">
                {{ session('status') }}
            </div>
        @endif

        <div class="auth-form__field">
            <label for="email" class="form-label">{{ __('Email') }}</label>
            <input id="email" class="form-input" type="email" name="email" value="{{ old('email') }}" required autofocus
                @if ($errors->has('email')) aria-invalid="true" aria-describedby="email-error" @endif>
            @error('email')
                <p id="email-error" class="form-error" role="alert">{{ $message }}</p>
            @enderror
        </div>

        <div class="auth-form__actions">
            <button type="submit" class="btn btn--primary">{{ __('Email Password Reset Link') }}</button>
        </div>
    </form>
@endsection

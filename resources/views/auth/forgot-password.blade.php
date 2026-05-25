@extends('layouts.guest')

@section('title', __('auth.pages.forgot_password.title'))

@section('content')
    <form method="POST" action="{{ route('password.email') }}" class="auth-form">
        @csrf

        <h1 class="auth-form__title">{{ __('auth.pages.forgot_password.title') }}</h1>

        <p class="auth-form__description">
            {{ __('auth.pages.forgot_password.description') }}
        </p>

        @if (session('status'))
            <div class="auth-form__status" role="alert" aria-live="polite">
                {{ session('status') }}
            </div>
        @endif

        <div class="auth-form__field">
            <label for="email" class="form-label">{{ __('auth.fields.email') }}</label>
            <input id="email" class="form-input" type="email" name="email" value="{{ old('email') }}" required autofocus
                @if ($errors->has('email')) aria-invalid="true" aria-describedby="email-error" @endif>
            @error('email')
                <p id="email-error" class="form-error" role="alert">{{ $message }}</p>
            @enderror
        </div>

        <div class="auth-form__actions">
            <button type="submit" class="btn btn--primary">{{ __('auth.pages.forgot_password.submit') }}</button>
        </div>
    </form>
@endsection

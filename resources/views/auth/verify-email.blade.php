@extends('layouts.guest')

@section('title', __('auth.pages.verify_email.title'))

@section('content')
    <div class="auth-form">
        <h1 class="auth-form__title">{{ __('auth.pages.verify_email.title') }}</h1>

        <p class="auth-form__description">
            {{ __('auth.pages.verify_email.description') }}
        </p>

        @if (session('status') == 'verification-link-sent')
            <div class="auth-form__status" role="alert" aria-live="polite">
                {{ __('auth.pages.verify_email.status') }}
            </div>
        @endif

        <div class="auth-form__actions">
            <form method="POST" action="{{ route('verification.send') }}">
                @csrf
                <button type="submit" class="btn btn--primary">{{ __('auth.pages.verify_email.resend') }}</button>
            </form>

            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="auth-form__link">{{ __('auth.pages.verify_email.logout') }}</button>
            </form>
        </div>
    </div>
@endsection

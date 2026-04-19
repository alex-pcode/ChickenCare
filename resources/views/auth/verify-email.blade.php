@extends('layouts.guest')

@section('title', 'Verify Email')

@section('content')
    <div class="auth-form">
        <h1 class="auth-form__title">{{ __('Verify Email') }}</h1>

        <p class="auth-form__description">
            {{ __('Thanks for signing up! Before getting started, could you verify your email address by clicking on the link we just emailed to you? If you didn\'t receive the email, we will gladly send you another.') }}
        </p>

        @if (session('status') == 'verification-link-sent')
            <div class="auth-form__status" role="alert" aria-live="polite">
                {{ __('A new verification link has been sent to the email address you provided during registration.') }}
            </div>
        @endif

        <div class="auth-form__actions">
            <form method="POST" action="{{ route('verification.send') }}">
                @csrf
                <button type="submit" class="btn btn--primary">{{ __('Resend Verification Email') }}</button>
            </form>

            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="auth-form__link">{{ __('Log Out') }}</button>
            </form>
        </div>
    </div>
@endsection

@extends('layouts.guest')

@section('title', __('ui.pwa.offline_title'))

@section('full-content')
    <main class="auth-layout">
        <section class="auth-card">
            <div class="auth-card__content">
                <h1>{{ __('ui.pwa.offline_title') }}</h1>
                <p>{{ __('ui.pwa.offline_message') }}</p>
                <a href="{{ route('landing') }}" class="btn btn--primary">{{ __('ui.pwa.back_home') }}</a>
            </div>
        </section>
    </main>
@endsection
@extends('layouts.guest')

@section('title', 'Manage Your Flock — Track Eggs & Expenses')

@section('full-content')
<div class="landing">
    @include('landing.partials.navbar')
    @include('landing.partials.hero')
    @include('landing.partials.problem-statement')
    @include('landing.partials.personas')
    @include('landing.partials.features')
    @include('landing.partials.social-proof')
    @include('landing.partials.pricing')
    @include('landing.partials.final-cta')
    @include('landing.partials.footer')
    @include('landing.partials.fullscreen-modal')
</div>
@endsection

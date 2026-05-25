@extends('layouts.guest')

@section('title', 'Cost Calculator')

@section('full-content')
<div class="landing">
    @include('landing.partials.navbar')

    <section class="landing__hero">
        <div class="landing__hero-content">
            <h1 class="landing__hero-headline landing__hero-headline--visible">
                Know Your <span class="landing__hero-headline-gradient">Real Cost Per Egg</span>
            </h1>

            <p class="landing__hero-sub landing__hero-sub--visible">
                ChickenCare connects feed spend, flock performance, and egg output so you can stop guessing and start pricing with confidence.
            </p>

            <div class="landing__hero-cta landing__hero-cta--visible">
                <a href="{{ route('register') }}" class="shiny-cta">
                    <span>Start Calculating Free</span>
                </a>
            </div>
        </div>
    </section>

    @include('landing.partials.pricing')
    @include('landing.partials.final-cta')
</div>
@endsection
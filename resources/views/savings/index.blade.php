@extends('layouts.app')

@section('title', 'Savings Analysis')

@section('content')
<div class="savings">
    @include('savings.partials.hero')

    @include('savings.partials.preferences')

    <div id="savings-financial-summary">
        @include('savings.partials.financial-summary')
    </div>

    @include('savings.partials.lifetime-impact')
</div>
@endsection

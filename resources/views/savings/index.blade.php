@extends('layouts.app')

@section('title', __('savings.page.title'))

@section('content')
<div class="savings">
    @include('savings.partials.hero')

    <div id="savings-financial-summary">
        @include('savings.partials.financial-summary')
    </div>

    @include('savings.partials.lifetime-impact')
</div>
@endsection

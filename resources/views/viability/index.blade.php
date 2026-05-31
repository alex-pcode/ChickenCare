@extends('layouts.app')

@section('title', __('viability.page.title'))

@section('content')
    <x-layout.page-header :title="__('viability.page.header')" />

    @include('viability.partials.calculator', ['newDefaults' => $newDefaults])
@endsection

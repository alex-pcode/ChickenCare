@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
    <h2>Welcome, {{ Auth::user()->name }}!</h2>
    <p>The dashboard is under construction.</p>
@endsection

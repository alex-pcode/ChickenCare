@extends('layouts.app')

@section('title', 'Flock Batches')

@section('content')
<div class="batches"
     @modal:close.window="document.getElementById('modal-container').innerHTML = ''">

    <x-layout.page-header title="Flock Batches">
        <x-slot:actions>
            <a href="{{ route('app.batches.create') }}" class="btn btn--primary">Add Batch</a>
        </x-slot:actions>
    </x-layout.page-header>

    <div class="batches__filter-bar" role="group" aria-label="Filter batches">
        <a href="{{ route('app.batches.index', ['filter' => 'active']) }}"
           class="batches__filter-btn {{ $filter === 'active' ? 'batches__filter-btn--active' : '' }}"
           @if($filter === 'active') aria-current="true" @endif>
            Active
        </a>
        <a href="{{ route('app.batches.index', ['filter' => 'archived']) }}"
           class="batches__filter-btn {{ $filter === 'archived' ? 'batches__filter-btn--active' : '' }}"
           @if($filter === 'archived') aria-current="true" @endif>
            Archived
        </a>
        <a href="{{ route('app.batches.index', ['filter' => 'all']) }}"
           class="batches__filter-btn {{ $filter === 'all' ? 'batches__filter-btn--active' : '' }}"
           @if($filter === 'all') aria-current="true" @endif>
            All
        </a>
    </div>

    @include('batches.partials.batches-table', ['batches' => $batches, 'sort' => $sort, 'dir' => $dir])
</div>
@endsection

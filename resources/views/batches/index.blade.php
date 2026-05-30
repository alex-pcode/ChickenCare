@extends('layouts.app')

@section('title', __('batches.page.title'))

@section('content')
<div class="batches"
     @modal:close.window="document.getElementById('modal-container').innerHTML = ''">

    @include('batches.partials.hero')

    <div class="batches__filter-bar" role="group" aria-label="{{ __('batches.filters.label') }}">
        <a href="{{ route('app.batches.index', ['filter' => 'active']) }}"
           class="batches__filter-btn {{ $filter === 'active' ? 'batches__filter-btn--active' : '' }}"
           @if($filter === 'active') aria-current="true" @endif>
            {{ __('batches.filters.active') }}
        </a>
        <a href="{{ route('app.batches.index', ['filter' => 'archived']) }}"
           class="batches__filter-btn {{ $filter === 'archived' ? 'batches__filter-btn--active' : '' }}"
           @if($filter === 'archived') aria-current="true" @endif>
            {{ __('batches.filters.archived') }}
        </a>
        <a href="{{ route('app.batches.index', ['filter' => 'all']) }}"
           class="batches__filter-btn {{ $filter === 'all' ? 'batches__filter-btn--active' : '' }}"
           @if($filter === 'all') aria-current="true" @endif>
            {{ __('batches.filters.all') }}
        </a>
    </div>

    @include('batches.partials.batches-table', ['batches' => $batches, 'sort' => $sort, 'dir' => $dir])
</div>
@endsection

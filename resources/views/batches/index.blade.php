@extends('layouts.app')

@section('title', 'Flock Batches')

@section('content')
<div class="batches"
     x-data="{
         toastMessage: '',
         toastType: 'success',
         showToast: false,
         displayToast(message, type) {
             this.toastMessage = message;
             this.toastType = type ?? 'success';
             this.showToast = true;
             setTimeout(() => this.showToast = false, 4000);
         }
     }"
     @flock:changed.window="displayToast(event.detail?.message ?? 'Flock updated.', event.detail?.type ?? 'success')"
     @flock:success.window="displayToast($event.detail?.value ?? $event.detail?.message ?? 'Updated.', 'success')"
     @flock:error.window="displayToast($event.detail?.value ?? $event.detail?.message ?? 'Something went wrong.', 'error')"
     @modal:close.window="document.getElementById('modal-container').innerHTML = ''">

    <div id="modal-container"></div>

    {{-- Toast --}}
    <div class="fixed bottom-6 right-6 z-50 w-80"
         x-show="showToast"
         x-cloak
         :role="toastType === 'success' ? 'status' : 'alert'"
         aria-live="polite"
         x-transition:enter="transition ease-out duration-300 transform"
         x-transition:enter-start="opacity-0 translate-y-4"
         x-transition:enter-end="opacity-100 translate-y-0"
         x-transition:leave="transition ease-in duration-200 transform"
         x-transition:leave-start="opacity-100 translate-y-0"
         x-transition:leave-end="opacity-0 translate-y-4">
        <div :class="toastType === 'success'
                ? 'bg-green-50 dark:bg-green-900/30 border border-green-200 dark:border-green-700 text-green-800 dark:text-green-300'
                : 'bg-red-50 dark:bg-red-900/30 border border-red-200 dark:border-red-700 text-red-800 dark:text-red-300'"
             class="rounded-xl px-4 py-3 shadow-lg flex items-center gap-3">
            <span x-text="toastMessage"></span>
        </div>
    </div>

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

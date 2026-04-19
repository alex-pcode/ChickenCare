@props(['paginator', 'window' => 2])

@php
    $current = $paginator->currentPage();
    $last = $paginator->lastPage();
    $pages = collect(range(1, $last))
        ->filter(fn ($p) => $p === 1 || $p === $last || abs($p - $current) <= $window)
        ->values();
@endphp

@if($paginator->hasPages())
    <nav class="pagination" aria-label="Pagination">
        @if($paginator->onFirstPage())
            <span class="pagination__link pagination__link--disabled" aria-disabled="true">Previous</span>
        @else
            <a href="{{ $paginator->previousPageUrl() }}" class="pagination__link" rel="prev">Previous</a>
        @endif

        @php($prev = 0)
        @foreach($pages as $page)
            @if($prev && $page - $prev > 1)
                <span class="pagination__ellipsis" aria-hidden="true">…</span>
            @endif
            @if($page === $current)
                <span class="pagination__link pagination__link--active" aria-current="page">{{ $page }}</span>
            @else
                <a href="{{ $paginator->url($page) }}" class="pagination__link" aria-label="Go to page {{ $page }}">{{ $page }}</a>
            @endif
            @php($prev = $page)
        @endforeach

        @if($paginator->hasMorePages())
            <a href="{{ $paginator->nextPageUrl() }}" class="pagination__link" rel="next">Next</a>
        @else
            <span class="pagination__link pagination__link--disabled" aria-disabled="true">Next</span>
        @endif
    </nav>
@endif

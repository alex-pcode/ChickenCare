@props(['paginator'])

@if($paginator->hasPages())
    <nav class="pagination" aria-label="Pagination">
        {{ $paginator->links() }}
    </nav>
@endif

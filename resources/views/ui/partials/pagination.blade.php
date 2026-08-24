@if($paginator->hasPages())
    <nav class="guard-pagination" aria-label="Pagination">
        <p>Showing <strong>{{ $paginator->firstItem() }}</strong>-<strong>{{ $paginator->lastItem() }}</strong> of <strong>{{ $paginator->total() }}</strong></p>
        <div>
            @if($paginator->onFirstPage())
                <span class="disabled" aria-disabled="true">Previous</span>
            @else
                <a href="{{ $paginator->previousPageUrl() }}" rel="prev">Previous</a>
            @endif
            @php($start = max(1, $paginator->currentPage() - 2))
            @php($end = min($paginator->lastPage(), $paginator->currentPage() + 2))
            @foreach(range($start, $end) as $page)
                @if($page === $paginator->currentPage())
                    <span class="current" aria-current="page">{{ $page }}</span>
                @else
                    <a href="{{ $paginator->url($page) }}">{{ $page }}</a>
                @endif
            @endforeach
            @if($paginator->hasMorePages())
                <a href="{{ $paginator->nextPageUrl() }}" rel="next">Next</a>
            @else
                <span class="disabled" aria-disabled="true">Next</span>
            @endif
        </div>
    </nav>
@endif

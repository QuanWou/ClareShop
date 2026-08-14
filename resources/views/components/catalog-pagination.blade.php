@props(['paginator'])

@if ($paginator->hasPages())
    <nav class="products-pagination-nav" role="navigation" aria-label="Phân trang sản phẩm">
        <p>Trang <strong>{{ $paginator->currentPage() }}</strong> / {{ $paginator->lastPage() }}</p>

        <div class="products-pagination-links">
            @if ($paginator->onFirstPage())
                <span class="products-pagination-direction is-disabled" aria-disabled="true">← Trước</span>
            @else
                <a class="products-pagination-direction" href="{{ $paginator->previousPageUrl() }}" rel="prev">← Trước</a>
            @endif

            @foreach ($paginator->getUrlRange(1, $paginator->lastPage()) as $page => $url)
                @if ($page === $paginator->currentPage())
                    <span class="products-pagination-page is-current" aria-current="page">{{ $page }}</span>
                @else
                    <a class="products-pagination-page" href="{{ $url }}" aria-label="Đến trang {{ $page }}">{{ $page }}</a>
                @endif
            @endforeach

            @if ($paginator->hasMorePages())
                <a class="products-pagination-direction" href="{{ $paginator->nextPageUrl() }}" rel="next">Sau →</a>
            @else
                <span class="products-pagination-direction is-disabled" aria-disabled="true">Sau →</span>
            @endif
        </div>
    </nav>
@endif

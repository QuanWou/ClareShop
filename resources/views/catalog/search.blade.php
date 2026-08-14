@extends('layouts.storefront', [
    'title' => 'Tìm “'.$query.'”',
    'description' => 'Kết quả tìm kiếm sản phẩm Clare cho '.$query.'.',
    'bodyClass' => 'catalog-search-page',
])

@section('content')
    <section class="catalog-search-hero" aria-labelledby="search-results-title">
        <div class="shell catalog-search-hero-inner" data-reveal data-reveal-immediate>
            <nav class="catalog-breadcrumbs" aria-label="Đường dẫn trang">
                <a href="{{ route('catalog.home') }}">Trang chủ</a>
                <span aria-hidden="true">/</span>
                <span aria-current="page">Tìm kiếm</span>
            </nav>

            <p class="eyebrow">Tìm trong Clare</p>
            <h1 id="search-results-title">Kết quả cho <mark>“{{ $query }}”</mark></h1>
            <p>Thử một kiểu đèn, chất liệu hoặc tên góc nhà. Clare sẽ tìm trong những mẫu đang thực sự có bán.</p>

            <form class="catalog-search-form" action="{{ route('catalog.search') }}" method="get" role="search">
                <label for="search-results-query">Bạn muốn tìm chiếc đèn nào?</label>
                <div>
                    <input id="search-results-query" name="q" type="search" value="{{ $query }}" minlength="2" maxlength="80" required autocomplete="off">
                    <button class="button button-primary" type="submit">Tìm sản phẩm</button>
                </div>
            </form>
        </div>
    </section>

    <section class="catalog-search-results section" aria-labelledby="search-list-title">
        <div class="shell">
            <nav class="search-category-suggestions" aria-label="Khám phá theo danh mục" data-search-categories data-reveal>
                <span>Hoặc xem nhanh</span>
                <a href="{{ route('catalog.products.index') }}">Tất cả đèn</a>
                @foreach ($categories as $category)
                    <a href="{{ route('catalog.collections.show', $category) }}">
                        {{ $category->name }} <small>{{ $category->published_products_count }}</small>
                    </a>
                @endforeach
            </nav>

            <div class="search-listing-heading" data-reveal>
                <div>
                    <p class="eyebrow">Những kết quả gần nhất</p>
                    <h2 id="search-list-title">{{ $products->total() > 0 ? 'Có thể bạn đang tìm.' : 'Chưa thấy chiếc đèn ấy.' }}</h2>
                </div>
                <p><strong>{{ $products->total() }}</strong> sản phẩm phù hợp</p>
            </div>

            <div class="product-grid search-product-grid" data-reveal-group>
                @forelse ($products as $product)
                    <x-product-card :product="$product" reveal />
                @empty
                    <div class="catalog-search-empty">
                        <span class="catalog-search-empty-mark" aria-hidden="true">?</span>
                        <div>
                            <h2>Clare chưa tìm thấy “{{ $query }}”.</h2>
                            <p>Thử từ khóa ngắn hơn như “đèn bàn”, “gỗ”, “opal”, hoặc khám phá toàn bộ catalog đang có.</p>
                        </div>
                        <a class="button button-primary" href="{{ route('catalog.products.index') }}">Xem tất cả đèn</a>
                    </div>
                @endforelse
            </div>

            @if ($products->hasPages())
                <div class="pagination-wrap search-pagination">
                    <x-catalog-pagination :paginator="$products" />
                </div>
            @endif
        </div>
    </section>
@endsection

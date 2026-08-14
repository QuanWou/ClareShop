@extends('layouts.storefront', [
    'title' => $selectedCategory?->name ?? 'Tất cả đèn',
    'description' => $selectedCategory?->description ?: 'Khám phá toàn bộ các mẫu đèn Clare đang có.',
    'bodyClass' => 'catalog-products-page',
])

@section('content')
    <section class="products-hero" aria-labelledby="all-products-title">
        <div class="shell products-hero-grid">
            <div class="products-hero-copy" data-reveal data-reveal-immediate>
                <nav class="products-breadcrumbs" aria-label="Đường dẫn trang">
                    <a href="{{ route('catalog.home') }}">Trang chủ</a>
                    <span aria-hidden="true">/</span>
                    <span aria-current="page">{{ $selectedCategory?->name ?? 'Tất cả đèn' }}</span>
                </nav>

                <p class="eyebrow">Thư viện ánh sáng / Clare</p>
                <h1 id="all-products-title">
                    @if ($selectedCategory)
                        {{ $selectedCategory->name }}<em>, chọn cho đúng góc.</em>
                    @else
                        Chiếc đèn khiến bạn <em>muốn về nhà sớm hơn.</em>
                    @endif
                </h1>
                <p class="products-hero-intro">{{ $selectedCategory?->description ?? 'Từ chiếc đèn nhỏ bên giường đến ánh sáng cho góc đọc sách — mỗi mẫu Clare đều được chọn để căn phòng ấm hơn mà vẫn là chính bạn.' }}</p>

                <ul class="products-hero-notes" aria-label="Thông tin mua sắm">
                    <li><span aria-hidden="true"></span>Giá rõ theo từng màu</li>
                    <li><span aria-hidden="true"></span>Tồn kho cập nhật</li>
                    <li><span aria-hidden="true"></span>Tư vấn khi bạn cần</li>
                </ul>
            </div>

            <div class="products-hero-collage" aria-label="Một vài mẫu đèn Clare" data-products-collage data-reveal data-reveal-immediate>
                @foreach ($products->getCollection()->take(3) as $previewProduct)
                    @php($previewImage = $previewProduct->images->first())

                    @if ($previewImage)
                        <a class="products-collage-card products-collage-card-{{ $loop->iteration }}" href="{{ route('catalog.products.show', $previewProduct) }}">
                            <img
                                src="{{ $previewImage->url }}"
                                alt="{{ $previewImage->alt_text ?? $previewProduct->name }}"
                                width="720"
                                height="720"
                            >
                            <span>{{ $previewProduct->name }}</span>
                        </a>
                    @endif
                @endforeach

                <span class="products-collage-spark products-collage-spark-one" aria-hidden="true">✦</span>
                <span class="products-collage-spark products-collage-spark-two" aria-hidden="true">✦</span>
                <p class="products-collage-note"><span>{{ $products->total() }}</span> mẫu đang chờ bạn</p>
            </div>
        </div>
    </section>

    <section class="all-products-page section" aria-labelledby="products-grid-title">
        <div class="shell">
            <div class="products-filter-panel" data-reveal>
                <div class="products-filter-heading">
                    <span>Chọn theo góc nhà</span>
                    <strong>{{ $selectedCategory?->name ?? 'Tất cả kiểu đèn' }}</strong>
                </div>

                <nav class="catalog-category-filter" aria-label="Lọc theo loại đèn">
                    <a
                        @class(['is-current' => $selectedCategory === null])
                        href="{{ route('catalog.products.index') }}"
                        @if ($selectedCategory === null) aria-current="page" @endif
                    >
                        <span>Tất cả</span><small>{{ $totalProductCount }}</small>
                    </a>
                    @foreach ($categories as $category)
                        <a
                            @class(['is-current' => $selectedCategory?->is($category)])
                            href="{{ route('catalog.products.index', ['category' => $category->slug]) }}"
                            @if ($selectedCategory?->is($category)) aria-current="page" @endif
                        >
                            <span>{{ $category->name }}</span><small>{{ $category->published_products_count }}</small>
                        </a>
                    @endforeach
                </nav>
            </div>

            <div class="products-listing-heading" data-reveal>
                <div>
                    <p class="eyebrow">Dành cho căn phòng của bạn</p>
                    <h2 id="products-grid-title">{{ $selectedCategory ? 'Những mẫu '.$selectedCategory->name : 'Tất cả mẫu đang có' }}</h2>
                </div>
                <div class="products-listing-meta">
                    <p><strong>{{ $products->total() }}</strong> sản phẩm</p>
                    <span>Clare chọn mẫu nổi bật trước</span>
                    @if ($selectedCategory)
                        <a href="{{ route('catalog.products.index') }}">Gỡ bộ lọc <span aria-hidden="true">×</span></a>
                    @endif
                </div>
            </div>

            <div class="product-grid products-catalog-grid" data-reveal-group>
                @forelse ($products as $product)
                    <x-product-card :product="$product" reveal />
                @empty
                    <div class="products-empty-state">
                        <span aria-hidden="true">✦</span>
                        <h2>Góc này đang chờ một chiếc đèn mới.</h2>
                        <p>Thử xem toàn bộ bộ sưu tập hoặc kể Clare nghe về căn phòng của bạn.</p>
                        <a class="button button-primary" href="{{ route('catalog.products.index') }}">Xem tất cả đèn</a>
                    </div>
                @endforelse
            </div>

            @if ($products->hasPages())
                <div class="pagination-wrap products-pagination">
                    <x-catalog-pagination :paginator="$products" />
                </div>
            @endif

            <aside class="products-consultation-card" aria-labelledby="products-consultation-title" data-reveal data-ambient>
                <div class="products-consultation-mark" aria-hidden="true">C</div>
                <div>
                    <p class="eyebrow">Một lời nhắc nhỏ</p>
                    <h2 id="products-consultation-title">Chọn đèn cũng giống chọn một người bạn cùng phòng.</h2>
                    <p>Nó nên vừa vặn, dễ sống cùng và khiến bạn thấy vui mỗi lần bật sáng. Nếu còn phân vân, Clare sẽ cùng bạn chọn.</p>
                </div>
                <a class="button button-light" href="{{ route('appointments.create') }}">Kể Clare nghe</a>
            </aside>
        </div>
    </section>
@endsection

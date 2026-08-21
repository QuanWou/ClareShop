@extends('layouts.storefront', [
    'title' => $selectedCategory?->name ?? 'Tất cả đèn',
    'description' => $selectedCategory?->description ?: $siteContent->get('catalog_meta_description'),
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

                <p class="eyebrow">{{ $siteContent->get('catalog_hero_eyebrow') }}</p>
                <h1 id="all-products-title">
                    @if ($selectedCategory)
                        {{ $selectedCategory->name }} <em>{{ $siteContent->get('collection_heading_suffix') }}</em>
                    @else
                        {{ $siteContent->get('catalog_hero_title') }} <em>{{ $siteContent->get('catalog_hero_emphasis') }}</em>
                    @endif
                </h1>
                <p class="products-hero-intro">{{ $selectedCategory?->description ?? $siteContent->get('catalog_hero_intro') }}</p>

                <ul class="products-hero-notes" aria-label="Thông tin mua sắm">
                    <li><span aria-hidden="true"></span>{{ $siteContent->get('catalog_note_price') }}</li>
                    <li><span aria-hidden="true"></span>{{ $siteContent->get('catalog_note_stock') }}</li>
                    <li><span aria-hidden="true"></span>{{ $siteContent->get('catalog_note_help') }}</li>
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
            <div class="catalog-mobile-toolbar" data-reveal>
                <button type="button" aria-controls="catalog-filter-sidebar" aria-expanded="false" data-catalog-filter-open>Bộ lọc</button>
                <span>{{ $products->total() }} sản phẩm phù hợp</span>
            </div>

            <div class="catalog-shop-layout">
                @include('catalog.partials.filters')

                <div class="catalog-results">
                    <div class="products-listing-heading" data-reveal>
                        <div>
                            <p class="eyebrow">{{ $siteContent->get('catalog_listing_eyebrow') }}</p>
                            <h2 id="products-grid-title">{{ $selectedCategory ? 'Những mẫu '.$selectedCategory->name : 'Tất cả mẫu đang có' }}</h2>
                            <p class="catalog-result-count"><strong>{{ $products->total() }}</strong> sản phẩm</p>
                        </div>
                        <div class="catalog-result-controls">
                            <label>
                                <span>Sắp xếp</span>
                                <select name="sort" form="catalog-filter-form" data-catalog-auto-submit>
                                    <option value="newest" @selected(($filters['sort'] ?? 'newest') === 'newest')>Mới nhất</option>
                                    <option value="bestselling" @selected(($filters['sort'] ?? '') === 'bestselling')>Bán chạy</option>
                                    <option value="price_asc" @selected(($filters['sort'] ?? '') === 'price_asc')>Giá tăng dần</option>
                                    <option value="price_desc" @selected(($filters['sort'] ?? '') === 'price_desc')>Giá giảm dần</option>
                                </select>
                            </label>
                            <div class="catalog-view-switch" aria-label="Kiểu hiển thị">
                                <a @class(['is-current' => $viewMode === 'grid']) href="{{ request()->fullUrlWithQuery(['view' => 'grid']) }}" aria-label="Hiển thị dạng lưới">▦</a>
                                <a @class(['is-current' => $viewMode === 'list']) href="{{ request()->fullUrlWithQuery(['view' => 'list']) }}" aria-label="Hiển thị dạng danh sách">☷</a>
                            </div>
                        </div>
                    </div>

                    <div @class(['product-grid', 'products-catalog-grid', 'is-list-view' => $viewMode === 'list']) data-reveal-group>
                        @forelse ($products as $product)
                            <x-product-card :product="$product" reveal />
                        @empty
                            <div class="products-empty-state">
                                <span aria-hidden="true">✦</span>
                                <h2>Chưa có mẫu phù hợp với bộ lọc.</h2>
                                <p>Đặt lại bộ lọc hoặc kể Clare nghe về căn phòng của bạn.</p>
                                <a class="button button-primary" href="{{ route('catalog.products.index') }}">Đặt lại bộ lọc</a>
                            </div>
                        @endforelse
                    </div>

                    @if ($products->hasPages())
                        <div class="pagination-wrap products-pagination">
                            <x-catalog-pagination :paginator="$products" />
                        </div>
                    @endif
                </div>
            </div>

            <aside class="products-consultation-card" aria-labelledby="products-consultation-title" data-reveal data-ambient>
                <div class="products-consultation-mark" aria-hidden="true">C</div>
                <div>
                    <p class="eyebrow">Một lời nhắc nhỏ</p>
                    <h2 id="products-consultation-title">{{ $siteContent->get('catalog_consultation_title') }}</h2>
                    <p>{{ $siteContent->get('catalog_consultation_body') }}</p>
                </div>
                <a class="button button-light" href="{{ route('appointments.create') }}">{{ $siteContent->get('catalog_consultation_cta') }}</a>
            </aside>
        </div>
    </section>
@endsection
